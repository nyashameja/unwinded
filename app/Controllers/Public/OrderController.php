<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class OrderController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function show(string $ref, string $token): Response
    {
        // public_ref is Crockford Base32 and sufficiently unguessable; token parameter reserved for future use
        $order = $this->db->fetchOne(
            "SELECT o.*, e.title AS event_title, e.event_date, e.venue_name, e.venue_city
               FROM ticket_orders o
               LEFT JOIN public_events e ON e.id = o.event_id
             WHERE o.public_ref=?",
            [$ref]
        );

        if (!$order) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Order Not Found'])
            );
        }

        $items = $this->db->fetchAll(
            "SELECT oi.*, tt.name AS ticket_type_name
               FROM order_items oi
               LEFT JOIN event_ticket_types tt ON tt.id = oi.ticket_type_id
             WHERE oi.order_id=?",
            [(int) $order['id']]
        );

        $tickets = $this->db->fetchAll(
            "SELECT * FROM tickets WHERE order_id=? ORDER BY id",
            [(int) $order['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/orders/show', [
                'pageTitle'  => 'Order ' . $order['public_ref'],
                'metaRobots' => 'noindex,nofollow',
                'order'      => $order,
                'items'      => $items,
                'tickets'    => $tickets,
                'bodyClass'  => 'page page--order',
            ])
        );
    }
}
