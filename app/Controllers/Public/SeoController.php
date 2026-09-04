<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;

class SeoController
{
    public function __construct(private Database $db) {}

    public function sitemap(): Response
    {
        $appUrl = rtrim(config('app.url', ''), '/');
        $urls   = [];

        // Static pages
        $statics = [
            ['loc' => '/',                  'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/experiences',       'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/packages',          'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/events',            'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/gallery',           'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/about',             'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/faqs',              'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/testimonials',      'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => '/contact',           'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/request-a-quote',   'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => '/how-it-works',      'priority' => '0.7', 'changefreq' => 'monthly'],
        ];
        foreach ($statics as $s) {
            $urls[] = $s;
        }

        // Experiences
        $experiences = $this->db->fetchAll(
            "SELECT slug, updated_at FROM experiences WHERE is_active = 1 ORDER BY sort_order"
        );
        foreach ($experiences as $row) {
            $urls[] = ['loc' => '/experiences/' . $row['slug'], 'lastmod' => substr($row['updated_at'], 0, 10), 'priority' => '0.8'];
        }

        // Packages
        $packages = $this->db->fetchAll(
            "SELECT slug, updated_at FROM packages WHERE status = 'published' AND deleted_at IS NULL"
        );
        foreach ($packages as $row) {
            $urls[] = ['loc' => '/packages#' . $row['slug'], 'lastmod' => substr($row['updated_at'], 0, 10), 'priority' => '0.7'];
        }

        // Upcoming events
        $events = $this->db->fetchAll(
            "SELECT slug, updated_at FROM public_events
             WHERE status NOT IN ('draft','cancelled') AND deleted_at IS NULL
             ORDER BY event_date_utc DESC LIMIT 50"
        );
        foreach ($events as $row) {
            $urls[] = ['loc' => '/events/' . $row['slug'], 'lastmod' => substr($row['updated_at'], 0, 10), 'priority' => '0.8'];
        }

        // Gallery albums
        $albums = $this->db->fetchAll(
            "SELECT slug, updated_at FROM gallery_albums WHERE status = 'published' AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT 50"
        );
        foreach ($albums as $row) {
            $urls[] = ['loc' => '/gallery/' . $row['slug'], 'lastmod' => substr($row['updated_at'], 0, 10), 'priority' => '0.6'];
        }

        // CMS pages
        $pages = $this->db->fetchAll(
            "SELECT slug, updated_at FROM pages WHERE status = 'published' AND deleted_at IS NULL AND is_system = 0"
        );
        foreach ($pages as $row) {
            $urls[] = ['loc' => '/' . ltrim($row['slug'], '/'), 'lastmod' => substr($row['updated_at'], 0, 10), 'priority' => '0.6'];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($appUrl . $u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod']))    $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            if (!empty($u['changefreq'])) $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            if (!empty($u['priority']))   $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return Response::make()
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->body($xml);
    }

    public function robots(): Response
    {
        $custom   = setting('seo.robots_txt', '');
        $sitemapUrl = url('/sitemap.xml');

        $body = $custom ?: "User-agent: *\nAllow: /\nDisallow: /admin/\n\nSitemap: {$sitemapUrl}\n";

        return Response::make()
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->body($body);
    }
}
