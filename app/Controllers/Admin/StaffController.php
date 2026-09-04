<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class StaffController
{
    private const ROLES = ['facilitator', 'photographer', 'coordinator', 'host', 'assistant', 'other'];

    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $upcomingBookings = $this->db->fetchAll(
            "SELECT pb.id, pb.public_ref, pb.event_date, c.name AS customer_name,
                    GROUP_CONCAT(u.name ORDER BY sa.role SEPARATOR ', ') AS staff_names
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
               LEFT JOIN staff_assignments sa ON sa.assignable_type='private_booking' AND sa.assignable_id=pb.id
               LEFT JOIN users u ON u.id = sa.user_id
              WHERE pb.event_date_utc >= CURDATE()
                AND pb.booking_status NOT IN ('cancelled','refunded')
             GROUP BY pb.id
             ORDER BY pb.event_date ASC
             LIMIT 50"
        );

        $upcomingEvents = $this->db->fetchAll(
            "SELECT pe.id, pe.public_ref, pe.title, pe.event_date,
                    GROUP_CONCAT(u.name ORDER BY sa.role SEPARATOR ', ') AS staff_names
               FROM public_events pe
               LEFT JOIN staff_assignments sa ON sa.assignable_type='public_event' AND sa.assignable_id=pe.id
               LEFT JOIN users u ON u.id = sa.user_id
              WHERE pe.event_date_utc >= CURDATE()
                AND pe.status NOT IN ('cancelled')
             GROUP BY pe.id
             ORDER BY pe.event_date ASC
             LIMIT 50"
        );

        $assignments = $this->db->fetchAll(
            "SELECT sa.*, u.name AS user_name, u.email AS user_email
               FROM staff_assignments sa
               JOIN users u ON u.id = sa.user_id
             ORDER BY sa.assigned_at DESC
             LIMIT 200"
        );

        $users = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/staff/index', [
                'pageTitle'        => 'Staff Assignments',
                'upcomingBookings' => $upcomingBookings,
                'upcomingEvents'   => $upcomingEvents,
                'assignments'      => $assignments,
                'users'            => $users,
                'roles'            => self::ROLES,
            ])
        );
    }

    public function store(): Response
    {
        $assignableType = $this->request->str('assignable_type');
        $assignableId   = (int) ($this->request->str('assignable_id') ?? 0);
        $userId         = (int) ($this->request->str('user_id') ?? 0);
        $role           = $this->request->str('role') ?? 'facilitator';
        $notes          = trim($this->request->str('notes') ?? '');

        if (!in_array($assignableType, ['private_booking', 'public_event'], true)) {
            flash('error', 'Invalid assignment type.');
            return Response::make()->redirect(url('/admin/staff'));
        }

        if (!in_array($role, self::ROLES, true)) {
            $role = 'facilitator';
        }

        if (!$assignableId || !$userId) {
            flash('error', 'Event and staff member are required.');
            return Response::make()->redirect(url('/admin/staff'));
        }

        $existing = $this->db->fetchScalar(
            "SELECT id FROM staff_assignments WHERE assignable_type=? AND assignable_id=? AND user_id=? AND role=?",
            [$assignableType, $assignableId, $userId, $role]
        );

        if ($existing) {
            flash('error', 'This person is already assigned in that role.');
            return Response::make()->redirect(url('/admin/staff'));
        }

        $this->db->insert('staff_assignments', [
            'assignable_type' => $assignableType,
            'assignable_id'   => $assignableId,
            'user_id'         => $userId,
            'role'            => $role,
            'notes'           => $notes ?: null,
            'assigned_by'     => $_SESSION['user']['id'] ?? null,
            'assigned_at'     => date('Y-m-d H:i:s'),
        ]);

        flash('success', 'Staff member assigned.');
        return Response::make()->redirect(url('/admin/staff'));
    }

    public function destroy(string $id): Response
    {
        $this->db->execute("DELETE FROM staff_assignments WHERE id = ?", [(int) $id]);

        flash('success', 'Assignment removed.');
        return Response::make()->redirect(url('/admin/staff'));
    }
}
