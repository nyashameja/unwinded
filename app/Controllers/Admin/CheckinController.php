<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class CheckinController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(string $id): Response
    {
        $event = $this->findEvent((int) $id);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        $stats = $this->db->fetchOne(
            "SELECT
                SUM(tt.qty_sold)                                       AS total_sold,
                (SELECT COUNT(*) FROM tickets t
                 WHERE t.event_id=? AND t.status='used')               AS checked_in,
                (SELECT COUNT(*) FROM tickets t
                 WHERE t.event_id=? AND t.status='valid')              AS remaining
             FROM event_ticket_types tt
             WHERE tt.event_id=? AND tt.deleted_at IS NULL",
            [(int) $id, (int) $id, (int) $id]
        );

        $recentCheckins = $this->db->fetchAll(
            "SELECT tc.*, t.ticket_uid, t.attendee_name, tt.name AS ticket_type_name
               FROM ticket_checkins tc
               JOIN tickets t ON t.id = tc.ticket_id
               LEFT JOIN event_ticket_types tt ON tt.id = t.ticket_type_id
             WHERE t.event_id=?
             ORDER BY tc.checked_in_at DESC
             LIMIT 20",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/checkin/index', [
                'pageTitle'      => 'Check-in — ' . $event['title'],
                'event'          => $event,
                'stats'          => $stats,
                'recentCheckins' => $recentCheckins,
            ])
        );
    }

    public function checkin(string $id): Response
    {
        $event = $this->findEvent((int) $id);
        if (!$event) {
            return Response::make()->status(404)->json(['error' => 'Event not found']);
        }

        $ticketUid = trim($this->request->str('ticket_uid'));
        $method    = $this->request->str('method') === 'manual' ? 'manual' : 'qr_scan';
        $override  = (bool) $this->request->str('override');
        $reason    = trim($this->request->str('override_reason'));
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!$ticketUid) {
            return Response::make()->json(['success' => false, 'message' => 'No ticket UID provided.']);
        }

        $ticket = $this->db->fetchOne(
            "SELECT t.*, tt.name AS ticket_type_name
               FROM tickets t
               LEFT JOIN event_ticket_types tt ON tt.id = t.ticket_type_id
             WHERE t.ticket_uid=? AND t.event_id=?",
            [$ticketUid, (int) $id]
        );

        if (!$ticket) {
            return Response::make()->json(['success' => false, 'message' => 'Ticket not found for this event.']);
        }

        if ($ticket['status'] === 'used') {
            $existing = $this->db->fetchOne(
                "SELECT * FROM ticket_checkins WHERE ticket_id=?",
                [(int) $ticket['id']]
            );
            $when = $existing ? date('d M Y H:i', strtotime($existing['checked_in_at'])) : 'earlier';
            return Response::make()->json([
                'success' => false,
                'already_used' => true,
                'message' => "Ticket already checked in at {$when}.",
            ]);
        }

        if ($ticket['status'] === 'cancelled') {
            return Response::make()->json(['success' => false, 'message' => 'Ticket is cancelled.']);
        }

        if ($ticket['status'] === 'refunded') {
            return Response::make()->json(['success' => false, 'message' => 'Ticket has been refunded.']);
        }

        // Valid ticket — check in
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO ticket_checkins
                    (ticket_id, checked_in_by, checked_in_at, method, override_reason, override_by, ip_address)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?)",
                [
                    (int) $ticket['id'],
                    $_SESSION['user']['name'] ?? 'Admin',
                    $method,
                    $override ? $reason : null,
                    $override ? ($_SESSION['user']['name'] ?? 'Admin') : null,
                    $ip,
                ]
            );
            $this->db->execute(
                "UPDATE tickets SET status='used', updated_at=NOW() WHERE id=?",
                [(int) $ticket['id']]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            return Response::make()->json(['success' => false, 'message' => 'Database error during check-in.']);
        }

        $this->activityLogger->log('ticket.checked_in', 'ticket', (string) $ticket['id']);

        return Response::make()->json([
            'success'          => true,
            'message'          => 'Check-in successful.',
            'attendee_name'    => $ticket['attendee_name'],
            'ticket_type_name' => $ticket['ticket_type_name'],
        ]);
    }

    private function findEvent(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM public_events WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
