<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class ChecklistController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $type = $this->request->str('type');
        if (!in_array($type, ['private_booking', 'public_event'], true)) {
            $type = '';
        }

        $where  = $type ? "WHERE ec.checkable_type = ?" : "";
        $params = $type ? [$type] : [];

        $checklists = $this->db->fetchAll(
            "SELECT ec.*,
                    COUNT(ci.id)                        AS total_items,
                    SUM(ci.is_completed)                AS completed_items,
                    SUM(ci.is_required AND NOT ci.is_completed) AS overdue_required,
                    ct.name                             AS template_name
               FROM event_checklists ec
               LEFT JOIN checklist_items ci ON ci.checklist_id = ec.id
               LEFT JOIN checklist_templates ct ON ct.id = ec.template_id
               $where
             GROUP BY ec.id
             ORDER BY ec.created_at DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/checklists/index', [
                'pageTitle'  => 'Checklists',
                'checklists' => $checklists,
                'type'       => $type,
            ])
        );
    }

    public function show(string $id): Response
    {
        $checklist = $this->db->fetchOne(
            "SELECT ec.*, ct.name AS template_name
               FROM event_checklists ec
               LEFT JOIN checklist_templates ct ON ct.id = ec.template_id
              WHERE ec.id = ?",
            [(int) $id]
        );

        if (!$checklist) {
            flash('error', 'Checklist not found.');
            return Response::make()->redirect(url('/admin/checklists'));
        }

        $items = $this->db->fetchAll(
            "SELECT ci.*, u.name AS completed_by_name
               FROM checklist_items ci
               LEFT JOIN users u ON u.id = ci.completed_by
              WHERE ci.checklist_id = ?
             ORDER BY ci.sort_order, ci.id",
            [(int) $id]
        );

        $eventLabel = $this->resolveEventLabel($checklist);

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/checklists/show', [
                'pageTitle'  => 'Checklist: ' . $checklist['name'],
                'checklist'  => $checklist,
                'items'      => $items,
                'eventLabel' => $eventLabel,
            ])
        );
    }

    public function toggle(string $id, string $iid): Response
    {
        $item = $this->db->fetchOne(
            "SELECT ci.* FROM checklist_items ci
               JOIN event_checklists ec ON ec.id = ci.checklist_id
              WHERE ci.id = ? AND ec.id = ?",
            [(int) $iid, (int) $id]
        );

        if (!$item) {
            return Response::make()->status(404)->body('Not found');
        }

        if ($item['is_completed']) {
            $this->db->execute(
                "UPDATE checklist_items SET is_completed=0, completed_by=NULL, completed_at=NULL WHERE id=?",
                [(int) $iid]
            );
        } else {
            $this->db->execute(
                "UPDATE checklist_items SET is_completed=1, completed_by=?, completed_at=NOW() WHERE id=?",
                [$_SESSION['user']['id'] ?? null, (int) $iid]
            );
        }

        flash('success', 'Item updated.');
        return Response::make()->redirect(url('/admin/checklists/' . (int) $id));
    }

    private function resolveEventLabel(array $checklist): string
    {
        if ($checklist['checkable_type'] === 'private_booking') {
            $row = $this->db->fetchOne(
                "SELECT public_ref FROM private_bookings WHERE id = ?",
                [$checklist['checkable_id']]
            );
            return $row ? 'Booking ' . $row['public_ref'] : 'Booking #' . $checklist['checkable_id'];
        }

        $row = $this->db->fetchOne(
            "SELECT title FROM public_events WHERE id = ?",
            [$checklist['checkable_id']]
        );
        return $row ? $row['title'] : 'Event #' . $checklist['checkable_id'];
    }
}
