<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Support\Ref;

class PackageController
{
    private const PRICING_MODELS = ['per_person', 'fixed', 'price_on_request'];

    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $packages = $this->db->fetchAll(
            "SELECT p.*,
                    COUNT(DISTINCT pf.id) AS feature_count,
                    COUNT(DISTINCT pel.extra_id) AS extra_count
               FROM packages p
               LEFT JOIN package_features pf ON pf.package_id = p.id
               LEFT JOIN package_extra_links pel ON pel.package_id = p.id
              WHERE p.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY p.sort_order, p.name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/packages/index', [
                'pageTitle' => 'Packages',
                'packages'  => $packages,
            ])
        );
    }

    public function create(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/packages/create', [
                'pageTitle'     => 'New Package',
                'pricingModels' => self::PRICING_MODELS,
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/packages/create'));
        }

        $data['public_ref'] = Ref::generate('PKG');
        $this->ensureUniqueSlug($data['slug']);

        $id = $this->db->insert('packages', $data);
        $this->syncFeatures((int) $id);

        flash('success', 'Package created.');
        return Response::make()->redirect(url('/admin/packages/' . (int) $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $package = $this->findOrFail((int) $id);
        if (!$package) {
            flash('error', 'Package not found.');
            return Response::make()->redirect(url('/admin/packages'));
        }

        $features = $this->db->fetchAll(
            "SELECT * FROM package_features WHERE package_id = ? ORDER BY sort_order",
            [(int) $id]
        );

        $extras = $this->db->fetchAll(
            "SELECT pe.*, pel.is_default
               FROM package_extras pe
               JOIN package_extra_links pel ON pel.extra_id = pe.id
              WHERE pel.package_id = ?
             ORDER BY pe.sort_order",
            [(int) $id]
        );

        $allExtras = $this->db->fetchAll(
            "SELECT * FROM package_extras WHERE is_active = 1 ORDER BY sort_order, name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/packages/edit', [
                'pageTitle'     => 'Edit Package',
                'package'       => $package,
                'features'      => $features,
                'extras'        => $extras,
                'allExtras'     => $allExtras,
                'pricingModels' => self::PRICING_MODELS,
            ])
        );
    }

    public function update(string $id): Response
    {
        $package = $this->findOrFail((int) $id);
        if (!$package) {
            flash('error', 'Package not found.');
            return Response::make()->redirect(url('/admin/packages'));
        }

        [$data, $errors] = $this->validate($package['slug']);

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/packages/' . (int) $id . '/edit'));
        }

        $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
        $this->db->execute(
            "UPDATE packages SET $set, updated_at=NOW() WHERE id=?",
            [...array_values($data), (int) $id]
        );

        $this->syncFeatures((int) $id);

        flash('success', 'Package updated.');
        return Response::make()->redirect(url('/admin/packages/' . (int) $id . '/edit'));
    }

    public function destroy(string $id): Response
    {
        $this->db->execute(
            "UPDATE packages SET deleted_at=NOW() WHERE id=?",
            [(int) $id]
        );

        flash('success', 'Package deleted.');
        return Response::make()->redirect(url('/admin/packages'));
    }

    private function validate(string $existingSlug = ''): array
    {
        $errors = [];
        $data   = [];

        $name = trim($this->request->str('name') ?? '');
        if (!$name) {
            $errors[] = 'Name is required.';
        } else {
            $data['name'] = substr($name, 0, 200);
        }

        $slug = trim($this->request->str('slug') ?? '');
        if (!$slug) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
            $slug = trim($slug, '-');
        }
        $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', $slug));
        if (!$slug) {
            $errors[] = 'Slug is required.';
        } else {
            if ($slug !== $existingSlug) {
                $dup = $this->db->fetchScalar("SELECT id FROM packages WHERE slug=? AND deleted_at IS NULL", [$slug]);
                if ($dup) {
                    $errors[] = 'That slug is already in use.';
                }
            }
            $data['slug'] = substr($slug, 0, 250);
        }

        $pricingModel = $this->request->str('pricing_model') ?? 'per_person';
        $data['pricing_model'] = in_array($pricingModel, self::PRICING_MODELS, true) ? $pricingModel : 'per_person';

        $priceInput = (float) str_replace(',', '', $this->request->str('base_price') ?? '0');
        $data['base_price_cents'] = (int) round($priceInput * 100);

        $data['tagline']         = substr(trim($this->request->str('tagline') ?? ''), 0, 500) ?: null;
        $data['description']     = trim($this->request->str('description') ?? '') ?: null;
        $data['min_guests']      = max(1, (int) ($this->request->str('min_guests') ?? 1));
        $data['max_guests']      = (($v = (int) ($this->request->str('max_guests') ?? 0)) > 0) ? $v : null;
        $data['status']          = $this->request->str('status') === 'published' ? 'published' : 'draft';
        $data['is_featured']     = (bool) $this->request->str('is_featured') ? 1 : 0;
        $data['sort_order']      = (int) ($this->request->str('sort_order') ?? 0);
        $data['highlight_colour'] = substr(trim($this->request->str('highlight_colour') ?? ''), 0, 7) ?: null;

        return [$data, $errors];
    }

    private function syncFeatures(int $packageId): void
    {
        $labels = $this->request->all()['feature_label'] ?? [];
        if (!is_array($labels)) {
            return;
        }

        $this->db->execute("DELETE FROM package_features WHERE package_id = ?", [$packageId]);

        $included = $this->request->all()['feature_included'] ?? [];
        $order    = 0;
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if (!$label) {
                continue;
            }
            $this->db->insert('package_features', [
                'package_id'  => $packageId,
                'label'       => substr($label, 0, 300),
                'is_included' => isset($included[$i]) ? 1 : 0,
                'sort_order'  => $order++,
            ]);
        }
    }

    private function ensureUniqueSlug(string &$slug, int $excludeId = 0): void
    {
        $base = $slug;
        $i    = 2;
        while ($this->db->fetchScalar(
            "SELECT id FROM packages WHERE slug=? AND deleted_at IS NULL AND id != ?",
            [$slug, $excludeId]
        )) {
            $slug = $base . '-' . $i++;
        }
    }

    private function findOrFail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM packages WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
