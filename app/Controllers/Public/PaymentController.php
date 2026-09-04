<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * Payment initiation — full implementation is Phase 8.
 * Token-protected access; no login required.
 */
class PaymentController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function show(string $ref, string $token): Response
    {
        $booking = $this->resolveBooking($ref, $token);
        if (!$booking) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Payment Not Found'])
            );
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/payment/show', [
                'pageTitle'  => 'Make Payment',
                'metaRobots' => 'noindex,nofollow',
                'booking'    => $booking,
                'token'      => $token,
                'bodyClass'  => 'page page--payment',
            ])
        );
    }

    public function initiate(string $ref, string $token): Response
    {
        flash('info', 'Online payments will be enabled soon. Please contact us to arrange payment.');
        return Response::make()->redirect(url("/pay/{$ref}/{$token}"));
    }

    private function resolveBooking(string $ref, string $token): ?array
    {
        $tokenHash = hash('sha256', base64_decode($token));
        return $this->db->fetchOne(
            "SELECT pb.*, pat.token_hash
             FROM private_bookings pb
             JOIN payment_access_tokens pat ON pat.booking_id = pb.id
             WHERE pb.public_ref = ?
               AND pat.token_hash = ?
               AND (pat.expires_at IS NULL OR pat.expires_at > NOW())",
            [$ref, $tokenHash]
        ) ?: null;
    }
}
