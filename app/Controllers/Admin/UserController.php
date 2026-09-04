<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Support\Ref;

class UserController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $users = $this->db->fetchAll(
            "SELECT u.*, GROUP_CONCAT(r.label ORDER BY r.name SEPARATOR ', ') AS role_labels
               FROM users u
               LEFT JOIN user_roles ur ON ur.user_id = u.id
               LEFT JOIN roles r ON r.id = ur.role_id
              WHERE u.deleted_at IS NULL
             GROUP BY u.id
             ORDER BY u.name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/users/index', [
                'pageTitle' => 'Admin Users',
                'users'     => $users,
            ])
        );
    }

    public function create(): Response
    {
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY label");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/users/create', [
                'pageTitle' => 'New User',
                'roles'     => $roles,
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
            return Response::make()->redirect(url('/admin/users/create'));
        }

        $password = $this->request->str('password') ?? '';
        $confirm  = $this->request->str('password_confirm') ?? '';

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            return Response::make()->redirect(url('/admin/users/create'));
        }
        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            return Response::make()->redirect(url('/admin/users/create'));
        }

        $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        $data['public_ref']    = Ref::generate('USR');

        $id = $this->db->insert('users', $data);
        $this->syncRoles((int) $id);

        flash('success', 'User created.');
        return Response::make()->redirect(url('/admin/users/' . (int) $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $user = $this->findUser((int) $id);
        if (!$user) {
            flash('error', 'User not found.');
            return Response::make()->redirect(url('/admin/users'));
        }

        $roles       = $this->db->fetchAll("SELECT * FROM roles ORDER BY label");
        $userRoleIds = array_column(
            $this->db->fetchAll("SELECT role_id FROM user_roles WHERE user_id=?", [(int) $id]),
            'role_id'
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/users/edit', [
                'pageTitle'   => 'Edit User',
                'user'        => $user,
                'roles'       => $roles,
                'userRoleIds' => $userRoleIds,
            ])
        );
    }

    public function update(string $id): Response
    {
        $user = $this->findUser((int) $id);
        if (!$user) {
            flash('error', 'User not found.');
            return Response::make()->redirect(url('/admin/users'));
        }

        [$data, $errors] = $this->validate($user['email']);

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/users/' . (int) $id . '/edit'));
        }

        $newPassword = $this->request->str('password') ?? '';
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('error', 'Password must be at least 8 characters.');
                return Response::make()->redirect(url('/admin/users/' . (int) $id . '/edit'));
            }
            if ($newPassword !== ($this->request->str('password_confirm') ?? '')) {
                flash('error', 'Passwords do not match.');
                return Response::make()->redirect(url('/admin/users/' . (int) $id . '/edit'));
            }
            $data['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
        $this->db->execute(
            "UPDATE users SET $set, updated_at=NOW() WHERE id=?",
            [...array_values($data), (int) $id]
        );

        $this->syncRoles((int) $id);

        // Prevent suspending the current user's own account
        if ((int) $id !== ($_SESSION['user']['id'] ?? 0)) {
            $isActive = (bool) $this->request->str('is_active');
            $this->db->execute(
                "UPDATE users SET is_active=? WHERE id=?",
                [$isActive ? 1 : 0, (int) $id]
            );
        }

        flash('success', 'User updated.');
        return Response::make()->redirect(url('/admin/users/' . (int) $id . '/edit'));
    }

    public function suspend(string $id): Response
    {
        if ((int) $id === ($_SESSION['user']['id'] ?? 0)) {
            flash('error', 'You cannot suspend your own account.');
            return Response::make()->redirect(url('/admin/users'));
        }

        $this->db->execute(
            "UPDATE users SET is_active=0, updated_at=NOW() WHERE id=?",
            [(int) $id]
        );

        flash('success', 'User suspended.');
        return Response::make()->redirect(url('/admin/users'));
    }

    private function validate(string $existingEmail = ''): array
    {
        $errors = [];
        $data   = [];

        $name = trim($this->request->str('name') ?? '');
        if (!$name) {
            $errors[] = 'Name is required.';
        } else {
            $data['name'] = substr($name, 0, 200);
        }

        $email = strtolower(trim($this->request->str('email') ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        } else {
            if ($email !== $existingEmail) {
                $dup = $this->db->fetchScalar(
                    "SELECT id FROM users WHERE email=? AND deleted_at IS NULL", [$email]
                );
                if ($dup) {
                    $errors[] = 'That email is already in use.';
                }
            }
            $data['email'] = $email;
        }

        return [$data, $errors];
    }

    private function syncRoles(int $userId): void
    {
        $roleIds = $this->request->all()['role_ids'] ?? [];
        if (!is_array($roleIds)) {
            $roleIds = [];
        }

        // Validate IDs are integers
        $roleIds = array_map('intval', array_filter($roleIds));

        $this->db->execute("DELETE FROM user_roles WHERE user_id=?", [$userId]);
        foreach ($roleIds as $roleId) {
            if ($roleId > 0) {
                $this->db->execute(
                    "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?,?)",
                    [$userId, $roleId]
                );
            }
        }
    }

    private function findUser(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
