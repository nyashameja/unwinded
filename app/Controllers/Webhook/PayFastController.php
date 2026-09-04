<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Webhook;

use Unwinded\Core\Database;
use Unwinded\Core\Logger;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Services\PayFastService;
use Unwinded\Services\TicketIssuer;
use Unwinded\Support\Ref;

/**
 * Handles PayFast Instant Transaction Notifications (ITN).
 * CSRF is exempt (registered under /webhooks prefix).
 * All verification is done server-side; the browser return URL is never trusted.
 */
class PayFastController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private PayFastService $payfast,
        private TicketIssuer   $ticketIssuer,
        private Logger         $logger,
    ) {}

    public function itn(): Response
    {
        $rawPost   = file_get_contents('php://input') ?: '';
        $postData  = $this->request->all();
        $remoteIp  = $_SERVER['REMOTE_ADDR'] ?? '';

        // 1. Record the raw webhook immediately (idempotent via UNIQUE KEY)
        $pfPaymentId = (string) ($postData['pf_payment_id'] ?? '');
        if ($pfPaymentId === '') {
            return Response::make()->status(400)->body('Missing pf_payment_id');
        }

        try {
            $this->db->execute(
                "INSERT INTO payment_webhooks (provider, provider_event_id, payload, status, received_at)
                 VALUES ('payfast', ?, ?, 'received', NOW())",
                [$pfPaymentId, $rawPost]
            );
        } catch (\Throwable $e) {
            // UNIQUE constraint = duplicate ITN; return 200 to stop retries
            $this->logger->info('PayFast ITN duplicate', ['pf_payment_id' => $pfPaymentId]);
            return Response::make()->status(200)->body('DUPLICATE');
        }

        $webhookId = (int) $this->db->lastInsertId();

        // 2. Verify the ITN
        if (!$this->payfast->verifyItn($postData, $rawPost, $remoteIp)) {
            $this->markWebhook($webhookId, 'failed', 'Verification failed');
            $this->logger->warning('PayFast ITN verification failed', ['ip' => $remoteIp, 'pf_payment_id' => $pfPaymentId]);
            return Response::make()->status(200)->body('VERIFICATION_FAILED');
        }

        $paymentStatus = strtoupper($postData['payment_status'] ?? '');
        $ourRef        = $postData['custom_str1'] ?? '';    // our payment public_ref
        $amountGross   = $postData['amount_gross'] ?? '0';
        $amountCents   = (int) round((float) $amountGross * 100);

        if ($paymentStatus !== 'COMPLETE') {
            $this->markWebhook($webhookId, 'processed', null, "Non-complete status: {$paymentStatus}");
            return Response::make()->status(200)->body('NON_COMPLETE');
        }

        try {
            $this->db->beginTransaction();

            // 3. Find the payment record by our custom_str1 reference
            $payment = $this->db->fetchOne(
                "SELECT * FROM payments WHERE public_ref=?",
                [$ourRef]
            );

            if (!$payment) {
                $this->db->rollback();
                $this->markWebhook($webhookId, 'failed', "Payment not found: {$ourRef}");
                return Response::make()->status(200)->body('PAYMENT_NOT_FOUND');
            }

            // Idempotency: already successful
            if ($payment['status'] === 'successful') {
                $this->db->rollback();
                $this->markWebhook($webhookId, 'duplicate', null);
                return Response::make()->status(200)->body('ALREADY_PROCESSED');
            }

            // 4. Verify amount matches within 1 cent tolerance
            if (abs($amountCents - (int) $payment['amount_cents']) > 1) {
                $this->db->rollback();
                $this->markWebhook($webhookId, 'failed', "Amount mismatch: expected {$payment['amount_cents']}, got {$amountCents}");
                $this->logger->error('PayFast amount mismatch', ['ref' => $ourRef, 'expected' => $payment['amount_cents'], 'received' => $amountCents]);
                return Response::make()->status(200)->body('AMOUNT_MISMATCH');
            }

            // 5. Mark payment successful
            $this->db->execute(
                "UPDATE payments SET status='successful', gateway_ref=?, updated_at=NOW() WHERE id=?",
                [$pfPaymentId, (int) $payment['id']]
            );

            $this->db->execute(
                "INSERT INTO payment_logs (payment_id, event, data, created_at)
                 VALUES (?, 'payfast.itn', ?, NOW())",
                [(int) $payment['id'], json_encode($postData)]
            );

            // 6. Update linked allocations / payables
            $allocations = $this->db->fetchAll(
                "SELECT * FROM payment_allocations WHERE payment_id=?",
                [(int) $payment['id']]
            );

            foreach ($allocations as $alloc) {
                if ($alloc['payable_type'] === 'booking') {
                    $this->fulfillBookingPayment((int) $alloc['payable_id'], (int) $alloc['amount_cents']);
                } elseif ($alloc['payable_type'] === 'order') {
                    $this->fulfillOrderPayment((int) $alloc['payable_id']);
                }
            }

            // Link webhook to payment
            $this->db->execute(
                "UPDATE payment_webhooks SET payment_id=?, status='processed', processed_at=NOW() WHERE id=?",
                [(int) $payment['id'], $webhookId]
            );

            $this->db->commit();

            // 7. Issue tickets outside transaction (idempotent)
            foreach ($allocations as $alloc) {
                if ($alloc['payable_type'] === 'order') {
                    $this->ticketIssuer->issueForOrder((int) $alloc['payable_id']);
                }
            }

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->markWebhook($webhookId, 'failed', $e->getMessage());
            $this->logger->error('PayFast ITN processing error', ['error' => $e->getMessage(), 'ref' => $ourRef]);
            // Return 200 to prevent PayFast retrying a fundamentally broken request
            return Response::make()->status(200)->body('ERROR');
        }

        return Response::make()->status(200)->body('OK');
    }

    private function fulfillBookingPayment(int $bookingId, int $amountCents): void
    {
        $this->db->execute(
            "UPDATE private_bookings
             SET amount_paid_cents = amount_paid_cents + ?,
                 outstanding_cents = GREATEST(0, total_cents - (amount_paid_cents + ?)),
                 payment_status = CASE
                     WHEN (amount_paid_cents + ?) >= total_cents THEN 'paid'
                     WHEN (amount_paid_cents + ?) >= deposit_cents THEN 'partially_paid'
                     ELSE 'partially_paid'
                 END,
                 booking_status = CASE
                     WHEN (amount_paid_cents + ?) >= deposit_cents AND booking_status = 'awaiting_deposit' THEN 'confirmed'
                     ELSE booking_status
                 END,
                 updated_at = NOW()
             WHERE id=?",
            [$amountCents, $amountCents, $amountCents, $amountCents, $amountCents, $bookingId]
        );
    }

    private function fulfillOrderPayment(int $orderId): void
    {
        $this->db->execute(
            "UPDATE ticket_orders SET status='paid', paid_at=NOW(), updated_at=NOW() WHERE id=? AND status='pending'",
            [$orderId]
        );
    }

    private function markWebhook(int $id, string $status, ?string $error = null, ?string $note = null): void
    {
        $this->db->execute(
            "UPDATE payment_webhooks SET status=?, error=?, processed_at=NOW() WHERE id=?",
            [$status, $error ?? $note, $id]
        );
    }
}
