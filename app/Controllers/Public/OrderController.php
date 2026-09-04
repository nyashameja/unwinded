<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * Customer order view — full implementation is Phase 8.
 */
class OrderController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function show(string $ref, string $token): Response
    {
        $tokenHash = hash('sha256', base64_decode($token));
        $order = $this->db->fetchOne(
            "SELECT * FROM ticket_orders WHERE public_ref = ? AND access_token_hash = ?",
            [$ref, $tokenHash]
        );

        if (!$order) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Order Not Found'])
            );
        }

        $items = $this->db->fetchAll(
            "SELECT oi.*, ett.name AS ticket_type_name
             FROM order_items oi
             JOIN event_ticket_types ett ON ett.id = oi.ticket_type_id
             WHERE oi.order_id = ?",
            [$order['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/orders/show', [
                'pageTitle'  => 'Order ' . $order['public_ref'],
                'metaRobots' => 'noindex,nofollow',
                'order'      => $order,
                'items'      => $items,
                'bodyClass'  => 'page page--order',
            ])
        );
    }
}
