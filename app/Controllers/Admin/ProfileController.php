<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Auth;
use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class ProfileController
{
    public function __construct(
        private Auth           $auth,
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function show(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/profile/edit', [
                'pageTitle' => 'My Profile',
                'user'      => $this->auth->user(),
            ])
        );
    }

    public function update(): Response
    {
        $user   = $this->auth->user();
        $name   = trim($this->request->str('name'));
        $errors = [];

        if (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        }

        if (!empty($errors)) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/profile/edit', [
                    'pageTitle' => 'My Profile',
                    'user'      => $user,
                    'errors'    => $errors,
                ])
            );
        }

        $this->db->execute(
            "UPDATE users SET name = ? WHERE id = ?",
            [$name, $user['id']]
        );

        $this->activityLogger->log('profile.updated', 'user', $user['id']);

        flash('success', 'Profile updated.');
        return Response::make()->redirect(url('/admin/profile'));
    }

    public function changePassword(): Response
    {
        $user            = $this->auth->user();
        $current         = $this->request->str('current_password');
        $password        = $this->request->str('password');
        $passwordConfirm = $this->request->str('password_confirmation');
        $errors          = [];

        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (strlen($password) < 10) {
            $errors['password'] = 'New password must be at least 10 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/profile/edit', [
                    'pageTitle' => 'My Profile',
                    'user'      => $user,
                    'errors'    => $errors,
                    'tab'       => 'password',
                ])
            );
        }

        $this->db->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [password_hash($password, PASSWORD_ARGON2ID), $user['id']]
        );

        $this->activityLogger->log('password.changed', 'user', $user['id']);

        // Force re-login with new credentials
        $this->auth->logout();
        flash('success', 'Password changed. Please log in again with your new password.');
        return Response::make()->redirect(url('/admin/login'));
    }
}
