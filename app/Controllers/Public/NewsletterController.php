<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\RateLimiter;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;
use Unwinded\Support\Token;

class NewsletterController
{
    public function __construct(
        private Database    $db,
        private Request     $request,
        private View        $view,
        private RateLimiter $rateLimiter,
        private MailService $mail,
    ) {}

    public function subscribe(): Response
    {
        $email = strtolower(trim($this->request->str('email') ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
            return Response::make()->redirect($this->referer());
        }

        if ($this->rateLimiter->tooManyAttempts('newsletter:' . $this->request->ip(), 5, 60)) {
            flash('success', 'Check your inbox — we\'ve sent a confirmation email.');
            return Response::make()->redirect($this->referer());
        }

        $existing = $this->db->fetchOne(
            "SELECT * FROM newsletter_subscribers WHERE email = ?",
            [$email]
        );

        if ($existing && $existing['status'] === 'confirmed') {
            flash('success', 'You\'re already subscribed!');
            return Response::make()->redirect($this->referer());
        }

        // Generate confirmation token
        $tokenPair = Token::generate();  // ['raw' => hex, 'hash' => sha256]
        $rawToken  = $tokenPair['raw'];
        $tokenHash = $tokenPair['hash'];
        $expires   = date('Y-m-d H:i:s', strtotime('+48 hours'));

        if ($existing) {
            $this->db->execute(
                "UPDATE newsletter_subscribers
                 SET status = 'pending', confirm_token_hash = ?, confirm_token_expires_at = ?,
                     ip_address = ?, updated_at = NOW()
                 WHERE id = ?",
                [$tokenHash, $expires, $this->request->ip(), $existing['id']]
            );
        } else {
            $name = substr(trim($this->request->str('name') ?? ''), 0, 150);
            $this->db->insert('newsletter_subscribers', [
                'email'                     => $email,
                'name'                      => $name ?: null,
                'status'                    => 'pending',
                'confirm_token_hash'        => $tokenHash,
                'confirm_token_expires_at'  => $expires,
                'ip_address'                => $this->request->ip(),
                'source'                    => 'website_footer',
            ]);
        }

        $confirmUrl = url('/newsletter/confirm/' . urlencode($rawToken));
        try {
            $this->mail->send(
                $email,
                '',
                'Confirm your subscription — ' . config('app.name'),
                "<p>Thanks for subscribing! Please confirm your email address:</p>" .
                "<p><a href=\"" . e($confirmUrl) . "\">" . e($confirmUrl) . "</a></p>" .
                "<p>This link expires in 48 hours.</p>"
            );
        } catch (\Throwable) {
            // Token is in DB; user can request again
        }

        flash('success', 'Almost done! Check your email and click the confirmation link.');
        return Response::make()->redirect($this->referer());
    }

    public function confirm(string $token): Response
    {
        $tokenHash = Token::hash(urldecode($token));
        $sub = $this->db->fetchOne(
            "SELECT * FROM newsletter_subscribers
             WHERE confirm_token_hash = ?
               AND (confirm_token_expires_at IS NULL OR confirm_token_expires_at > NOW())",
            [$tokenHash]
        );

        if (!$sub) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/newsletter/confirm', [
                    'pageTitle'  => 'Subscription Confirmation',
                    'metaRobots' => 'noindex,nofollow',
                    'success'    => false,
                ])
            );
        }

        // Build unsubscribe token
        $unsubPair = Token::generate();
        $unsubHash = $unsubPair['hash'];

        $this->db->execute(
            "UPDATE newsletter_subscribers
             SET status = 'confirmed', confirmed_at = NOW(),
                 unsubscribe_token_hash = ?, confirm_token_hash = NULL, confirm_token_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = ?",
            [$unsubHash, $sub['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/newsletter/confirm', [
                'pageTitle'  => 'Subscription Confirmed',
                'metaRobots' => 'noindex,nofollow',
                'success'    => true,
            ])
        );
    }

    public function unsubscribe(string $token): Response
    {
        $tokenHash = Token::hash(urldecode($token));
        $sub = $this->db->fetchOne(
            "SELECT * FROM newsletter_subscribers WHERE unsubscribe_token_hash = ?",
            [$tokenHash]
        );

        $done = false;
        if ($sub && $sub['status'] !== 'unsubscribed') {
            $this->db->execute(
                "UPDATE newsletter_subscribers
                 SET status = 'unsubscribed', unsubscribed_at = NOW(), updated_at = NOW()
                 WHERE id = ?",
                [$sub['id']]
            );
            $done = true;
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/newsletter/unsubscribe', [
                'pageTitle'  => 'Unsubscribed',
                'metaRobots' => 'noindex,nofollow',
                'done'       => $done,
            ])
        );
    }

    private function referer(): string
    {
        $ref = $this->request->header('Referer') ?? '';
        $appUrl = rtrim(config('app.url', ''), '/');
        if ($ref && str_starts_with($ref, $appUrl)) {
            return $ref;
        }
        return url('/');
    }
}
