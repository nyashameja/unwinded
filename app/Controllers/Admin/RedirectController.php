<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class RedirectController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $redirects = $this->db->fetchAll(
            "SELECT * FROM redirects ORDER BY hit_count DESC, source_url"
        );
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/redirects/index', [
                'pageTitle' => 'URL Redirects',
                'redirects' => $redirects,
            ])
        );
    }

    public function store(): Response
    {
        $source = trim($this->request->str('source_url'));
        $target = trim($this->request->str('target_url'));
        $code   = (int) ($this->request->str('status_code') ?? '301');
        $errors = [];

        if (!str_starts_with($source, '/')) {
            $errors['source_url'] = 'Source URL must start with /.';
        }
        if (empty($target)) {
            $errors['target_url'] = 'Target URL is required.';
        }
        if (!in_array($code, [301, 302, 307, 308], true)) {
            $code = 301;
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            return Response::make()->redirect(url('/admin/redirects'));
        }

        $existing = $this->db->fetchScalar(
            "SELECT id FROM redirects WHERE source_url = ?", [$source]
        );
        if ($existing) {
            $this->db->execute(
                "UPDATE redirects SET target_url=?, status_code=?, is_active=1 WHERE source_url=?",
                [$target, $code, $source]
            );
        } else {
            $this->db->insert('redirects', [
                'source_url'  => substr($source, 0, 500),
                'target_url'  => substr($target, 0, 500),
                'status_code' => $code,
            ]);
        }
        $this->activityLogger->log('redirect.created', 'redirect');

        flash('success', 'Redirect saved.');
        return Response::make()->redirect(url('/admin/redirects'));
    }

    public function destroy(string $id): Response
    {
        $this->db->execute("DELETE FROM redirects WHERE id = ?", [(int) $id]);
        $this->activityLogger->log('redirect.deleted', 'redirect', $id);

        flash('success', 'Redirect removed.');
        return Response::make()->redirect(url('/admin/redirects'));
    }
}
