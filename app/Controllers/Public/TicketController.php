<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * Individual ticket view / QR scan — full implementation is Phase 8.
 */
class TicketController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function show(string $uid): Response
    {
        $ticket = $this->db->fetchOne(
            "SELECT t.*, ett.name AS ticket_type_name, pe.title AS event_title,
                    pe.event_date, pe.start_time, pe.venue_name
             FROM tickets t
             JOIN event_ticket_types ett ON ett.id = t.ticket_type_id
             JOIN public_events pe ON pe.id = ett.event_id
             WHERE t.uid = ?",
            [$uid]
        );

        if (!$ticket) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Ticket Not Found'])
            );
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/tickets/show', [
                'pageTitle'  => 'Ticket — ' . $ticket['event_title'],
                'metaRobots' => 'noindex,nofollow',
                'ticket'     => $ticket,
                'bodyClass'  => 'page page--ticket',
            ])
        );
    }
}
