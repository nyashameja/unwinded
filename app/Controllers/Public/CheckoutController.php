<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * Ticket checkout — full implementation is Phase 7.
 */
class CheckoutController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function show(string $slug): Response
    {
        $event = $this->db->fetchOne(
            "SELECT * FROM public_events WHERE slug = ? AND status = 'on_sale' AND deleted_at IS NULL",
            [$slug]
        );
        if (!$event) {
            return Response::make()->redirect(url('/events'));
        }

        $ticketTypes = $this->db->fetchAll(
            "SELECT * FROM event_ticket_types WHERE event_id = ? AND is_active = 1 ORDER BY sort_order",
            [$event['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/checkout/show', [
                'pageTitle'   => 'Book Tickets — ' . $event['title'],
                'metaRobots'  => 'noindex,nofollow',
                'event'       => $event,
                'ticketTypes' => $ticketTypes,
                'bodyClass'   => 'page page--checkout',
            ])
        );
    }

    public function reserve(string $slug): Response
    {
        flash('info', 'Online ticket booking is coming soon. Please contact us directly.');
        return Response::make()->redirect(url('/events/' . $slug));
    }

    public function confirm(string $ref): Response
    {
        flash('info', 'Online ticket booking is coming soon.');
        return Response::make()->redirect(url('/events'));
    }

    public function return(string $ref): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/checkout/return', [
                'pageTitle'  => 'Booking Return',
                'metaRobots' => 'noindex,nofollow',
                'ref'        => $ref,
                'bodyClass'  => 'page page--checkout-return',
            ])
        );
    }
}
