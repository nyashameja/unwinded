<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class FaqController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $groups = $this->db->fetchAll(
            "SELECT * FROM faq_groups WHERE is_active = 1 ORDER BY sort_order"
        );
        $faqs = $this->db->fetchAll(
            "SELECT * FROM faqs WHERE is_published = 1 AND deleted_at IS NULL ORDER BY group_id, sort_order"
        );

        $faqsByGroup = [];
        $ungrouped   = [];
        foreach ($faqs as $faq) {
            if ($faq['group_id']) {
                $faqsByGroup[$faq['group_id']][] = $faq;
            } else {
                $ungrouped[] = $faq;
            }
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/faqs', [
                'pageTitle'       => 'Frequently Asked Questions',
                'metaDescription' => 'Answers to common questions about Unwinded sip-and-paint experiences.',
                'groups'          => $groups,
                'faqsByGroup'     => $faqsByGroup,
                'ungrouped'       => $ungrouped,
                'bodyClass'       => 'page page--faqs',
            ])
        );
    }
}
