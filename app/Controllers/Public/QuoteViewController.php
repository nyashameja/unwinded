<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * Customer-facing quote view — full implementation is Phase 6.
 * Token-based access; no login required.
 */
class QuoteViewController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function show(string $ref, string $token): Response
    {
        $quote = $this->resolveQuote($ref, $token);
        if (!$quote) {
            return $this->notFound();
        }
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/quote/view', [
                'pageTitle'  => 'Your Quote — ' . $quote['public_ref'],
                'metaRobots' => 'noindex,nofollow',
                'quote'      => $quote,
                'token'      => $token,
                'bodyClass'  => 'page page--quote-view',
            ])
        );
    }

    public function accept(string $ref, string $token): Response
    {
        $quote = $this->resolveQuote($ref, $token);
        if (!$quote || $quote['status'] !== 'sent') {
            flash('error', 'This quote is no longer available for acceptance.');
            return Response::make()->redirect(url("/quote/{$ref}/{$token}"));
        }

        $this->db->execute(
            "UPDATE quotes SET status = 'accepted', accepted_at = NOW(), customer_ip = ? WHERE id = ?",
            [$this->request->ip(), $quote['id']]
        );
        flash('success', 'Quote accepted! We\'ll be in touch shortly to confirm your booking.');
        return Response::make()->redirect(url("/quote/{$ref}/{$token}"));
    }

    public function decline(string $ref, string $token): Response
    {
        $quote = $this->resolveQuote($ref, $token);
        if (!$quote || $quote['status'] !== 'sent') {
            flash('error', 'This quote is no longer available.');
            return Response::make()->redirect(url("/quote/{$ref}/{$token}"));
        }

        $this->db->execute(
            "UPDATE quotes SET status = 'declined', declined_at = NOW() WHERE id = ?",
            [$quote['id']]
        );
        flash('info', 'Quote declined. If you change your mind, please get in touch.');
        return Response::make()->redirect(url("/quote/{$ref}/{$token}"));
    }

    private function resolveQuote(string $ref, string $token): ?array
    {
        $tokenHash = hash('sha256', base64_decode($token));
        return $this->db->fetchOne(
            "SELECT q.* FROM quotes q
             JOIN quote_access_tokens qat ON qat.quote_id = q.id
             WHERE q.public_ref = ?
               AND qat.token_hash = ?
               AND (qat.expires_at IS NULL OR qat.expires_at > NOW())",
            [$ref, $tokenHash]
        ) ?: null;
    }

    private function notFound(): Response
    {
        return Response::make()->status(404)->html(
            $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Not Found'])
        );
    }
}
