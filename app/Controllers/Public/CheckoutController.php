<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;
use Unwinded\Support\Ref;

class CheckoutController
{
    public const RESERVATION_MINUTES = 15;

    public function __construct(
        private Database    $db,
        private Request     $request,
        private View        $view,
        private MailService $mailService,
    ) {}

    public function show(string $slug): Response
    {
        $event = $this->findOnSaleEvent($slug);
        if (!$event) {
            flash('info', 'Ticket sales are not currently open for this event.');
            return Response::make()->redirect(url('/events/' . $slug));
        }

        $ticketTypes = $this->loadTicketTypes($event['id']);
        if (empty($ticketTypes)) {
            flash('info', 'No ticket types are currently available.');
            return Response::make()->redirect(url('/events/' . $slug));
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/checkout/show', [
                'pageTitle'   => 'Buy Tickets — ' . $event['title'],
                'metaRobots'  => 'noindex,nofollow',
                'event'       => $event,
                'ticketTypes' => $ticketTypes,
                'errors'      => [],
                'bodyClass'   => 'page page--checkout',
            ])
        );
    }

    public function reserve(string $slug): Response
    {
        $event = $this->findOnSaleEvent($slug);
        if (!$event) {
            return Response::make()->redirect(url('/events/' . $slug));
        }

        $ticketTypes = $this->loadTicketTypes($event['id']);
        $errors      = [];

        // Collect and validate quantities
        $selections = []; // [ticket_type_id => qty]
        foreach ($ticketTypes as $tt) {
            $qty = (int) ($this->request->str('qty_' . $tt['id']) ?? 0);
            if ($qty < 0) {
                $qty = 0;
            }
            if ($qty === 0) {
                continue;
            }
            if ($qty < (int) $tt['min_per_order']) {
                $errors[] = 'Minimum order for "' . $tt['name'] . '" is ' . $tt['min_per_order'] . '.';
                continue;
            }
            if ($qty > (int) $tt['max_per_order']) {
                $errors[] = 'Maximum order for "' . $tt['name'] . '" is ' . $tt['max_per_order'] . '.';
                continue;
            }
            $selections[(int) $tt['id']] = $qty;
        }

        if (empty($selections) && empty($errors)) {
            $errors[] = 'Please select at least one ticket.';
        }

        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/checkout/show', [
                    'pageTitle'   => 'Buy Tickets — ' . $event['title'],
                    'metaRobots'  => 'noindex,nofollow',
                    'event'       => $event,
                    'ticketTypes' => $ticketTypes,
                    'errors'      => $errors,
                    'bodyClass'   => 'page page--checkout',
                ])
            );
        }

        // Purchaser details
        $name  = trim($this->request->str('name'));
        $email = trim($this->request->str('email'));
        $phone = trim($this->request->str('phone'));

        if (strlen($name) < 2) {
            $errors[] = 'Please enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/checkout/show', [
                    'pageTitle'   => 'Buy Tickets — ' . $event['title'],
                    'metaRobots'  => 'noindex,nofollow',
                    'event'       => $event,
                    'ticketTypes' => $ticketTypes,
                    'errors'      => $errors,
                    'bodyClass'   => 'page page--checkout',
                ])
            );
        }

        // Build a lookup map for ticket types by id
        $ttMap = [];
        foreach ($ticketTypes as $tt) {
            $ttMap[(int) $tt['id']] = $tt;
        }

        // Reserve in a transaction — check availability atomically using SELECT ... FOR UPDATE equivalent
        $this->db->beginTransaction();
        try {
            // Re-read available quantities with a write lock
            $ttIds        = array_keys($selections);
            $placeholders = implode(',', array_fill(0, count($ttIds), '?'));

            $freshTypes = $this->db->fetchAll(
                "SELECT * FROM event_ticket_types WHERE id IN ($placeholders) AND is_active=1 AND deleted_at IS NULL FOR UPDATE",
                $ttIds
            );
            $freshMap = [];
            foreach ($freshTypes as $ft) {
                $freshMap[(int) $ft['id']] = $ft;
            }

            foreach ($selections as $ttId => $qty) {
                $ft = $freshMap[$ttId] ?? null;
                if (!$ft) {
                    throw new \RuntimeException('Ticket type no longer available.');
                }
                $remaining = (int) $ft['qty_available'] - (int) $ft['qty_reserved'] - (int) $ft['qty_sold'];
                if ($qty > $remaining) {
                    throw new \RuntimeException(
                        "Only {$remaining} ticket(s) left for \"{$ft['name']}\". Please reduce your quantity."
                    );
                }
            }

            // Calculate totals server-side — never trust browser values
            $subtotalCents = 0;
            foreach ($selections as $ttId => $qty) {
                $ft             = $freshMap[$ttId];
                $subtotalCents += (int) $ft['price_cents'] * $qty;
            }
            $totalCents = $subtotalCents; // discounts handled in Phase 8

            // Upsert customer
            $customer = $this->db->fetchOne(
                "SELECT id FROM customers WHERE email=? AND deleted_at IS NULL",
                [$email]
            );
            if (!$customer) {
                $customerRef = Ref::generate('CST');
                $this->db->execute(
                    "INSERT INTO customers (public_ref,name,email,phone,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())",
                    [$customerRef, $name, $email, $phone]
                );
                $customerId = (int) $this->db->lastInsertId();
            } else {
                $customerId = (int) $customer['id'];
            }

            // Create pending order
            $orderRef         = Ref::generate('ORD');
            $reservedUntilSql = 'DATE_ADD(NOW(), INTERVAL ' . self::RESERVATION_MINUTES . ' MINUTE)';

            $this->db->execute(
                "INSERT INTO ticket_orders
                    (public_ref,event_id,customer_id,purchaser_name,purchaser_email,purchaser_phone,
                     subtotal_cents,total_cents,status,reserved_until,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,?,?,'pending',$reservedUntilSql,NOW(),NOW())",
                [$orderRef, $event['id'], $customerId, $name, $email, $phone, $subtotalCents, $totalCents]
            );
            $orderId = (int) $this->db->lastInsertId();

            // Insert order items and update reservations
            foreach ($selections as $ttId => $qty) {
                $ft          = $freshMap[$ttId];
                $unitCents   = (int) $ft['price_cents'];
                $lineCents   = $unitCents * $qty;

                $this->db->execute(
                    "INSERT INTO order_items (order_id,ticket_type_id,quantity,unit_price_cents,line_total_cents)
                     VALUES (?,?,?,?,?)",
                    [$orderId, $ttId, $qty, $unitCents, $lineCents]
                );

                $this->db->execute(
                    "INSERT INTO ticket_reservations (ticket_type_id,order_id,quantity,expires_at)
                     VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL ? MINUTE))",
                    [$ttId, $orderId, $qty, self::RESERVATION_MINUTES]
                );

                $this->db->execute(
                    "UPDATE event_ticket_types SET qty_reserved = qty_reserved + ?, updated_at=NOW() WHERE id=?",
                    [$qty, $ttId]
                );
            }

            $this->db->commit();
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/checkout/show', [
                    'pageTitle'   => 'Buy Tickets — ' . $event['title'],
                    'metaRobots'  => 'noindex,nofollow',
                    'event'       => $event,
                    'ticketTypes' => $ticketTypes,
                    'errors'      => [$e->getMessage()],
                    'bodyClass'   => 'page page--checkout',
                ])
            );
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        // Redirect to order summary/payment page
        return Response::make()->redirect(url('/checkout/' . $orderRef . '/confirm'));
    }

    public function confirm(string $ref): Response
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, e.title AS event_title, e.event_date, e.venue_name, e.venue_city
               FROM ticket_orders o
               LEFT JOIN public_events e ON e.id = o.event_id
             WHERE o.public_ref=?",
            [$ref]
        );

        if (!$order) {
            flash('error', 'Order not found.');
            return Response::make()->redirect(url('/events'));
        }

        // Expire check
        if ($order['status'] === 'pending' && strtotime($order['reserved_until']) < time()) {
            $this->expireOrder((int) $order['id']);
            flash('error', 'Your reservation expired. Please try again.');
            return Response::make()->redirect(url('/events/' . ($order['event_slug'] ?? '')));
        }

        if (!in_array($order['status'], ['pending','paid'], true)) {
            flash('info', 'This order is ' . str_replace('_', ' ', $order['status']) . '.');
            return Response::make()->redirect(url('/orders/' . $ref . '/view'));
        }

        $items = $this->db->fetchAll(
            "SELECT oi.*, tt.name AS ticket_type_name
               FROM order_items oi
               LEFT JOIN event_ticket_types tt ON tt.id = oi.ticket_type_id
             WHERE oi.order_id=?",
            [(int) $order['id']]
        );

        // Phase 8 will add PayFast here; for now show EFT instructions and confirm manually
        if ($this->request->str('_method') === 'POST' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processConfirm($order, $items);
        }

        $secondsLeft = max(0, strtotime($order['reserved_until']) - time());

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/checkout/confirm', [
                'pageTitle'   => 'Confirm Order — ' . $order['public_ref'],
                'metaRobots'  => 'noindex,nofollow',
                'order'       => $order,
                'items'       => $items,
                'secondsLeft' => $secondsLeft,
                'bodyClass'   => 'page page--checkout-confirm',
            ])
        );
    }

    public function return(string $ref): Response
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, e.title AS event_title FROM ticket_orders o
               LEFT JOIN public_events e ON e.id=o.event_id
             WHERE o.public_ref=?",
            [$ref]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/checkout/return', [
                'pageTitle'  => 'Order ' . $ref,
                'metaRobots' => 'noindex,nofollow',
                'order'      => $order,
                'ref'        => $ref,
                'bodyClass'  => 'page page--checkout-return',
            ])
        );
    }

    private function processConfirm(array $order, array $items): Response
    {
        // Mark pending EFT order — tickets will be issued once payment is verified by admin
        // Do NOT mark as paid here; payment verification is admin-only
        if ($order['status'] !== 'pending') {
            flash('info', 'Order is already ' . $order['status'] . '.');
            return Response::make()->redirect(url('/checkout/' . $order['public_ref'] . '/confirm'));
        }

        // Extend the reservation to give EFT transfer time (48 hours)
        $this->db->execute(
            "UPDATE ticket_orders SET reserved_until = DATE_ADD(NOW(), INTERVAL 48 HOUR), updated_at=NOW() WHERE id=?",
            [(int) $order['id']]
        );

        // Send confirmation email with payment instructions
        $bankingDetails = setting('bank.details', 'Please contact us for banking details.');
        $body = $this->buildEftEmailBody($order, $items, $bankingDetails);

        try {
            $this->mailService->send(
                $order['purchaser_email'],
                $order['purchaser_name'],
                'Order ' . $order['public_ref'] . ' — payment instructions',
                $body
            );
        } catch (\Throwable) {
            // Email failure is non-fatal; admin can resend
        }

        flash('success', 'Order confirmed! Please complete your EFT payment within 48 hours.');
        return Response::make()->redirect(url('/checkout/return/' . $order['public_ref']));
    }

    private function expireOrder(int $orderId): void
    {
        $this->db->beginTransaction();
        try {
            $reservations = $this->db->fetchAll(
                "SELECT * FROM ticket_reservations WHERE order_id=? AND released_at IS NULL",
                [$orderId]
            );
            foreach ($reservations as $res) {
                $this->db->execute(
                    "UPDATE event_ticket_types SET qty_reserved=GREATEST(0,qty_reserved-?), updated_at=NOW() WHERE id=?",
                    [(int) $res['quantity'], (int) $res['ticket_type_id']]
                );
                $this->db->execute("UPDATE ticket_reservations SET released_at=NOW() WHERE id=?", [(int) $res['id']]);
            }
            $this->db->execute("UPDATE ticket_orders SET status='expired', updated_at=NOW() WHERE id=?", [$orderId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
        }
    }

    private function buildEftEmailBody(array $order, array $items, string $bankingDetails): string
    {
        $lines = [
            '<p>Dear ' . htmlspecialchars($order['purchaser_name']) . ',</p>',
            '<p>Thank you for your order. Please complete your EFT payment to confirm your tickets.</p>',
            '<h3>Order summary</h3>',
            '<table border="1" cellpadding="6" style="border-collapse:collapse;">',
            '<tr><th>Ticket type</th><th>Qty</th><th>Total</th></tr>',
        ];
        foreach ($items as $item) {
            $lines[] = '<tr><td>' . htmlspecialchars($item['ticket_type_name'] ?? '—') . '</td>'
                . '<td>' . (int) $item['quantity'] . '</td>'
                . '<td>' . money($item['line_total_cents']) . '</td></tr>';
        }
        $lines[] = '<tr><td colspan="2"><strong>Total</strong></td><td><strong>' . money($order['total_cents']) . '</strong></td></tr>';
        $lines[] = '</table>';
        $lines[] = '<h3>Payment details</h3>';
        $lines[] = '<p>' . nl2br(htmlspecialchars($bankingDetails)) . '</p>';
        $lines[] = '<p><strong>Reference: ' . htmlspecialchars($order['public_ref']) . '</strong></p>';
        $lines[] = '<p>Please use your order reference as the payment reference. Your tickets will be issued once payment is confirmed.</p>';
        $lines[] = '<p>Payment must be received within 48 hours or your reservation will expire.</p>';
        return implode("\n", $lines);
    }

    private function findOnSaleEvent(string $slug): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM public_events
             WHERE slug=? AND status='on_sale' AND deleted_at IS NULL
               AND (sales_open_at IS NULL OR sales_open_at <= NOW())
               AND (sales_close_at IS NULL OR sales_close_at >= NOW())",
            [$slug]
        ) ?: null;
    }

    private function loadTicketTypes(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM event_ticket_types
             WHERE event_id=? AND is_active=1 AND deleted_at IS NULL
               AND (sales_open_at IS NULL OR sales_open_at <= NOW())
               AND (sales_close_at IS NULL OR sales_close_at >= NOW())
             ORDER BY sort_order",
            [$eventId]
        );
    }
}
