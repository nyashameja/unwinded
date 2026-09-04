<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Support\Str;

class EventController
{
    private const STATUSES = ['draft','scheduled','on_sale','sold_out','sales_closed','completed','cancelled','postponed'];

    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];

        $events = $this->db->fetchAll(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM event_ticket_types WHERE event_id = e.id AND deleted_at IS NULL) AS ticket_type_count
               FROM public_events e
               $where
             ORDER BY e.event_date DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/events/index', [
                'pageTitle' => 'Events',
                'events'    => $events,
                'status'    => $status,
                'statuses'  => self::STATUSES,
            ])
        );
    }

    public function create(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/events/create', [
                'pageTitle' => 'New Event',
                'event'     => [],
                'errors'    => [],
                'statuses'  => self::STATUSES,
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/events/create', [
                    'pageTitle' => 'New Event',
                    'event'     => $data,
                    'errors'    => $errors,
                    'statuses'  => self::STATUSES,
                ])
            );
        }

        $slug = Str::slug($data['slug'] ?: $data['title']);
        $this->ensureUniqueSlug($slug);

        $this->db->execute(
            "INSERT INTO public_events
                (slug,title,short_description,body,event_date,event_date_utc,start_time,end_time,
                 venue_name,venue_address,venue_city,map_link,featured_image,total_capacity,
                 what_is_included,what_to_bring,dress_code,cancellation_policy,
                 status,sales_open_at,sales_close_at,
                 meta_title,meta_description,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                $slug, $data['title'], $data['short_description'], $data['body'],
                $data['event_date'] ?: null, $data['event_date'] ?: null,
                $data['start_time'] ?: null, $data['end_time'] ?: null,
                $data['venue_name'], $data['venue_address'], $data['venue_city'], $data['map_link'],
                $data['featured_image'] ?: null, (int) $data['total_capacity'],
                $data['what_is_included'], $data['what_to_bring'],
                $data['dress_code'], $data['cancellation_policy'],
                $data['status'],
                $data['sales_open_at'] ?: null, $data['sales_close_at'] ?: null,
                $data['meta_title'], $data['meta_description'],
            ]
        );

        $id = (int) $this->db->lastInsertId();
        $this->activityLogger->log('event.created', 'event', (string) $id);
        flash('success', 'Event created.');
        return Response::make()->redirect(url('/admin/events/' . $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $event = $this->find((int) $id);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        $ticketTypes = $this->db->fetchAll(
            "SELECT * FROM event_ticket_types WHERE event_id=? AND deleted_at IS NULL ORDER BY sort_order",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/events/edit', [
                'pageTitle'   => 'Edit Event',
                'event'       => $event,
                'ticketTypes' => $ticketTypes,
                'errors'      => [],
                'statuses'    => self::STATUSES,
            ])
        );
    }

    public function update(string $id): Response
    {
        $event = $this->find((int) $id);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            $ticketTypes = $this->db->fetchAll(
                "SELECT * FROM event_ticket_types WHERE event_id=? AND deleted_at IS NULL ORDER BY sort_order",
                [(int) $id]
            );
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/events/edit', [
                    'pageTitle'   => 'Edit Event',
                    'event'       => array_merge($event, $data),
                    'ticketTypes' => $ticketTypes,
                    'errors'      => $errors,
                    'statuses'    => self::STATUSES,
                ])
            );
        }

        $slug = Str::slug($data['slug'] ?: $data['title']);
        if ($slug !== $event['slug']) {
            $this->ensureUniqueSlug($slug, (int) $id);
        }

        $this->db->execute(
            "UPDATE public_events SET
                slug=?, title=?, short_description=?, body=?,
                event_date=?, event_date_utc=?, start_time=?, end_time=?,
                venue_name=?, venue_address=?, venue_city=?, map_link=?,
                featured_image=?, total_capacity=?,
                what_is_included=?, what_to_bring=?, dress_code=?, cancellation_policy=?,
                status=?, sales_open_at=?, sales_close_at=?,
                meta_title=?, meta_description=?, updated_at=NOW()
             WHERE id=?",
            [
                $slug, $data['title'], $data['short_description'], $data['body'],
                $data['event_date'] ?: null, $data['event_date'] ?: null,
                $data['start_time'] ?: null, $data['end_time'] ?: null,
                $data['venue_name'], $data['venue_address'], $data['venue_city'], $data['map_link'],
                $data['featured_image'] ?: null, (int) $data['total_capacity'],
                $data['what_is_included'], $data['what_to_bring'],
                $data['dress_code'], $data['cancellation_policy'],
                $data['status'],
                $data['sales_open_at'] ?: null, $data['sales_close_at'] ?: null,
                $data['meta_title'], $data['meta_description'],
                (int) $id,
            ]
        );

        $this->activityLogger->log('event.updated', 'event', $id);
        flash('success', 'Event updated.');
        return Response::make()->redirect(url('/admin/events/' . $id . '/edit'));
    }

    public function destroy(string $id): Response
    {
        $event = $this->find((int) $id);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        $this->db->execute("UPDATE public_events SET deleted_at=NOW() WHERE id=?", [(int) $id]);
        $this->activityLogger->log('event.deleted', 'event', $id);
        flash('success', 'Event deleted.');
        return Response::make()->redirect(url('/admin/events'));
    }

    private function validate(): array
    {
        $data = [
            'title'               => trim($this->request->str('title')),
            'slug'                => trim($this->request->str('slug')),
            'short_description'   => trim($this->request->str('short_description')),
            'body'                => $this->request->str('body'),
            'event_date'          => trim($this->request->str('event_date')),
            'start_time'          => trim($this->request->str('start_time')),
            'end_time'            => trim($this->request->str('end_time')),
            'venue_name'          => trim($this->request->str('venue_name')),
            'venue_address'       => trim($this->request->str('venue_address')),
            'venue_city'          => trim($this->request->str('venue_city')),
            'map_link'            => trim($this->request->str('map_link')),
            'featured_image'      => trim($this->request->str('featured_image')),
            'total_capacity'      => $this->request->str('total_capacity'),
            'what_is_included'    => $this->request->str('what_is_included'),
            'what_to_bring'       => $this->request->str('what_to_bring'),
            'dress_code'          => trim($this->request->str('dress_code')),
            'cancellation_policy' => $this->request->str('cancellation_policy'),
            'status'              => $this->request->str('status'),
            'sales_open_at'       => trim($this->request->str('sales_open_at')),
            'sales_close_at'      => trim($this->request->str('sales_close_at')),
            'meta_title'          => substr(trim($this->request->str('meta_title')), 0, 500),
            'meta_description'    => substr(trim($this->request->str('meta_description')), 0, 500),
        ];

        $errors = [];
        if (strlen($data['title']) < 2) {
            $errors['title'] = 'Title is required.';
        }
        if (!in_array($data['status'], self::STATUSES, true)) {
            $data['status'] = 'draft';
        }
        if ($data['event_date'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['event_date'])) {
            $errors['event_date'] = 'Invalid date format.';
        }

        return [$data, $errors];
    }

    private function ensureUniqueSlug(string &$slug, int $excludeId = 0): void
    {
        $base    = $slug;
        $counter = 2;
        while (true) {
            $row = $this->db->fetchOne(
                "SELECT id FROM public_events WHERE slug=? AND id<>? AND deleted_at IS NULL",
                [$slug, $excludeId]
            );
            if (!$row) {
                break;
            }
            $slug = $base . '-' . $counter++;
        }
    }

    private function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM public_events WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
