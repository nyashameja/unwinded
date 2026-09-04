<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class TicketTypeController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(string $eid): Response
    {
        $event = $this->findEvent((int) $eid);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        $ticketTypes = $this->db->fetchAll(
            "SELECT * FROM event_ticket_types WHERE event_id=? AND deleted_at IS NULL ORDER BY sort_order",
            [(int) $eid]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/ticket-types/index', [
                'pageTitle'   => 'Ticket Types — ' . $event['title'],
                'event'       => $event,
                'ticketTypes' => $ticketTypes,
            ])
        );
    }

    public function store(string $eid): Response
    {
        $event = $this->findEvent((int) $eid);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            flash('error', implode(' ', $errors));
            return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
        }

        $this->db->execute(
            "INSERT INTO event_ticket_types
                (event_id,name,description,admissions,price_cents,qty_available,
                 min_per_order,max_per_order,sales_open_at,sales_close_at,is_active,sort_order,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                (int) $eid,
                $data['name'], $data['description'],
                $data['admissions'], $data['price_cents'],
                $data['qty_available'],
                $data['min_per_order'], $data['max_per_order'],
                $data['sales_open_at'] ?: null, $data['sales_close_at'] ?: null,
                $data['is_active'], $data['sort_order'],
            ]
        );

        $this->activityLogger->log('ticket_type.created', 'event', $eid);
        flash('success', 'Ticket type added.');
        return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
    }

    public function update(string $eid, string $id): Response
    {
        $event = $this->findEvent((int) $eid);
        if (!$event) {
            flash('error', 'Event not found.');
            return Response::make()->redirect(url('/admin/events'));
        }

        $tt = $this->db->fetchOne(
            "SELECT * FROM event_ticket_types WHERE id=? AND event_id=? AND deleted_at IS NULL",
            [(int) $id, (int) $eid]
        );
        if (!$tt) {
            flash('error', 'Ticket type not found.');
            return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
        }

        if ($this->request->str('_delete')) {
            $this->db->execute("UPDATE event_ticket_types SET deleted_at=NOW() WHERE id=?", [(int) $id]);
            $this->activityLogger->log('ticket_type.deleted', 'event', $eid);
            flash('success', 'Ticket type removed.');
            return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            flash('error', implode(' ', $errors));
            return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
        }

        // qty_available cannot be less than qty_reserved + qty_sold
        $minQty = (int) $tt['qty_reserved'] + (int) $tt['qty_sold'];
        if ($data['qty_available'] < $minQty) {
            flash('error', "Quantity available cannot be less than {$minQty} (already reserved/sold).");
            return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
        }

        $this->db->execute(
            "UPDATE event_ticket_types SET
                name=?, description=?, admissions=?, price_cents=?, qty_available=?,
                min_per_order=?, max_per_order=?, sales_open_at=?, sales_close_at=?,
                is_active=?, sort_order=?, updated_at=NOW()
             WHERE id=?",
            [
                $data['name'], $data['description'],
                $data['admissions'], $data['price_cents'],
                $data['qty_available'],
                $data['min_per_order'], $data['max_per_order'],
                $data['sales_open_at'] ?: null, $data['sales_close_at'] ?: null,
                $data['is_active'], $data['sort_order'],
                (int) $id,
            ]
        );

        $this->activityLogger->log('ticket_type.updated', 'event', $eid);
        flash('success', 'Ticket type updated.');
        return Response::make()->redirect(url('/admin/events/' . $eid . '/edit'));
    }

    private function validate(): array
    {
        $data = [
            'name'           => trim($this->request->str('name')),
            'description'    => trim($this->request->str('description')),
            'admissions'     => max(1, (int) $this->request->str('admissions')),
            'price_cents'    => (int) round((float) str_replace([' ',','], '', $this->request->str('price_rand')) * 100),
            'qty_available'  => max(0, (int) $this->request->str('qty_available')),
            'min_per_order'  => max(1, (int) ($this->request->str('min_per_order') ?: 1)),
            'max_per_order'  => max(1, (int) ($this->request->str('max_per_order') ?: 10)),
            'sales_open_at'  => trim($this->request->str('sales_open_at')),
            'sales_close_at' => trim($this->request->str('sales_close_at')),
            'is_active'      => $this->request->str('is_active') ? 1 : 0,
            'sort_order'     => (int) ($this->request->str('sort_order') ?: 0),
        ];

        $errors = [];
        if (strlen($data['name']) < 1) {
            $errors[] = 'Name is required.';
        }
        if ($data['price_cents'] < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        return [$data, $errors];
    }

    private function findEvent(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM public_events WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
