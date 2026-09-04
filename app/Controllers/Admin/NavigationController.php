<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class NavigationController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $menus = $this->db->fetchAll("SELECT * FROM menus ORDER BY name");
        foreach ($menus as &$menu) {
            $menu['items'] = $this->db->fetchAll(
                "SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order",
                [$menu['id']]
            );
        }
        unset($menu);

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/navigation/index', [
                'pageTitle' => 'Navigation',
                'menus'     => $menus,
            ])
        );
    }

    public function update(string $id): Response
    {
        $menu = $this->db->fetchOne("SELECT id FROM menus WHERE id = ?", [(int) $id]);
        if (!$menu) {
            flash('error', 'Menu not found.');
            return Response::make()->redirect(url('/admin/navigation'));
        }

        $items = $this->request->all()['items'] ?? [];
        if (!is_array($items)) {
            flash('error', 'Invalid data.');
            return Response::make()->redirect(url('/admin/navigation'));
        }

        // Replace all items for this menu atomically
        $this->db->transaction(function () use ($id, $items): void {
            $this->db->execute("DELETE FROM menu_items WHERE menu_id = ?", [(int) $id]);
            foreach ($items as $sort => $item) {
                if (empty(trim($item['label'] ?? ''))) {
                    continue;
                }
                $this->db->insert('menu_items', [
                    'menu_id'    => (int) $id,
                    'parent_id'  => !empty($item['parent_id']) ? (int) $item['parent_id'] : null,
                    'label'      => substr(trim($item['label']), 0, 200),
                    'url'        => substr(trim($item['url'] ?? ''), 0, 500),
                    'target'     => in_array($item['target'] ?? '', ['_self', '_blank'], true)
                                    ? $item['target'] : '_self',
                    'sort_order' => (int) $sort,
                    'is_active'  => isset($item['is_active']) ? 1 : 0,
                ]);
            }
        });

        $this->activityLogger->log('navigation.updated', 'menu', $id);
        flash('success', 'Navigation saved.');
        return Response::make()->redirect(url('/admin/navigation'));
    }
}
