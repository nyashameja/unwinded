<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Support\Ref;

class PaymentController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        $allowed = ['created','pending','successful','failed','cancelled','expired','partially_refunded','refunded'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE p.status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];

        $payments = $this->db->fetchAll(
            "SELECT p.*, c.name AS customer_name
               FROM payments p
               LEFT JOIN customers c ON c.id = p.customer_id
               $where
             ORDER BY p.created_at DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/payments/index', [
                'pageTitle' => 'Payments',
                'payments'  => $payments,
                'status'    => $status,
                'statuses'  => $allowed,
            ])
        );
    }

    public function show(string $id): Response
    {
        $payment = $this->find((int) $id);
        if (!$payment) {
            flash('error', 'Payment not found.');
            return Response::make()->redirect(url('/admin/payments'));
        }

        $allocations = $this->db->fetchAll(
            "SELECT * FROM payment_allocations WHERE payment_id=? ORDER BY created_at",
            [(int) $id]
        );
        $refunds = $this->db->fetchAll(
            "SELECT * FROM refunds WHERE payment_id=? ORDER BY requested_at DESC",
            [(int) $id]
        );
        $logs = $this->db->fetchAll(
            "SELECT * FROM payment_logs WHERE payment_id=? ORDER BY created_at DESC LIMIT 50",
            [(int) $id]
        );
        $eftProof = $this->db->fetchOne(
            "SELECT ep.*, m.thumb_url, m.filename FROM eft_proofs ep LEFT JOIN media m ON m.id=ep.media_id WHERE ep.payment_id=?",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/payments/show', [
                'pageTitle'   => 'Payment ' . $payment['public_ref'],
                'payment'     => $payment,
                'allocations' => $allocations,
                'refunds'     => $refunds,
                'logs'        => $logs,
                'eftProof'    => $eftProof,
            ])
        );
    }

    public function recordEft(): Response
    {
        $bookingId   = (int) $this->request->str('booking_id');
        $amountRand  = $this->request->str('amount_rand');
        $bankRef     = trim($this->request->str('bank_ref'));
        $notes       = trim($this->request->str('notes'));
        $allocType   = $this->request->str('allocation_type');
        $allowed     = ['deposit','balance','full','adjustment'];

        if (!in_array($allocType, $allowed, true)) {
            $allocType = 'full';
        }

        $booking = $this->db->fetchOne(
            "SELECT * FROM private_bookings WHERE id=? AND deleted_at IS NULL",
            [$bookingId]
        );
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/payments'));
        }

        $amountCents = (int) round((float) str_replace([' ',','], '', $amountRand) * 100);
        if ($amountCents <= 0) {
            flash('error', 'Amount must be greater than zero.');
            return Response::make()->redirect(url('/admin/bookings/' . $bookingId));
        }

        $customerId = $booking['customer_id'];
        $ref        = Ref::generate('PAY');

        $this->db->beginTransaction();
        try {
                $this->db->execute(
                "INSERT INTO payments (public_ref,customer_id,amount_cents,currency,gateway,payment_method,status,notes,processed_by,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                [$ref, $customerId, $amountCents, 'ZAR', 'manual', 'eft', 'successful', $notes, $_SESSION['user']['id'] ?? null]
            );

            $paymentId = (int) $this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO payment_allocations (payment_id,payable_type,payable_id,amount_cents,allocation_type,notes,created_at)
                 VALUES (?,?,?,?,?,?,NOW())",
                [$paymentId, 'booking', $bookingId, $amountCents, $allocType, $notes]
            );

            if ($bankRef) {
                $this->db->execute(
                    "INSERT INTO eft_proofs (payment_id,bank_ref,notes,uploaded_at) VALUES (?,?,?,NOW())",
                    [$paymentId, substr($bankRef, 0, 200), $notes]
                );
            }

            // Update booking amounts paid / outstanding
            $this->db->execute(
                "UPDATE private_bookings
                 SET amount_paid_cents = amount_paid_cents + ?,
                     outstanding_cents = GREATEST(0, total_cents - (amount_paid_cents + ?)),
                     payment_status = CASE
                         WHEN (amount_paid_cents + ?) >= total_cents THEN 'paid'
                         WHEN (amount_paid_cents + ?) >= deposit_cents THEN 'partially_paid'
                         ELSE 'partially_paid'
                     END,
                     updated_at = NOW()
                 WHERE id=?",
                [$amountCents, $amountCents, $amountCents, $amountCents, $bookingId]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('payment.eft_recorded', 'payment', (string) $paymentId, ['booking_id' => $bookingId]);
        flash('success', 'EFT payment ' . $ref . ' recorded.');
        return Response::make()->redirect(url('/admin/bookings/' . $bookingId));
    }

    public function refund(string $id): Response
    {
        $payment = $this->find((int) $id);
        if (!$payment || $payment['status'] !== 'successful') {
            flash('error', 'Payment not found or not refundable.');
            return Response::make()->redirect(url('/admin/payments'));
        }

        $amountCents = (int) round((float) $this->request->str('amount_rand') * 100);
        $reason      = trim($this->request->str('reason'));

        if ($amountCents <= 0 || $amountCents > $payment['amount_cents']) {
            flash('error', 'Invalid refund amount.');
            return Response::make()->redirect(url('/admin/payments/' . $id));
        }

        $ref = Ref::generate('REF');

        $this->db->execute(
            "INSERT INTO refunds (public_ref,payment_id,amount_cents,reason,status,requested_by_name,requested_at,due_by)
             VALUES (?,?,?,?,'requested',?,NOW(),DATE_ADD(NOW(), INTERVAL 10 DAY))",
            [$ref, (int) $id, $amountCents, $reason, $_SESSION['user']['name'] ?? 'Admin']
        );

        $this->db->execute(
            "UPDATE payments SET status='partially_refunded', updated_at=NOW() WHERE id=?",
            [(int) $id]
        );

        $this->activityLogger->log('payment.refund_requested', 'payment', $id, ['amount_cents' => $amountCents]);
        flash('success', 'Refund ' . $ref . ' requested. Due within 10 business days.');
        return Response::make()->redirect(url('/admin/payments/' . $id));
    }

    private function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT p.*, c.name AS customer_name, c.email AS customer_email
               FROM payments p
               LEFT JOIN customers c ON c.id = p.customer_id
              WHERE p.id = ?",
            [$id]
        ) ?: null;
    }
}
