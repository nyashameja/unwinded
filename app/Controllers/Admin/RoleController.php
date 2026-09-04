<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class RoleController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $roles = $this->db->fetchAll(
            "SELECT r.*,
                    COUNT(DISTINCT rp.permission_id) AS permission_count,
                    COUNT(DISTINCT ur.user_id)        AS user_count
               FROM roles r
               LEFT JOIN role_permissions rp ON rp.role_id = r.id
               LEFT JOIN user_roles ur ON ur.role_id = r.id
             GROUP BY r.id
             ORDER BY r.label"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/roles/index', [
                'pageTitle' => 'Roles',
                'roles'     => $roles,
            ])
        );
    }

    public function create(): Response
    {
        $permissions = $this->db->fetchAll(
            "SELECT * FROM permissions ORDER BY group_name, label"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/roles/create', [
                'pageTitle'   => 'New Role',
                'permissions' => $permissions,
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/roles/create'));
        }

        $id = $this->db->insert('roles', $data);
        $this->syncPermissions((int) $id);

        flash('success', 'Role created.');
        return Response::make()->redirect(url('/admin/roles/' . (int) $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $role = $this->findRole((int) $id);
        if (!$role) {
            flash('error', 'Role not found.');
            return Response::make()->redirect(url('/admin/roles'));
        }

        $permissions    = $this->db->fetchAll("SELECT * FROM permissions ORDER BY group_name, label");
        $rolePermIds    = array_column(
            $this->db->fetchAll("SELECT permission_id FROM role_permissions WHERE role_id=?", [(int) $id]),
            'permission_id'
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/roles/edit', [
                'pageTitle'      => 'Edit Role',
                'role'           => $role,
                'permissions'    => $permissions,
                'rolePermIds'    => $rolePermIds,
            ])
        );
    }

    public function update(string $id): Response
    {
        $role = $this->findRole((int) $id);
        if (!$role) {
            flash('error', 'Role not found.');
            return Response::make()->redirect(url('/admin/roles'));
        }

        if ($role['is_super']) {
            flash('error', 'The super-admin role cannot be edited.');
            return Response::make()->redirect(url('/admin/roles'));
        }

        [$data, $errors] = $this->validate($role['name']);

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/roles/' . (int) $id . '/edit'));
        }

        $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
        $this->db->execute(
            "UPDATE roles SET $set, updated_at=NOW() WHERE id=?",
            [...array_values($data), (int) $id]
        );

        $this->syncPermissions((int) $id);

        flash('success', 'Role updated.');
        return Response::make()->redirect(url('/admin/roles/' . (int) $id . '/edit'));
    }

    private function validate(string $existingName = ''): array
    {
        $errors = [];
        $data   = [];

        $label = trim($this->request->str('label') ?? '');
        if (!$label) {
            $errors[] = 'Label is required.';
        } else {
            $data['label'] = substr($label, 0, 200);
        }

        $name = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($this->request->str('name') ?? $label)));
        if (!$name) {
            $errors[] = 'Name/slug is required.';
        } else {
            if ($name !== $existingName) {
                $dup = $this->db->fetchScalar("SELECT id FROM roles WHERE name=?", [$name]);
                if ($dup) {
                    $errors[] = 'That role name already exists.';
                }
            }
            $data['name'] = substr($name, 0, 100);
        }

        $data['description'] = trim($this->request->str('description') ?? '') ?: null;

        return [$data, $errors];
    }

    private function syncPermissions(int $roleId): void
    {
        $permIds = $this->request->all()['permission_ids'] ?? [];
        if (!is_array($permIds)) {
            $permIds = [];
        }

        $permIds = array_map('intval', array_filter($permIds));

        $this->db->execute("DELETE FROM role_permissions WHERE role_id=?", [$roleId]);
        foreach ($permIds as $permId) {
            if ($permId > 0) {
                $this->db->execute(
                    "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)",
                    [$roleId, $permId]
                );
            }
        }
    }

    private function findRole(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM roles WHERE id=?", [$id]) ?: null;
    }
}
