<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Auth;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\RateLimiter;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class AuthController
{
    public function __construct(
        private Auth           $auth,
        private Request        $request,
        private RateLimiter    $rateLimiter,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function showLogin(): Response
    {
        return Response::make()->html(
            $this->view->render('admin/auth/login', [])
        );
    }

    public function login(): Response
    {
        $ip = $this->request->ip();

        // 10 attempts per 15 minutes per IP
        if (!$this->rateLimiter->attempt("login:{$ip}", 10, 15 * 60)) {
            flash('error', 'Too many login attempts. Please wait 15 minutes and try again.');
            return Response::make()->redirect(url('/admin/login'));
        }

        $email    = $this->request->str('email');
        $password = $this->request->str('password');
        $errors   = [];

        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin/auth/login', 'admin/auth/login', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ])
            );
        }

        if (!$this->auth->attempt($email, $password, $ip)) {
            // Deliberate vague message — don't leak whether email exists
            $errors['auth'] = 'These credentials do not match our records.';
            return Response::make()->html(
                $this->view->renderWithLayout('admin/auth/login', 'admin/auth/login', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ])
            );
        }

        // Login successful — update last_login_at and log activity
        $user = $this->auth->user();
        app('db')->execute(
            "UPDATE users SET last_login_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        $this->activityLogger->log('login', 'user', $user['id'], ['ip' => $ip]);

        $intended = app('session')->get('_intended_url', url('/admin'));
        app('session')->forget('_intended_url');

        flash('success', 'Welcome back, ' . $user['name'] . '!');
        return Response::make()->redirect($intended);
    }

    public function logout(): Response
    {
        $user = $this->auth->user();
        if ($user) {
            $this->activityLogger->log('logout', 'user', $user['id']);
        }
        $this->auth->logout();
        flash('success', 'You have been logged out.');
        return Response::make()->redirect(url('/admin/login'));
    }
}
