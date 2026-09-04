<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class ExperienceController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $experiences = $this->db->fetchAll(
            "SELECT * FROM experiences WHERE is_active = 1 ORDER BY sort_order, title"
        );
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/experiences/index', [
                'pageTitle'       => 'Experiences',
                'metaDescription' => 'Explore our range of sip-and-paint experiences for corporate events, private parties, and more.',
                'experiences'     => $experiences,
                'bodyClass'       => 'page page--experiences',
            ])
        );
    }

    public function show(string $slug): Response
    {
        $exp = $this->db->fetchOne(
            "SELECT * FROM experiences WHERE slug = ? AND is_active = 1",
            [$slug]
        );
        if (!$exp) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Not Found'])
            );
        }
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/experiences/show', [
                'pageTitle'       => $exp['meta_title'] ?: $exp['title'],
                'metaDescription' => $exp['meta_description'] ?: $exp['short_desc'],
                'experience'      => $exp,
                'bodyClass'       => 'page page--experience-detail',
            ])
        );
    }

    public function corporate(): Response
    {
        return $this->showType('corporate', 'Corporate Events', 'Corporate sip-and-paint experiences for teams.');
    }

    public function restaurant(): Response
    {
        return $this->showType('restaurant', 'Restaurant Partnerships', 'Partner with us for exclusive events at your venue.');
    }

    public function private(): Response
    {
        return $this->showType('private', 'Private Celebrations', 'Birthdays, hens, and special occasions — make it memorable.');
    }

    private function showType(string $type, string $title, string $desc): Response
    {
        $experiences = $this->db->fetchAll(
            "SELECT * FROM experiences WHERE is_active = 1 AND type = ? ORDER BY sort_order, title",
            [$type]
        );
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/experiences/index', [
                'pageTitle'       => $title,
                'metaDescription' => $desc,
                'experiences'     => $experiences,
                'activeType'      => $type,
                'bodyClass'       => 'page page--experiences page--experiences-' . $type,
            ])
        );
    }
}
