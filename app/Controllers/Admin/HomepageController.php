<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class HomepageController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $sections = $this->db->fetchAll(
            "SELECT * FROM homepage_sections ORDER BY sort_order"
        );
        // Decode config JSON for each section
        foreach ($sections as &$s) {
            $s['config'] = $s['config'] ? json_decode($s['config'], true) : [];
        }
        unset($s);

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/homepage/index', [
                'pageTitle' => 'Homepage Sections',
                'sections'  => $sections,
            ])
        );
    }

    public function update(string $id): Response
    {
        $section = $this->db->fetchOne(
            "SELECT * FROM homepage_sections WHERE id = ?", [(int) $id]
        );
        if (!$section) {
            flash('error', 'Section not found.');
            return Response::make()->redirect(url('/admin/homepage'));
        }

        $existing = $section['config'] ? json_decode($section['config'], true) : [];
        $incoming = $this->request->all();
        unset($incoming['_csrf_token']);

        // Merge incoming fields into existing config, respecting booleans
        $isVisible = isset($incoming['is_visible']) ? 1 : 0;
        unset($incoming['is_visible']);

        $newConfig = array_merge($existing, $incoming);

        $this->db->execute(
            "UPDATE homepage_sections SET is_visible=?, config=?, updated_at=NOW() WHERE id=?",
            [$isVisible, json_encode($newConfig), (int) $id]
        );
        $this->activityLogger->log('homepage.section.updated', 'homepage_section', $id);

        flash('success', 'Section updated.');
        return Response::make()->redirect(url('/admin/homepage'));
    }

    public function reorder(): Response
    {
        $order = $this->request->all()['order'] ?? [];
        if (!is_array($order)) {
            return Response::make()->json(['error' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $sectionId) {
            $this->db->execute(
                "UPDATE homepage_sections SET sort_order=? WHERE id=?",
                [(int) $position, (int) $sectionId]
            );
        }
        $this->activityLogger->log('homepage.reordered', 'homepage_section');
        return Response::make()->json(['ok' => true]);
    }
}
