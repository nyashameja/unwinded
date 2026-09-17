<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class TestimonialController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $testimonials = $this->db->fetchAll(
            "SELECT t.*, CONCAT('/media/', m.year, '/', m.month, '/', m.public_ref, '_thumb.webp') AS photo_url
             FROM testimonials t
             LEFT JOIN media m ON m.id = t.photo_media_id AND m.deleted_at IS NULL
             WHERE t.is_published = 1 AND t.deleted_at IS NULL
             ORDER BY t.is_featured DESC, t.sort_order"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/testimonials', [
                'pageTitle'       => 'What Our Guests Say',
                'metaDescription' => 'Read reviews and testimonials from guests who have attended Unwinded events.',
                'testimonials'    => $testimonials,
                'bodyClass'       => 'page page--testimonials',
            ])
        );
    }
}
