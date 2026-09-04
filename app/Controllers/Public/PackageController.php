<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class PackageController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $packages = $this->db->fetchAll(
            "SELECT * FROM packages
             WHERE status = 'published' AND deleted_at IS NULL
             ORDER BY sort_order, name"
        );

        $packageIds = array_column($packages, 'id');
        $features   = [];
        $extras     = [];

        if ($packageIds) {
            $placeholders = implode(',', array_fill(0, count($packageIds), '?'));
            $featureRows = $this->db->fetchAll(
                "SELECT * FROM package_features
                 WHERE package_id IN ({$placeholders})
                 ORDER BY package_id, sort_order",
                $packageIds
            );
            foreach ($featureRows as $f) {
                $features[$f['package_id']][] = $f;
            }

            $extraRows = $this->db->fetchAll(
                "SELECT pe.*, pel.package_id, pel.is_default
                 FROM package_extras pe
                 JOIN package_extra_links pel ON pel.extra_id = pe.id
                 WHERE pel.package_id IN ({$placeholders}) AND pe.is_active = 1
                 ORDER BY pel.package_id, pe.sort_order",
                $packageIds
            );
            foreach ($extraRows as $e) {
                $extras[$e['package_id']][] = $e;
            }
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/packages/index', [
                'pageTitle'       => 'Packages',
                'metaDescription' => 'Choose the perfect sip-and-paint package for your event.',
                'packages'        => $packages,
                'features'        => $features,
                'extras'          => $extras,
                'bodyClass'       => 'page page--packages',
            ])
        );
    }
}
