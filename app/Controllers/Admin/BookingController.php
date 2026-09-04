<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;
use Unwinded\Services\MediaUploadService;
use Unwinded\Support\Token;

class BookingController
{
    private const BOOKING_STATUSES = [
        'provisional','awaiting_deposit','confirmed','planning','ready','completed','cancelled','refunded'
    ];

    public function __construct(
        private Database           $db,
        private Request            $request,
        private View               $view,
        private ActivityLogger     $activityLogger,
        private MediaUploadService $mediaUpload,
        private MailService        $mail,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        if (!in_array($status, self::BOOKING_STATUSES, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE pb.booking_status = ?" : "WHERE pb.deleted_at IS NULL";
        $params = $status ? [$status] : [];

        $bookings = $this->db->fetchAll(
            "SELECT pb.*, c.name AS customer_name, c.email AS customer_email
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
               $where
             ORDER BY pb.event_date ASC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/bookings/index', [
                'pageTitle' => 'Private Bookings',
                'bookings'  => $bookings,
                'status'    => $status,
                'statuses'  => self::BOOKING_STATUSES,
            ])
        );
    }

    public function show(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/bookings'));
        }

        $items     = $this->db->fetchAll("SELECT * FROM booking_items WHERE booking_id=? ORDER BY sort_order", [(int) $id]);
        $notes     = $this->db->fetchAll("SELECT * FROM booking_notes WHERE booking_id=? ORDER BY created_at DESC", [(int) $id]);
        $history   = $this->db->fetchAll("SELECT * FROM booking_status_history WHERE booking_id=? ORDER BY created_at DESC", [(int) $id]);
        $documents = $this->db->fetchAll(
            "SELECT bd.*, m.filename AS media_filename, m.thumb_url
               FROM booking_documents bd
               LEFT JOIN media m ON m.id = bd.media_id
              WHERE bd.booking_id=?
             ORDER BY bd.created_at DESC",
            [(int) $id]
        );
        $payments  = $this->db->fetchAll(
            "SELECT p.*, pa.allocation_type, pa.amount_cents AS allocated_cents
               FROM payment_allocations pa
               JOIN payments p ON p.id = pa.payment_id
              WHERE pa.payable_type='booking' AND pa.payable_id=?
             ORDER BY p.created_at DESC",
            [(int) $id]
        );
        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/bookings/show', [
                'pageTitle' => 'Booking ' . $booking['public_ref'],
                'booking'   => $booking,
                'items'     => $items,
                'notes'     => $notes,
                'history'   => $history,
                'documents' => $documents,
                'payments'  => $payments,
                'users'     => $users,
                'statuses'  => self::BOOKING_STATUSES,
            ])
        );
    }

    public function edit(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/bookings'));
        }

        $items    = $this->db->fetchAll("SELECT * FROM booking_items WHERE booking_id=? ORDER BY sort_order", [(int) $id]);
        $packages = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
        $users    = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/bookings/edit', [
                'pageTitle' => 'Edit Booking ' . $booking['public_ref'],
                'booking'   => $booking,
                'items'     => $items,
                'packages'  => $packages,
                'users'     => $users,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function update(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/bookings'));
        }

        $eventDate = $this->request->str('event_date');
        $errors    = [];

        if (!$eventDate || !strtotime($eventDate)) {
            $errors['event_date'] = 'A valid event date is required.';
        }

        if ($errors) {
            $items    = $this->db->fetchAll("SELECT * FROM booking_items WHERE booking_id=? ORDER BY sort_order", [(int) $id]);
            $packages = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
            $users    = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/bookings/edit', [
                    'pageTitle' => 'Edit Booking ' . $booking['public_ref'],
                    'booking'   => $booking,
                    'items'     => $items,
                    'packages'  => $packages,
                    'users'     => $users,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        $descs  = (array) ($this->request->all()['line_desc']  ?? []);
        $prices = (array) ($this->request->all()['line_price'] ?? []);
        $qtys   = (array) ($this->request->all()['line_qty']   ?? []);

        $items    = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            $desc = trim((string) $desc);
            if ($desc === '') {
                continue;
            }
            $priceCents = (int) round((float) ($prices[$i] ?? 0) * 100);
            $qty        = max(0.01, (float) ($qtys[$i] ?? 1));
            $lineCents  = (int) round($priceCents * $qty);
            $items[]    = ['description' => substr($desc, 0, 500), 'unit_price_cents' => $priceCents,
                           'quantity' => $qty, 'line_total_cents' => $lineCents];
            $subtotal  += $lineCents;
        }

        $depositCents     = (int) round((float) $this->request->str('deposit_cents') * 100);
        $amountPaidCents  = $booking['amount_paid_cents'];
        $outstandingCents = max(0, $subtotal - $amountPaidCents);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE private_bookings SET
                    event_type=?,event_date=?,event_date_utc=?,start_time=?,end_time=?,
                    guest_count=?,venue_name=?,venue_address=?,package_id=?,
                    subtotal_cents=?,total_cents=?,deposit_cents=?,outstanding_cents=?,
                    balance_due_date=?,creative_direction=?,internal_notes=?,assigned_to=?,
                    deposit_soft_deadline=?,deposit_hard_deadline=?,updated_at=NOW()
                 WHERE id=?",
                [
                    substr(trim($this->request->str('event_type')), 0, 100),
                    $eventDate,
                    $eventDate . ' ' . ($this->request->str('start_time') ?: '00:00:00'),
                    $this->request->str('start_time') ?: null,
                    $this->request->str('end_time') ?: null,
                    (int) $this->request->str('guest_count') ?: 1,
                    substr(trim($this->request->str('venue_name')), 0, 300),
                    trim($this->request->str('venue_address')),
                    (int) $this->request->str('package_id') ?: null,
                    $subtotal,
                    $subtotal,
                    $depositCents,
                    $outstandingCents,
                    $this->request->str('balance_due_date') ?: null,
                    trim($this->request->str('creative_direction')),
                    trim($this->request->str('internal_notes')),
                    (int) $this->request->str('assigned_to') ?: null,
                    $this->request->str('deposit_soft_deadline') ?: null,
                    $this->request->str('deposit_hard_deadline') ?: null,
                    (int) $id,
                ]
            );

            $this->db->execute("DELETE FROM booking_items WHERE booking_id=?", [(int) $id]);
            foreach ($items as $i => $item) {
                $this->db->execute(
                    "INSERT INTO booking_items (booking_id,sort_order,type,description,unit_price_cents,quantity,line_total_cents)
                     VALUES (?,?,'custom',?,?,?,?)",
                    [(int) $id, $i + 1, $item['description'], $item['unit_price_cents'], $item['quantity'], $item['line_total_cents']]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('booking.updated', 'booking', $id);
        flash('success', 'Booking updated.');
        return Response::make()->redirect(url('/admin/bookings/' . $id));
    }

    public function status(string $id): Response
    {
        $booking   = $this->findWithCustomer((int) $id);
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/bookings'));
        }

        $newStatus = $this->request->str('booking_status');
        if (!in_array($newStatus, self::BOOKING_STATUSES, true)) {
            flash('error', 'Invalid status.');
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        $reason = trim($this->request->str('reason'));

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE private_bookings SET booking_status=?, cancellation_reason=IF(?='cancelled',?,cancellation_reason), updated_at=NOW() WHERE id=?",
                [$newStatus, $newStatus, $reason ?: null, (int) $id]
            );
            $this->db->execute(
                "INSERT INTO booking_status_history (booking_id,from_status,to_status,changed_by,changed_name,reason,created_at)
                 VALUES (?,?,?,?,?,?,NOW())",
                [(int) $id, $booking['booking_status'], $newStatus,
                 $_SESSION['user']['id'] ?? null, $_SESSION['user']['name'] ?? null, $reason ?: null]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('booking.status_changed', 'booking', $id, ['to' => $newStatus]);
        flash('success', 'Booking status updated to ' . $newStatus . '.');
        return Response::make()->redirect(url('/admin/bookings/' . $id));
    }

    public function addNote(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            return Response::make()->status(404)->body('Not found');
        }

        $note = trim($this->request->str('note'));
        if (strlen($note) < 2) {
            flash('error', 'Note cannot be blank.');
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        $this->db->execute(
            "INSERT INTO booking_notes (booking_id,user_id,user_name,note,created_at)
             VALUES (?,?,?,?,NOW())",
            [(int) $id, $_SESSION['user']['id'] ?? null, $_SESSION['user']['name'] ?? 'Admin', $note]
        );

        $this->activityLogger->log('booking.note_added', 'booking', $id);
        flash('success', 'Note added.');
        return Response::make()->redirect(url('/admin/bookings/' . $id));
    }

    public function upload(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            return Response::make()->status(404)->body('Not found');
        }

        $file = $_FILES['document'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Upload failed or no file selected.');
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        try {
            $media = $this->mediaUpload->store($file, uploadedBy: $_SESSION['user']['id'] ?? null);
        } catch (\Throwable $e) {
            flash('error', 'Upload failed: ' . $e->getMessage());
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        $docType = $this->request->str('document_type');
        $allowed = ['contract','proof_of_payment','inspiration','other'];
        if (!in_array($docType, $allowed, true)) {
            $docType = 'other';
        }

        $this->db->execute(
            "INSERT INTO booking_documents (booking_id,media_id,label,document_type,notes,uploaded_by,created_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [
                (int) $id,
                $media['id'],
                substr(trim($this->request->str('label') ?: $file['name']), 0, 200),
                $docType,
                trim($this->request->str('doc_notes')),
                $_SESSION['user']['id'] ?? null,
            ]
        );

        $this->activityLogger->log('booking.document_uploaded', 'booking', $id);
        flash('success', 'Document uploaded.');
        return Response::make()->redirect(url('/admin/bookings/' . $id));
    }

    public function sendPaymentLink(string $id): Response
    {
        $booking = $this->findWithCustomer((int) $id);
        if (!$booking) {
            flash('error', 'Booking not found.');
            return Response::make()->redirect(url('/admin/bookings'));
        }

        if ((int) $booking['outstanding_cents'] <= 0) {
            flash('info', 'This booking is already paid in full.');
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        // Generate a secure token; store only the SHA-256 hash
        $token       = Token::generate();  // ['raw' => hex, 'hash' => sha256]
        $tokenHash   = $token['hash'];
        $tokenBase64 = base64_encode(hex2bin($token['raw']));

        // Expire any previous tokens for this booking so only one link works at a time
        $this->db->execute(
            "UPDATE payment_access_tokens SET expires_at=NOW() WHERE booking_id=? AND (expires_at IS NULL OR expires_at > NOW())",
            [(int) $id]
        );

        $this->db->execute(
            "INSERT INTO payment_access_tokens (token_hash, booking_id, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())",
            [$tokenHash, (int) $id]
        );

        $paymentUrl = url('/pay/' . $booking['public_ref'] . '/' . $tokenBase64);

        $siteName = setting('site.name', 'Unwinded');
        $body = '<p>Dear ' . htmlspecialchars($booking['customer_name']) . ',</p>'
            . '<p>Your booking is confirmed — please use the secure link below to pay your '
            . ($booking['amount_paid_cents'] > 0 ? 'outstanding balance' : 'deposit') . '.</p>'
            . '<p><a href="' . htmlspecialchars($paymentUrl) . '" style="display:inline-block;background:#1a1a1a;color:#fff;padding:.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:600;">Pay now</a></p>'
            . '<p style="font-size:.875rem;color:#666;">Or copy this link into your browser:<br>'
            . '<a href="' . htmlspecialchars($paymentUrl) . '">' . htmlspecialchars($paymentUrl) . '</a></p>'
            . '<p style="font-size:.875rem;color:#666;">This link is valid for 30 days. If you have any questions, reply to this email or contact us.</p>'
            . '<p>Thank you,<br>' . htmlspecialchars($siteName) . ' team</p>';

        try {
            $this->mail->send(
                $booking['customer_email'],
                $booking['customer_name'],
                'Your payment link — Booking ' . $booking['public_ref'],
                $body,
            );
        } catch (\Throwable $e) {
            flash('error', 'Token created but email failed to send: ' . $e->getMessage());
            return Response::make()->redirect(url('/admin/bookings/' . $id));
        }

        $this->activityLogger->log('booking.payment_link_sent', 'booking', $id);
        flash('success', 'Payment link sent to ' . $booking['customer_email'] . '.');
        return Response::make()->redirect(url('/admin/bookings/' . $id));
    }

    private function findWithCustomer(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT pb.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
              WHERE pb.id = ? AND pb.deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
