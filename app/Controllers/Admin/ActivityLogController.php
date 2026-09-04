<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class ActivityLogController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $entityType = $this->request->str('entity_type') ?? '';
        $userId     = (int) ($this->request->str('user_id') ?? 0);

        $where  = "WHERE 1=1";
        $params = [];

        if ($entityType) {
            $where   .= " AND al.entity_type = ?";
            $params[] = $entityType;
        }
        if ($userId) {
            $where   .= " AND al.user_id = ?";
            $params[] = $userId;
        }

        $logs = $this->db->fetchAll(
            "SELECT al.*, u.name AS user_display_name
               FROM activity_logs al
               LEFT JOIN users u ON u.id = al.user_id
               $where
             ORDER BY al.created_at DESC
             LIMIT 300",
            $params
        );

        $entityTypes = $this->db->fetchAll(
            "SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type"
        );

        $users = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/activity-log/index', [
                'pageTitle'   => 'Activity Log',
                'logs'        => $logs,
                'entityTypes' => array_column($entityTypes, 'entity_type'),
                'users'       => $users,
                'entityType'  => $entityType,
                'userId'      => $userId,
            ])
        );
    }
}
