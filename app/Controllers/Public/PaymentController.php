<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;
use Unwinded\Services\PayFastService;
use Unwinded\Support\Ref;

/**
 * Token-gated payment page for private bookings.
 * Token is stored as SHA-256 hash; plaintext exists once in the payment link URL.
 */
class PaymentController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private PayFastService $payfast,
        private MailService    $mail,
    ) {}

    public function show(string $ref, string $token): Response
    {
        [$booking, $payfieldData] = $this->resolveAndBuild($ref, $token);
        if (!$booking) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Payment Not Found'])
            );
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/payment/show', [
                'pageTitle'      => 'Make Payment',
                'metaRobots'     => 'noindex,nofollow',
                'booking'        => $booking,
                'token'          => $token,
                'payfieldData'   => $payfieldData,
                'gatewayUrl'     => $this->payfast->gatewayUrl(),
                'bankingDetails' => setting('bank.details', ''),
                'bodyClass'      => 'page page--payment',
            ])
        );
    }

    public function initiate(string $ref, string $token): Response
    {
        [$booking, $payfieldData] = $this->resolveAndBuild($ref, $token);
        if (!$booking) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Payment Not Found'])
            );
        }

        // PayFast form is rendered on show.php and self-submits; this POST endpoint
        // validates the request before the redirect to PayFast.
        $paymentType = $this->request->str('payment_type'); // 'deposit' or 'full'
        $amountCents = $paymentType === 'deposit'
            ? (int) $booking['deposit_cents']
            : (int) $booking['outstanding_cents'];

        if ($amountCents <= 0) {
            flash('error', 'Nothing to pay.');
            return Response::make()->redirect(url("/pay/{$ref}/{$token}"));
        }

        // Create a payment record (status=pending) — PayFast ITN will mark it successful
        $payRef = Ref::generate('PAY');
        $this->db->execute(
            "INSERT INTO payments (public_ref,customer_id,amount_cents,currency,gateway,payment_method,status,created_at,updated_at)
             VALUES (?,?,?,?,?,?,'pending',NOW(),NOW())",
            [$payRef, (int) $booking['customer_id'], $amountCents, 'ZAR', 'payfast', 'card']
        );
        $paymentId = (int) $this->db->lastInsertId();

        $allocType = $paymentType === 'deposit' ? 'deposit' : 'balance';
        $this->db->execute(
            "INSERT INTO payment_allocations (payment_id,payable_type,payable_id,amount_cents,allocation_type,created_at)
             VALUES (?,'booking',?,?,?,NOW())",
            [$paymentId, (int) $booking['id'], $amountCents, $allocType]
        );

        // Build PayFast fields server-side (all amounts from DB, never browser)
        $nameParts = explode(' ', trim($booking['customer_name']), 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        $fields = $this->payfast->buildPaymentFields(
            amountRand:       number_format($amountCents / 100, 2, '.', ''),
            itemName:         'Booking ' . $booking['public_ref'],
            itemDescription:  ($booking['event_type'] ?? 'Private event') . ' — ' . $booking['event_date'],
            buyerFirstName:   $firstName,
            buyerLastName:    $lastName,
            buyerEmail:       $booking['customer_email'],
            returnUrl:        url('/pay/' . $ref . '/' . $token . '/return'),
            cancelUrl:        url('/pay/' . $ref . '/' . $token),
            notifyUrl:        url('/webhooks/payfast'),
            customStr1:       $payRef,
        );

        // Render a self-submitting form to redirect the customer to PayFast
        $html = $this->buildAutoSubmitForm($this->payfast->gatewayUrl(), $fields);
        return Response::make()->html($html);
    }

    public function return(string $ref, string $token): Response
    {
        [$booking] = $this->resolveAndBuild($ref, $token);
        if (!$booking) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Payment Not Found'])
            );
        }

        // PayFast redirects here after payment; we do NOT mark the order as paid here.
        // The ITN webhook is the authoritative source; we just show a pending message.
        flash('info', 'Thank you — your payment is being processed. You will receive a confirmation once it has cleared.');
        return Response::make()->redirect(url('/pay/' . $ref . '/' . $token));
    }

    private function resolveAndBuild(string $ref, string $token): array
    {
        // Token in URL is base64_encode(hex2bin($raw)); reverse to get hex, then hash to match stored hash
        $tokenHash = hash('sha256', bin2hex(base64_decode($token, true)));
        $booking   = $this->db->fetchOne(
            "SELECT pb.*, c.name AS customer_name, c.email AS customer_email
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
               JOIN payment_access_tokens pat ON pat.booking_id = pb.id
             WHERE pb.public_ref=?
               AND pat.token_hash=?
               AND (pat.expires_at IS NULL OR pat.expires_at > NOW())
               AND pb.deleted_at IS NULL",
            [$ref, $tokenHash]
        );

        if (!$booking) {
            return [null, null];
        }

        // Build both field sets (deposit + full) for display
        $payfieldData = null;
        if ((int) $booking['outstanding_cents'] > 0) {
            $nameParts = explode(' ', trim($booking['customer_name']), 2);
            $payfieldData = [
                'deposit' => $this->payfast->buildPaymentFields(
                    amountRand:      number_format($booking['deposit_cents'] / 100, 2, '.', ''),
                    itemName:        'Deposit — Booking ' . $booking['public_ref'],
                    itemDescription: ($booking['event_type'] ?? 'Event') . ' ' . $booking['event_date'],
                    buyerFirstName:  $nameParts[0],
                    buyerLastName:   $nameParts[1] ?? '',
                    buyerEmail:      $booking['customer_email'],
                    returnUrl:       url('/pay/' . $ref . '/' . $token . '/return'),
                    cancelUrl:       url('/pay/' . $ref . '/' . $token),
                    notifyUrl:       url('/webhooks/payfast'),
                    customStr1:      '', // populated at initiate time
                ),
            ];
        }

        return [$booking, $payfieldData];
    }

    private function buildAutoSubmitForm(string $url, array $fields): string
    {
        $inputs = '';
        foreach ($fields as $k => $v) {
            $inputs .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $v, ENT_QUOTES) . '">' . "\n";
        }
        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>Redirecting to PayFast…</title></head>
<body onload="document.getElementById('pf').submit()">
<p>Redirecting to PayFast payment gateway…</p>
<form id="pf" method="POST" action="{$url}">
{$inputs}
<button type="submit">Continue to payment</button>
</form>
</body>
</html>
HTML;
    }
}
