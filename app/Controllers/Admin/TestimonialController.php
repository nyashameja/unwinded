<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class TestimonialController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $testimonials = $this->db->fetchAll(
            "SELECT t.*, m.public_url AS photo_url
               FROM testimonials t
               LEFT JOIN media m ON m.id = t.photo_media_id AND m.deleted_at IS NULL
              WHERE t.deleted_at IS NULL
             ORDER BY t.is_featured DESC, t.sort_order ASC, t.created_at DESC"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/testimonials/index', [
                'pageTitle'    => 'Testimonials',
                'testimonials' => $testimonials,
            ])
        );
    }

    public function create(): Response
    {
        $media = $this->db->fetchAll(
            "SELECT id, public_url, file_name FROM media WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 200"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/testimonials/create', [
                'pageTitle' => 'New Testimonial',
                'media'     => $media,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            $media = $this->db->fetchAll("SELECT id, public_url, file_name FROM media WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 200");
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/testimonials/create', [
                    'pageTitle' => 'New Testimonial',
                    'media'     => $media,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        $this->db->execute(
            "INSERT INTO testimonials
                (customer_name,customer_title,body,rating,event_type,photo_media_id,
                 is_featured,is_published,sort_order,published_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,IF(?=1,NOW(),NULL),NOW(),NOW())",
            [
                $data['customer_name'], $data['customer_title'], $data['body'],
                $data['rating'], $data['event_type'], $data['photo_media_id'],
                $data['is_featured'], $data['is_published'], $data['sort_order'],
                $data['is_published'],
            ]
        );
        $id = (int) $this->db->lastInsertId();

        $this->activityLogger->log('testimonial.created', 'testimonial', (string) $id);
        flash('success', 'Testimonial created.');
        return Response::make()->redirect(url('/admin/testimonials'));
    }

    public function edit(string $id): Response
    {
        $t = $this->findOrFail((int) $id);
        if (!$t) {
            flash('error', 'Testimonial not found.');
            return Response::make()->redirect(url('/admin/testimonials'));
        }

        $media = $this->db->fetchAll("SELECT id, public_url, file_name FROM media WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 200");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/testimonials/edit', [
                'pageTitle'   => 'Edit Testimonial',
                'testimonial' => $t,
                'media'       => $media,
                'old'         => [],
                'errors'      => [],
            ])
        );
    }

    public function update(string $id): Response
    {
        $t = $this->findOrFail((int) $id);
        if (!$t) {
            flash('error', 'Testimonial not found.');
            return Response::make()->redirect(url('/admin/testimonials'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            $media = $this->db->fetchAll("SELECT id, public_url, file_name FROM media WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 200");
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/testimonials/edit', [
                    'pageTitle'   => 'Edit Testimonial',
                    'testimonial' => $t,
                    'media'       => $media,
                    'old'         => $this->request->all(),
                    'errors'      => $errors,
                ])
            );
        }

        $this->db->execute(
            "UPDATE testimonials
             SET customer_name=?,customer_title=?,body=?,rating=?,event_type=?,photo_media_id=?,
                 is_featured=?,is_published=?,sort_order=?,
                 published_at=IF(?=1 AND published_at IS NULL,NOW(),published_at),
                 updated_at=NOW()
             WHERE id=?",
            [
                $data['customer_name'], $data['customer_title'], $data['body'],
                $data['rating'], $data['event_type'], $data['photo_media_id'],
                $data['is_featured'], $data['is_published'], $data['sort_order'],
                $data['is_published'], (int) $id,
            ]
        );

        $this->activityLogger->log('testimonial.updated', 'testimonial', $id);
        flash('success', 'Testimonial updated.');
        return Response::make()->redirect(url('/admin/testimonials/' . $id . '/edit'));
    }

    public function destroy(string $id): Response
    {
        $this->db->execute("UPDATE testimonials SET deleted_at=NOW() WHERE id=?", [(int) $id]);
        $this->activityLogger->log('testimonial.deleted', 'testimonial', $id);
        flash('success', 'Testimonial deleted.');
        return Response::make()->redirect(url('/admin/testimonials'));
    }

    private function validate(): array
    {
        $post   = $this->request->all();
        $errors = [];

        $name = trim((string) ($post['customer_name'] ?? ''));
        if (strlen($name) < 2) {
            $errors['customer_name'] = 'Customer name is required.';
        }

        $body = trim((string) ($post['body'] ?? ''));
        if (strlen($body) < 10) {
            $errors['body'] = 'Testimonial body must be at least 10 characters.';
        }

        $rating = (int) ($post['rating'] ?? 5);
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $data = [
            'customer_name'  => substr($name, 0, 150),
            'customer_title' => substr(trim((string) ($post['customer_title'] ?? '')), 0, 150),
            'body'           => $body,
            'rating'         => $rating,
            'event_type'     => substr(trim((string) ($post['event_type'] ?? '')), 0, 100),
            'photo_media_id' => (int) ($post['photo_media_id'] ?? 0) ?: null,
            'is_featured'    => isset($post['is_featured']) ? 1 : 0,
            'is_published'   => isset($post['is_published']) ? 1 : 0,
            'sort_order'     => max(0, (int) ($post['sort_order'] ?? 0)),
        ];

        return [$data, $errors];
    }

    private function findOrFail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT t.*, m.public_url AS photo_url FROM testimonials t LEFT JOIN media m ON m.id = t.photo_media_id WHERE t.id=? AND t.deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
