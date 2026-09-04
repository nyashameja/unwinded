<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;

class OrderController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
        private MailService    $mailService,
    ) {}

    public function index(): Response
    {
        $status   = $this->request->str('status');
        $eventId  = (int) $this->request->str('event_id');
        $allowed  = ['pending','paid','cancelled','refunded','requires_attention','expired'];

        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $conditions = ['1=1'];
        $params     = [];

        if ($status) {
            $conditions[] = 'o.status = ?';
            $params[]     = $status;
        }
        if ($eventId) {
            $conditions[] = 'o.event_id = ?';
            $params[]     = $eventId;
        }

        $where  = implode(' AND ', $conditions);
        $orders = $this->db->fetchAll(
            "SELECT o.*, e.title AS event_title, e.event_date
               FROM ticket_orders o
               LEFT JOIN public_events e ON e.id = o.event_id
             WHERE $where
             ORDER BY o.created_at DESC
             LIMIT 200",
            $params
        );

        $events = $this->db->fetchAll(
            "SELECT id, title, event_date FROM public_events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 100"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/orders/index', [
                'pageTitle' => 'Ticket Orders',
                'orders'    => $orders,
                'status'    => $status,
                'eventId'   => $eventId,
                'statuses'  => $allowed,
                'events'    => $events,
            ])
        );
    }

    public function show(string $id): Response
    {
        $order = $this->find((int) $id);
        if (!$order) {
            flash('error', 'Order not found.');
            return Response::make()->redirect(url('/admin/orders'));
        }

        $items = $this->db->fetchAll(
            "SELECT oi.*, tt.name AS ticket_type_name
               FROM order_items oi
               LEFT JOIN event_ticket_types tt ON tt.id = oi.ticket_type_id
             WHERE oi.order_id=?",
            [(int) $id]
        );

        $tickets = $this->db->fetchAll(
            "SELECT * FROM tickets WHERE order_id=? ORDER BY id",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/orders/show', [
                'pageTitle' => 'Order ' . $order['public_ref'],
                'order'     => $order,
                'items'     => $items,
                'tickets'   => $tickets,
            ])
        );
    }

    public function resend(string $id): Response
    {
        $order = $this->find((int) $id);
        if (!$order || $order['status'] !== 'paid') {
            flash('error', 'Order not found or not paid.');
            return Response::make()->redirect(url('/admin/orders'));
        }

        $tickets = $this->db->fetchAll(
            "SELECT t.*, tt.name AS ticket_type_name
               FROM tickets t
               LEFT JOIN event_ticket_types tt ON tt.id = t.ticket_type_id
             WHERE t.order_id=? AND t.status = 'valid'",
            [(int) $id]
        );

        $event = $this->db->fetchOne(
            "SELECT * FROM public_events WHERE id=?",
            [$order['event_id']]
        );

        try {
            $this->mailService->send(
                $order['purchaser_email'],
                $order['purchaser_name'],
                'Your tickets for ' . ($event['title'] ?? 'the event'),
                $this->buildTicketEmailBody($order, $tickets, $event)
            );
            $this->activityLogger->log('order.tickets_resent', 'order', $id);
            flash('success', 'Tickets resent to ' . $order['purchaser_email']);
        } catch (\Throwable $e) {
            flash('error', 'Failed to send email: ' . $e->getMessage());
        }

        return Response::make()->redirect(url('/admin/orders/' . $id));
    }

    public function cancel(string $id): Response
    {
        $order = $this->find((int) $id);
        if (!$order || !in_array($order['status'], ['pending','paid'], true)) {
            flash('error', 'Order cannot be cancelled.');
            return Response::make()->redirect(url('/admin/orders'));
        }

        $this->db->beginTransaction();
        try {
            // Release reservations and adjust qty counts
            $reservations = $this->db->fetchAll(
                "SELECT * FROM ticket_reservations WHERE order_id=? AND released_at IS NULL",
                [(int) $id]
            );

            foreach ($reservations as $res) {
                $this->db->execute(
                    "UPDATE event_ticket_types
                     SET qty_reserved = GREATEST(0, qty_reserved - ?),
                         updated_at = NOW()
                     WHERE id = ?",
                    [(int) $res['quantity'], (int) $res['ticket_type_id']]
                );
                $this->db->execute(
                    "UPDATE ticket_reservations SET released_at=NOW() WHERE id=?",
                    [(int) $res['id']]
                );
            }

            // If paid, reverse sold counts for each order item
            if ($order['status'] === 'paid') {
                $items = $this->db->fetchAll(
                    "SELECT * FROM order_items WHERE order_id=?",
                    [(int) $id]
                );
                foreach ($items as $item) {
                    $this->db->execute(
                        "UPDATE event_ticket_types
                         SET qty_sold = GREATEST(0, qty_sold - ?), updated_at=NOW()
                         WHERE id=?",
                        [(int) $item['quantity'], (int) $item['ticket_type_id']]
                    );
                }
            }

            // Cancel tickets
            $this->db->execute(
                "UPDATE tickets SET status='cancelled', updated_at=NOW() WHERE order_id=?",
                [(int) $id]
            );

            // Cancel order
            $this->db->execute(
                "UPDATE ticket_orders SET status='cancelled', updated_at=NOW() WHERE id=?",
                [(int) $id]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('order.cancelled', 'order', $id);
        flash('success', 'Order cancelled and quantities released.');
        return Response::make()->redirect(url('/admin/orders/' . $id));
    }

    private function buildTicketEmailBody(array $order, array $tickets, ?array $event): string
    {
        $lines = ['<p>Dear ' . htmlspecialchars($order['purchaser_name']) . ',</p>'];
        $lines[] = '<p>Your tickets for <strong>' . htmlspecialchars($event['title'] ?? 'the event') . '</strong> are below.</p>';
        $lines[] = '<ul>';
        foreach ($tickets as $t) {
            $ticketUrl = url('/tickets/' . urlencode($t['ticket_uid']));
            $lines[] = '<li>' . htmlspecialchars($t['ticket_type_name'] ?? 'Ticket') . ' — <a href="' . $ticketUrl . '">' . $ticketUrl . '</a></li>';
        }
        $lines[] = '</ul>';
        $lines[] = '<p>Order ref: ' . htmlspecialchars($order['public_ref']) . '</p>';
        return implode("\n", $lines);
    }

    private function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT o.*, e.title AS event_title, e.event_date, e.venue_name
               FROM ticket_orders o
               LEFT JOIN public_events e ON e.id = o.event_id
             WHERE o.id=?",
            [$id]
        ) ?: null;
    }
}
