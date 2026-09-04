<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class FaqController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $groups = $this->db->fetchAll("SELECT * FROM faq_groups ORDER BY sort_order");
        $faqs   = $this->db->fetchAll(
            "SELECT f.*, g.name AS group_name
               FROM faqs f
               LEFT JOIN faq_groups g ON g.id = f.group_id
              WHERE f.deleted_at IS NULL
             ORDER BY f.group_id, f.sort_order"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/faqs/index', [
                'pageTitle' => 'FAQs',
                'groups'    => $groups,
                'faqs'      => $faqs,
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            flash('error', implode(' ', $errors));
            return Response::make()->redirect(url('/admin/faqs'));
        }

        $this->db->execute(
            "INSERT INTO faqs (group_id,question,answer,is_featured,is_published,sort_order,created_at,updated_at)
             VALUES (?,?,?,?,?,?,NOW(),NOW())",
            [$data['group_id'], $data['question'], $data['answer'], $data['is_featured'], $data['is_published'], $data['sort_order']]
        );
        $id = (int) $this->db->lastInsertId();

        // Ensure group exists if new group name provided
        $newGroup = trim($this->request->str('new_group_name') ?? '');
        if ($newGroup && !$data['group_id']) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $newGroup));
            $this->db->execute(
                "INSERT IGNORE INTO faq_groups (name, slug, sort_order, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())",
                [$newGroup, substr($slug, 0, 160)]
            );
            $gid = (int) $this->db->lastInsertId();
            if ($gid) {
                $this->db->execute("UPDATE faqs SET group_id=? WHERE id=?", [$gid, $id]);
            }
        }

        $this->activityLogger->log('faq.created', 'faq', (string) $id);
        flash('success', 'FAQ added.');
        return Response::make()->redirect(url('/admin/faqs'));
    }

    public function update(string $id): Response
    {
        $faq = $this->db->fetchOne("SELECT * FROM faqs WHERE id=? AND deleted_at IS NULL", [(int) $id]);
        if (!$faq) {
            flash('error', 'FAQ not found.');
            return Response::make()->redirect(url('/admin/faqs'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            flash('error', implode(' ', $errors));
            return Response::make()->redirect(url('/admin/faqs'));
        }

        $this->db->execute(
            "UPDATE faqs SET group_id=?,question=?,answer=?,is_featured=?,is_published=?,sort_order=?,updated_at=NOW() WHERE id=?",
            [$data['group_id'], $data['question'], $data['answer'], $data['is_featured'], $data['is_published'], $data['sort_order'], (int) $id]
        );

        $this->activityLogger->log('faq.updated', 'faq', $id);
        flash('success', 'FAQ updated.');
        return Response::make()->redirect(url('/admin/faqs'));
    }

    public function destroy(string $id): Response
    {
        $this->db->execute("UPDATE faqs SET deleted_at=NOW() WHERE id=?", [(int) $id]);
        $this->activityLogger->log('faq.deleted', 'faq', $id);
        flash('success', 'FAQ deleted.');
        return Response::make()->redirect(url('/admin/faqs'));
    }

    private function validate(): array
    {
        $post   = $this->request->all();
        $errors = [];

        $question = trim((string) ($post['question'] ?? ''));
        if (strlen($question) < 5) {
            $errors['question'] = 'Question is required.';
        }

        $answer = trim((string) ($post['answer'] ?? ''));
        if (strlen($answer) < 5) {
            $errors['answer'] = 'Answer is required.';
        }

        $data = [
            'group_id'    => (int) ($post['group_id'] ?? 0) ?: null,
            'question'    => substr($question, 0, 500),
            'answer'      => $answer,
            'is_featured' => isset($post['is_featured']) ? 1 : 0,
            'is_published'=> isset($post['is_published']) ? 1 : 0,
            'sort_order'  => max(0, (int) ($post['sort_order'] ?? 0)),
        ];

        return [$data, $errors];
    }
}
