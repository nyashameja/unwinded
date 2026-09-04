<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class TicketController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function show(string $uid): Response
    {
        $ticket = $this->db->fetchOne(
            "SELECT t.*, tt.name AS ticket_type_name, e.title AS event_title,
                    e.event_date, e.start_time, e.venue_name, e.venue_city, e.slug AS event_slug
               FROM tickets t
               LEFT JOIN event_ticket_types tt ON tt.id = t.ticket_type_id
               LEFT JOIN public_events e ON e.id = t.event_id
             WHERE t.ticket_uid=?",
            [$uid]
        );

        if (!$ticket) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Ticket Not Found'])
            );
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/tickets/show', [
                'pageTitle'  => 'Ticket — ' . ($ticket['event_title'] ?? 'Event'),
                'metaRobots' => 'noindex,nofollow',
                'ticket'     => $ticket,
                'bodyClass'  => 'page page--ticket',
            ])
        );
    }
}
