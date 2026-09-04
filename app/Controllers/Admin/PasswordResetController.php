<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\RateLimiter;
use Unwinded\Core\View;
use Unwinded\Support\Token;

class PasswordResetController
{
    private const TOKEN_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private Database    $db,
        private Request     $request,
        private RateLimiter $rateLimiter,
        private View        $view,
    ) {}

    /** Show forgot-password form */
    public function showForgot(): Response
    {
        return Response::make()->html(
            $this->view->render('admin/auth/forgot-password', [])
        );
    }

    /** Handle forgot-password submission */
    public function sendResetLink(): Response
    {
        $ip = $this->request->ip();

        // 5 reset attempts per hour per IP
        if (!$this->rateLimiter->attempt("pwd_reset:{$ip}", 5, 3600)) {
            flash('error', 'Too many attempts. Please wait an hour and try again.');
            return Response::make()->redirect(url('/admin/password/reset'));
        }

        $email = strtolower(trim($this->request->str('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::make()->html(
                $this->view->render('admin/auth/forgot-password', [
                    'errors' => ['email' => 'Please enter a valid email address.'],
                ])
            );
        }

        // Always show the same success message to prevent email enumeration
        flash('success', 'If that email address is on file, you\'ll receive a reset link shortly.');

        $user = $this->db->fetchOne(
            "SELECT id, name, email FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL",
            [$email]
        );

        if (!$user) {
            return Response::make()->redirect(url('/admin/password/reset'));
        }

        // Invalidate any existing unused tokens for this email
        $this->db->execute(
            "UPDATE password_resets SET used_at = NOW() WHERE email = ? AND used_at IS NULL",
            [$email]
        );

        $token      = Token::generate();
        $expiresAt  = gmdate('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);

        $this->db->insert('password_resets', [
            'email'      => $email,
            'token_hash' => $token['hash'],
            'expires_at' => $expiresAt,
        ]);

        $resetUrl = url('/admin/password/reset/' . urlencode($token['raw']));

        // Queue the reset email
        $this->db->insert('email_queue', [
            'to_address'    => $email,
            'to_name'       => $user['name'],
            'subject'       => 'Reset your Unwinded admin password',
            'body_html'     => $this->buildResetEmail($user['name'], $resetUrl),
            'template_slug' => 'admin_password_reset',
            'priority'      => 1, // high priority
            'available_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        return Response::make()->redirect(url('/admin/password/reset'));
    }

    /** Show reset-password form (user arrived via email link) */
    public function showReset(string $rawToken): Response
    {
        $tokenHash = hash('sha256', base64_decode($rawToken));
        $record    = $this->findValidToken($tokenHash);

        if (!$record) {
            flash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            return Response::make()->redirect(url('/admin/password/reset'));
        }

        return Response::make()->html(
            $this->view->render('admin/auth/reset-password', [
                'rawToken' => $rawToken,
            ])
        );
    }

    /** Handle reset-password form submission */
    public function resetPassword(string $rawToken): Response
    {
        $tokenHash = hash('sha256', base64_decode($rawToken));
        $record    = $this->findValidToken($tokenHash);

        if (!$record) {
            flash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            return Response::make()->redirect(url('/admin/password/reset'));
        }

        $password        = $this->request->str('password');
        $passwordConfirm = $this->request->str('password_confirmation');
        $errors          = [];

        if (strlen($password) < 10) {
            $errors['password'] = 'Password must be at least 10 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return Response::make()->html(
                $this->view->render('admin/auth/reset-password', [
                    'rawToken' => $rawToken,
                    'errors'   => $errors,
                ])
            );
        }

        $this->db->transaction(function () use ($record, $password, $tokenHash): void {
            $this->db->execute(
                "UPDATE users SET password_hash = ? WHERE email = ?",
                [password_hash($password, PASSWORD_ARGON2ID), $record['email']]
            );
            $this->db->execute(
                "UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?",
                [$tokenHash]
            );
        });

        flash('success', 'Your password has been reset. Please log in with your new password.');
        return Response::make()->redirect(url('/admin/login'));
    }

    private function findValidToken(string $hash): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM password_resets
             WHERE token_hash = ?
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1",
            [$hash]
        ) ?: null;
    }

    private function buildResetEmail(string $name, string $resetUrl): string
    {
        $e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return <<<HTML
<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:40px 20px;">
<h2 style="color:#2c1810;">Password Reset Request</h2>
<p>Hi {$e($name)},</p>
<p>Someone requested a password reset for your Unwinded admin account.
If this was you, click the button below. This link expires in 1 hour.</p>
<p style="text-align:center;margin:32px 0;">
  <a href="{$e($resetUrl)}"
     style="display:inline-block;background:#2c1810;color:#d4a853;text-decoration:none;
            padding:14px 32px;border-radius:4px;font-size:16px;">
    Reset My Password
  </a>
</p>
<p style="color:#888;font-size:13px;">If you did not request this, ignore this email — your account remains secure.</p>
<p style="color:#888;font-size:13px;">Or copy this URL into your browser:<br>{$e($resetUrl)}</p>
</body></html>
HTML;
    }
}
