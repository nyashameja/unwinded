<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class SearchController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $query   = trim($this->request->str('q') ?? '');
        $results = [];

        if (strlen($query) >= 2) {
            $like = '%' . $query . '%';

            $pages = $this->db->fetchAll(
                "SELECT 'page' AS type, title, slug AS url_key, meta_description AS excerpt
                 FROM pages WHERE status = 'published' AND deleted_at IS NULL
                   AND (title LIKE ? OR meta_description LIKE ?)
                 LIMIT 5",
                [$like, $like]
            );

            $experiences = $this->db->fetchAll(
                "SELECT 'experience' AS type, title, slug AS url_key, short_desc AS excerpt
                 FROM experiences WHERE is_active = 1
                   AND (title LIKE ? OR short_desc LIKE ?)
                 LIMIT 5",
                [$like, $like]
            );

            $packages = $this->db->fetchAll(
                "SELECT 'package' AS type, name AS title, slug AS url_key, tagline AS excerpt
                 FROM packages WHERE status = 'published' AND deleted_at IS NULL
                   AND (name LIKE ? OR tagline LIKE ? OR description LIKE ?)
                 LIMIT 5",
                [$like, $like, $like]
            );

            $events = $this->db->fetchAll(
                "SELECT 'event' AS type, title, slug AS url_key, short_description AS excerpt
                 FROM public_events
                 WHERE status NOT IN ('draft','cancelled') AND deleted_at IS NULL
                   AND event_date_utc >= NOW()
                   AND (title LIKE ? OR short_description LIKE ?)
                 LIMIT 5",
                [$like, $like]
            );

            $results = array_merge($pages, $experiences, $packages, $events);
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/search', [
                'pageTitle'  => $query ? "Search: " . $query : 'Search',
                'metaRobots' => 'noindex,follow',
                'query'      => $query,
                'results'    => $results,
                'bodyClass'  => 'page page--search',
            ])
        );
    }
}
