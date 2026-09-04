<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class PageController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function about(): Response
    {
        return $this->renderPage('about', [
            'pageTitle'       => 'About Us',
            'metaDescription' => 'Learn about Unwinded — Johannesburg\'s favourite sip-and-paint experience.',
        ]);
    }

    public function howItWorks(): Response
    {
        return $this->renderPage('how-it-works', [
            'pageTitle'       => 'How It Works',
            'metaDescription' => 'Everything you need to know about booking and attending an Unwinded event.',
        ]);
    }

    public function terms(): Response
    {
        return $this->renderPage('terms', [
            'pageTitle'       => 'Terms of Service',
            'metaRobots'      => 'noindex,follow',
        ]);
    }

    public function privacy(): Response
    {
        return $this->renderPage('privacy', [
            'pageTitle'       => 'Privacy Policy',
            'metaRobots'      => 'noindex,follow',
        ]);
    }

    public function refundPolicy(): Response
    {
        return $this->renderPage('refund-policy', [
            'pageTitle'       => 'Refund Policy',
            'metaRobots'      => 'noindex,follow',
        ]);
    }

    private function renderPage(string $slug, array $defaults): Response
    {
        $page = $this->db->fetchOne(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published' AND deleted_at IS NULL",
            [$slug]
        );

        $data = array_merge($defaults, [
            'page'       => $page ?: null,
            'bodyClass'  => 'page page--' . $slug,
        ]);

        if ($page) {
            if (!empty($page['title']))            $data['pageTitle']       = $page['title'];
            if (!empty($page['meta_description'])) $data['metaDescription'] = $page['meta_description'];
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/page', $data)
        );
    }
}
