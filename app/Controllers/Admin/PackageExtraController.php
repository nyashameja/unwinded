<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class PackageExtraController
{
    private const PRICE_MODELS = ['fixed', 'per_person', 'per_unit'];

    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(string $pid): Response
    {
        $package = $this->findPackage((int) $pid);
        if (!$package) {
            flash('error', 'Package not found.');
            return Response::make()->redirect(url('/admin/packages'));
        }

        $linked = $this->db->fetchAll(
            "SELECT pe.*, pel.is_default
               FROM package_extras pe
               JOIN package_extra_links pel ON pel.extra_id = pe.id
              WHERE pel.package_id = ?
             ORDER BY pe.sort_order, pe.name",
            [(int) $pid]
        );

        $all = $this->db->fetchAll(
            "SELECT * FROM package_extras ORDER BY sort_order, name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/package-extras/index', [
                'pageTitle'   => 'Extras — ' . $package['name'],
                'package'     => $package,
                'linked'      => $linked,
                'allExtras'   => $all,
                'priceModels' => self::PRICE_MODELS,
            ])
        );
    }

    public function store(string $pid): Response
    {
        $package = $this->findPackage((int) $pid);
        if (!$package) {
            flash('error', 'Package not found.');
            return Response::make()->redirect(url('/admin/packages'));
        }

        $action = $this->request->str('action') ?? 'create';

        if ($action === 'link') {
            // Link an existing extra to this package
            $extraId = (int) ($this->request->str('extra_id') ?? 0);
            if (!$extraId) {
                flash('error', 'Select an extra to link.');
                return Response::make()->redirect(url('/admin/packages/' . (int) $pid . '/extras'));
            }
            $this->db->execute(
                "INSERT IGNORE INTO package_extra_links (package_id, extra_id, is_default) VALUES (?,?,0)",
                [(int) $pid, $extraId]
            );
            flash('success', 'Extra linked.');
        } else {
            // Create a new global extra and link it
            $name = trim($this->request->str('name') ?? '');
            if (!$name) {
                flash('error', 'Name is required.');
                return Response::make()->redirect(url('/admin/packages/' . (int) $pid . '/extras'));
            }

            $priceModel = $this->request->str('price_model') ?? 'fixed';
            if (!in_array($priceModel, self::PRICE_MODELS, true)) {
                $priceModel = 'fixed';
            }

            $priceCents = (int) round((float) str_replace(',', '', $this->request->str('price') ?? '0') * 100);

            $extraId = $this->db->insert('package_extras', [
                'name'        => substr($name, 0, 200),
                'description' => trim($this->request->str('description') ?? '') ?: null,
                'price_model' => $priceModel,
                'price_cents' => $priceCents,
                'min_qty'     => max(1, (int) ($this->request->str('min_qty') ?? 1)),
                'max_qty'     => (($v = (int) ($this->request->str('max_qty') ?? 0)) > 0) ? $v : null,
                'is_active'   => 1,
                'sort_order'  => (int) ($this->request->str('sort_order') ?? 0),
            ]);

            $this->db->execute(
                "INSERT IGNORE INTO package_extra_links (package_id, extra_id, is_default) VALUES (?,?,0)",
                [(int) $pid, $extraId]
            );

            flash('success', 'Extra created and linked.');
        }

        return Response::make()->redirect(url('/admin/packages/' . (int) $pid . '/extras'));
    }

    public function update(string $pid, string $id): Response
    {
        $package = $this->findPackage((int) $pid);
        if (!$package) {
            flash('error', 'Package not found.');
            return Response::make()->redirect(url('/admin/packages'));
        }

        $action = $this->request->str('action') ?? 'toggle_default';

        if ($action === 'unlink') {
            $this->db->execute(
                "DELETE FROM package_extra_links WHERE package_id=? AND extra_id=?",
                [(int) $pid, (int) $id]
            );
            flash('success', 'Extra unlinked.');
        } else {
            $isDefault = (bool) $this->request->str('is_default') ? 1 : 0;
            $this->db->execute(
                "UPDATE package_extra_links SET is_default=? WHERE package_id=? AND extra_id=?",
                [$isDefault, (int) $pid, (int) $id]
            );
            flash('success', 'Default status updated.');
        }

        return Response::make()->redirect(url('/admin/packages/' . (int) $pid . '/extras'));
    }

    private function findPackage(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM packages WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
