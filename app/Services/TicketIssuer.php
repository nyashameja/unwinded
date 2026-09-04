<?php

declare(strict_types=1);

namespace Unwinded\Services;

use Unwinded\Core\Database;
use Unwinded\Support\Ref;

class TicketIssuer
{
    public function __construct(
        private Database    $db,
        private MailService $mailService,
    ) {}

    /**
     * Issue tickets for a paid ticket_order.
     * Idempotent: if tickets already exist for this order, does nothing.
     * Returns the number of tickets issued.
     */
    public function issueForOrder(int $orderId): int
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, e.title AS event_title, e.event_date, e.start_time, e.venue_name, e.venue_city
               FROM ticket_orders o
               LEFT JOIN public_events e ON e.id = o.event_id
             WHERE o.id=?",
            [$orderId]
        );
        if (!$order || $order['status'] !== 'paid') {
            return 0;
        }

        // Idempotency: if tickets already exist, skip
        $existing = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM tickets WHERE order_id=?",
            [$orderId]
        );
        if ((int) ($existing['cnt'] ?? 0) > 0) {
            return 0;
        }

        $items = $this->db->fetchAll(
            "SELECT oi.*, tt.name AS ticket_type_name, tt.admissions
               FROM order_items oi
               LEFT JOIN event_ticket_types tt ON tt.id = oi.ticket_type_id
             WHERE oi.order_id=?",
            [$orderId]
        );

        $this->db->beginTransaction();
        $issued = 0;
        try {
            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                for ($i = 0; $i < $qty; $i++) {
                    $uid        = $this->generateUid();
                    $qrPayload  = url('/t/' . urlencode($uid));

                    $this->db->execute(
                        "INSERT INTO tickets
                            (ticket_uid, order_id, order_item_id, ticket_type_id, event_id,
                             qr_payload, status, issued_at)
                         VALUES (?,?,?,?,?,?,'valid',NOW())",
                        [
                            $uid,
                            $orderId,
                            (int) $item['id'],
                            (int) $item['ticket_type_id'],
                            (int) $order['event_id'],
                            $qrPayload,
                        ]
                    );
                    $issued++;
                }

                // Update qty_sold / qty_reserved
                $this->db->execute(
                    "UPDATE event_ticket_types
                     SET qty_sold     = qty_sold + ?,
                         qty_reserved = GREATEST(0, qty_reserved - ?),
                         updated_at   = NOW()
                     WHERE id=?",
                    [$qty, $qty, (int) $item['ticket_type_id']]
                );

                // Release the reservation rows
                $this->db->execute(
                    "UPDATE ticket_reservations SET released_at=NOW()
                     WHERE order_id=? AND ticket_type_id=? AND released_at IS NULL",
                    [$orderId, (int) $item['ticket_type_id']]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        // Send confirmation email (non-fatal)
        if ($issued > 0) {
            try {
                $this->sendTicketEmail($order, $orderId);
            } catch (\Throwable) {
                // Email failure logged separately; tickets are already issued
            }
        }

        return $issued;
    }

    private function sendTicketEmail(array $order, int $orderId): void
    {
        $tickets = $this->db->fetchAll(
            "SELECT t.*, tt.name AS ticket_type_name
               FROM tickets t
               LEFT JOIN event_ticket_types tt ON tt.id = t.ticket_type_id
             WHERE t.order_id=? AND t.status='valid'",
            [$orderId]
        );

        $lines = ['<p>Dear ' . htmlspecialchars($order['purchaser_name']) . ',</p>'];
        $lines[] = '<p>Your payment has been confirmed and your tickets for <strong>'
            . htmlspecialchars($order['event_title'] ?? 'the event')
            . '</strong> are ready.</p>';

        if ($order['event_date']) {
            $lines[] = '<p><strong>Date:</strong> ' . date('l, d F Y', strtotime($order['event_date'])) . '</p>';
        }
        if ($order['venue_name']) {
            $lines[] = '<p><strong>Venue:</strong> ' . htmlspecialchars($order['venue_name']) . ($order['venue_city'] ? ', ' . htmlspecialchars($order['venue_city']) : '') . '</p>';
        }

        $lines[] = '<h3>Your tickets</h3><ul>';
        foreach ($tickets as $t) {
            $url     = url('/t/' . urlencode($t['ticket_uid']));
            $lines[] = '<li>' . htmlspecialchars($t['ticket_type_name'] ?? 'Ticket')
                . ' — <a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></li>';
        }
        $lines[] = '</ul>';
        $lines[] = '<p>Present your QR code at the entrance. See you there!</p>';
        $lines[] = '<p><small>Order ref: ' . htmlspecialchars($order['public_ref']) . '</small></p>';

        $this->mailService->send(
            $order['purchaser_email'],
            $order['purchaser_name'],
            'Your tickets — ' . ($order['event_title'] ?? 'Event'),
            implode("\n", $lines)
        );
    }

    private function generateUid(): string
    {
        return Ref::generate('TKT');
    }
}
