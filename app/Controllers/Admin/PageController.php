<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Auth;
use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Support\Str;

class PageController
{
    public function __construct(
        private Auth           $auth,
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $pages = $this->db->fetchAll(
            "SELECT p.*, u.name AS author_name
             FROM pages p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.deleted_at IS NULL
             ORDER BY p.sort_order, p.title"
        );
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/pages/index', [
                'pageTitle' => 'Pages',
                'pages'     => $pages,
            ])
        );
    }

    public function create(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/pages/create', [
                'pageTitle' => 'New Page',
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate($this->request->all());
        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/pages/create', [
                    'pageTitle' => 'New Page',
                    'errors'    => $errors,
                    'old'       => $this->request->all(),
                ])
            );
        }

        $user = $this->auth->user();
        $id   = $this->db->insert('pages', [
            'slug'             => $data['slug'],
            'title'            => $data['title'],
            'meta_description' => $data['meta_description'],
            'content'          => $data['content'],
            'template'         => $data['template'],
            'status'           => 'draft',
            'created_by'       => $user['id'] ?? null,
        ]);

        $this->saveRevision($id, $data, $user['id'] ?? null);
        $this->activityLogger->log('page.created', 'page', $id);

        flash('success', 'Page created.');
        return Response::make()->redirect(url('/admin/pages/' . $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $page = $this->findPage((int) $id);
        if (!$page) {
            flash('error', 'Page not found.');
            return Response::make()->redirect(url('/admin/pages'));
        }

        $revisions = $this->db->fetchAll(
            "SELECT pr.*, u.name AS author_name FROM page_revisions pr
             LEFT JOIN users u ON u.id = pr.saved_by
             WHERE pr.page_id = ? ORDER BY pr.created_at DESC LIMIT 10",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/pages/edit', [
                'pageTitle' => 'Edit Page',
                'page'      => $page,
                'revisions' => $revisions,
            ])
        );
    }

    public function update(string $id): Response
    {
        $page = $this->findPage((int) $id);
        if (!$page) {
            flash('error', 'Page not found.');
            return Response::make()->redirect(url('/admin/pages'));
        }

        [$data, $errors] = $this->validate($this->request->all(), (int) $id);
        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/pages/edit', [
                    'pageTitle' => 'Edit Page',
                    'page'      => array_merge($page, $this->request->all()),
                    'errors'    => $errors,
                    'revisions' => [],
                ])
            );
        }

        $user = $this->auth->user();
        $this->db->execute(
            "UPDATE pages SET slug=?, title=?, meta_description=?, content=?, template=?, updated_at=NOW()
             WHERE id=?",
            [$data['slug'], $data['title'], $data['meta_description'], $data['content'], $data['template'], (int) $id]
        );

        $this->saveRevision((int) $id, $data, $user['id'] ?? null);
        $this->activityLogger->log('page.updated', 'page', $id);

        flash('success', 'Page saved.');
        return Response::make()->redirect(url('/admin/pages/' . $id . '/edit'));
    }

    public function destroy(string $id): Response
    {
        $page = $this->findPage((int) $id);
        if (!$page || $page['is_system']) {
            flash('error', $page && $page['is_system'] ? 'System pages cannot be deleted.' : 'Page not found.');
            return Response::make()->redirect(url('/admin/pages'));
        }

        $this->db->execute("UPDATE pages SET deleted_at=NOW() WHERE id=?", [(int) $id]);
        $this->activityLogger->log('page.deleted', 'page', $id);

        flash('success', 'Page deleted.');
        return Response::make()->redirect(url('/admin/pages'));
    }

    public function publish(string $id): Response
    {
        $page = $this->findPage((int) $id);
        if (!$page) {
            flash('error', 'Page not found.');
            return Response::make()->redirect(url('/admin/pages'));
        }

        $this->db->execute(
            "UPDATE pages SET status='published', published_at=COALESCE(published_at, NOW()), updated_at=NOW() WHERE id=?",
            [(int) $id]
        );
        $this->activityLogger->log('page.published', 'page', $id);

        flash('success', 'Page published.');
        return Response::make()->redirect(url('/admin/pages/' . $id . '/edit'));
    }

    public function unpublish(string $id): Response
    {
        $page = $this->findPage((int) $id);
        if (!$page) {
            flash('error', 'Page not found.');
            return Response::make()->redirect(url('/admin/pages'));
        }

        $this->db->execute(
            "UPDATE pages SET status='draft', updated_at=NOW() WHERE id=?",
            [(int) $id]
        );
        $this->activityLogger->log('page.unpublished', 'page', $id);

        flash('success', 'Page reverted to draft.');
        return Response::make()->redirect(url('/admin/pages/' . $id . '/edit'));
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function findPage(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM pages WHERE id = ? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }

    private function validate(array $input, ?int $excludeId = null): array
    {
        $title  = trim($input['title'] ?? '');
        $slug   = trim($input['slug'] ?? '');
        $errors = [];

        if (strlen($title) < 2) {
            $errors['title'] = 'Title must be at least 2 characters.';
        }
        if ($slug === '') {
            $slug = Str::slug($title);
        } else {
            $slug = Str::slug($slug);
        }
        if (strlen($slug) < 2) {
            $errors['slug'] = 'Slug is too short.';
        }

        // Uniqueness check
        if (!isset($errors['slug'])) {
            $existing = $this->db->fetchScalar(
                "SELECT id FROM pages WHERE slug = ? AND deleted_at IS NULL" . ($excludeId ? " AND id != {$excludeId}" : ""),
                [$slug]
            );
            if ($existing) {
                $errors['slug'] = 'A page with this slug already exists.';
            }
        }

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'meta_description' => substr(trim($input['meta_description'] ?? ''), 0, 500),
            'content'          => $input['content'] ?? '',
            'template'         => in_array($input['template'] ?? '', ['default', 'full-width', 'minimal'], true)
                                  ? $input['template']
                                  : 'default',
        ];

        return [$data, $errors];
    }

    private function saveRevision(int $pageId, array $data, ?int $userId): void
    {
        $this->db->insert('page_revisions', [
            'page_id'  => $pageId,
            'title'    => $data['title'],
            'content'  => $data['content'],
            'saved_by' => $userId,
        ]);
    }
}
