<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Support\Str;

class ExperienceController
{
    private const TYPES = ['corporate', 'restaurant', 'private', 'other'];

    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $experiences = $this->db->fetchAll(
            "SELECT * FROM experiences ORDER BY sort_order, title"
        );
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/experiences/index', [
                'pageTitle'   => 'Experiences',
                'experiences' => $experiences,
            ])
        );
    }

    public function edit(string $id): Response
    {
        $exp = $this->findOrFail((int) $id);
        if (!$exp) {
            flash('error', 'Experience not found.');
            return Response::make()->redirect(url('/admin/experiences'));
        }
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/experiences/edit', [
                'pageTitle'  => 'Edit Experience',
                'experience' => $exp,
                'types'      => self::TYPES,
            ])
        );
    }

    public function update(string $id): Response
    {
        $exp = $this->findOrFail((int) $id);
        if (!$exp) {
            flash('error', 'Experience not found.');
            return Response::make()->redirect(url('/admin/experiences'));
        }

        $title  = trim($this->request->str('title'));
        $errors = [];

        if (strlen($title) < 2) {
            $errors['title'] = 'Title must be at least 2 characters.';
        }

        $type = $this->request->str('type');
        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Invalid experience type.';
        }

        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/experiences/edit', [
                    'pageTitle'  => 'Edit Experience',
                    'experience' => array_merge($exp, $this->request->all()),
                    'errors'     => $errors,
                    'types'      => self::TYPES,
                ])
            );
        }

        $slug = Str::slug($this->request->str('slug') ?: $title);

        $this->db->execute(
            "UPDATE experiences SET
                title=?, slug=?, type=?, short_desc=?, body=?,
                cta_text=?, cta_url=?, meta_title=?, meta_description=?,
                is_active=?, sort_order=?, updated_at=NOW()
             WHERE id=?",
            [
                $title,
                $slug,
                $type,
                substr(trim($this->request->str('short_desc')), 0, 65535),
                $this->request->str('body'),
                substr(trim($this->request->str('cta_text')), 0, 200),
                substr(trim($this->request->str('cta_url')),  0, 500),
                substr(trim($this->request->str('meta_title')), 0, 500),
                substr(trim($this->request->str('meta_description')), 0, 500),
                $this->request->str('is_active') ? 1 : 0,
                (int) ($this->request->str('sort_order') ?? '0'),
                (int) $id,
            ]
        );

        $this->activityLogger->log('experience.updated', 'experience', $id);
        flash('success', 'Experience updated.');
        return Response::make()->redirect(url('/admin/experiences/' . $id . '/edit'));
    }

    private function findOrFail(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM experiences WHERE id = ?", [$id]) ?: null;
    }
}
