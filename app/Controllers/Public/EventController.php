<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class EventController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $upcoming = $this->db->fetchAll(
            "SELECT * FROM public_events
             WHERE status IN ('on_sale','published','sold_out','sales_closed')
               AND event_date_utc >= NOW()
               AND deleted_at IS NULL
             ORDER BY event_date_utc
             LIMIT 20"
        );

        $past = $this->db->fetchAll(
            "SELECT * FROM public_events
             WHERE status IN ('completed','on_sale','published')
               AND event_date_utc < NOW()
               AND deleted_at IS NULL
             ORDER BY event_date_utc DESC
             LIMIT 6"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/events/index', [
                'pageTitle'       => 'Upcoming Events',
                'metaDescription' => 'Browse and book tickets for upcoming Unwinded sip-and-paint events in Johannesburg.',
                'upcoming'        => $upcoming,
                'past'            => $past,
                'bodyClass'       => 'page page--events',
            ])
        );
    }

    public function show(string $slug): Response
    {
        $event = $this->db->fetchOne(
            "SELECT * FROM public_events
             WHERE slug = ?
               AND status NOT IN ('draft','cancelled')
               AND deleted_at IS NULL",
            [$slug]
        );
        if (!$event) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Event Not Found'])
            );
        }

        $ticketTypes = $this->db->fetchAll(
            "SELECT * FROM event_ticket_types
             WHERE event_id = ? AND is_active = 1
             ORDER BY sort_order, price_cents",
            [$event['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/events/show', [
                'pageTitle'       => $event['meta_title'] ?: $event['title'],
                'metaDescription' => $event['meta_description'] ?: $event['short_description'],
                'ogImage'         => $event['og_image'] ?: $event['featured_image'],
                'event'           => $event,
                'ticketTypes'     => $ticketTypes,
                'bodyClass'       => 'page page--event-detail',
            ])
        );
    }
}
