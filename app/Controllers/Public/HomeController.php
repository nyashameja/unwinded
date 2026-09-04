<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class HomeController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $sections = $this->db->fetchAll(
            "SELECT * FROM homepage_sections WHERE is_visible = 1 ORDER BY sort_order"
        );
        $sectionMap = [];
        foreach ($sections as $s) {
            $sectionMap[$s['section_key']] = array_merge($s, [
                'config' => json_decode($s['config'] ?? '{}', true) ?? [],
            ]);
        }

        $featuredPackages = $this->db->fetchAll(
            "SELECT p.*, GROUP_CONCAT(pf.label ORDER BY pf.sort_order SEPARATOR '|||') AS feature_labels
             FROM packages p
             LEFT JOIN package_features pf ON pf.package_id = p.id AND pf.is_included = 1
             WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.is_featured = 1
             GROUP BY p.id
             ORDER BY p.sort_order
             LIMIT 3"
        );

        $upcomingEvents = $this->db->fetchAll(
            "SELECT * FROM public_events
             WHERE status IN ('on_sale','published')
               AND event_date_utc >= NOW()
               AND deleted_at IS NULL
             ORDER BY event_date_utc
             LIMIT 3"
        );

        $testimonials = $this->db->fetchAll(
            "SELECT * FROM testimonials
             WHERE is_published = 1 AND is_featured = 1 AND deleted_at IS NULL
             ORDER BY sort_order
             LIMIT 6"
        );

        $galleryAlbums = $this->db->fetchAll(
            "SELECT ga.*, m.public_url AS cover_url
             FROM gallery_albums ga
             LEFT JOIN media m ON m.id = ga.cover_image_id AND m.deleted_at IS NULL
             WHERE ga.status = 'published' AND ga.is_featured = 1 AND ga.deleted_at IS NULL
             ORDER BY ga.sort_order
             LIMIT 6"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/home', [
                'pageTitle'       => null,
                'sections'        => $sectionMap,
                'featuredPackages'=> $featuredPackages,
                'upcomingEvents'  => $upcomingEvents,
                'testimonials'    => $testimonials,
                'galleryAlbums'   => $galleryAlbums,
            ])
        );
    }
}
