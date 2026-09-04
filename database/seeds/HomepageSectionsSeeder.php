<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class HomepageSectionsSeeder
{
    public function run(Database $db): void
    {
        // homepage_sections table: section_key, label, is_visible, sort_order, config (JSON)
        $sections = [
            [
                'section_key' => 'hero',
                'label'       => 'Hero Banner',
                'is_visible'  => 1,
                'sort_order'  => 10,
                'config'      => [
                    'heading'    => 'Sip. Paint. Unwind.',
                    'subheading' => 'Johannesburg\'s most-loved sip-and-paint experience for private parties, corporate events, and public classes.',
                    'cta_label'  => 'Book an Experience',
                    'cta_url'    => '/experiences',
                ],
            ],
            [
                'section_key' => 'intro',
                'label'       => 'Introduction',
                'is_visible'  => 1,
                'sort_order'  => 20,
                'config'      => [
                    'heading' => 'Where creativity meets connection',
                    'body'    => 'At Unwinded, we believe the best memories are made when people let go, pick up a brush, and enjoy a glass of something good. No experience needed — just good vibes and great company.',
                    'cta_label' => 'Learn more',
                    'cta_url'   => '/about',
                ],
            ],
            [
                'section_key' => 'featured_packages',
                'label'       => 'Featured Packages',
                'is_visible'  => 1,
                'sort_order'  => 30,
                'config'      => [
                    'heading'    => 'Choose your experience',
                    'subheading' => 'From intimate gatherings to large corporate events, we have a package for every occasion.',
                    'cta_label'  => 'View all packages',
                    'cta_url'    => '/packages',
                ],
            ],
            [
                'section_key' => 'upcoming_events',
                'label'       => 'Upcoming Events',
                'is_visible'  => 1,
                'sort_order'  => 40,
                'config'      => [
                    'heading'    => 'Upcoming public events',
                    'subheading' => 'Join us at one of our open sip-and-paint sessions — no group needed, just bring yourself!',
                    'cta_label'  => 'See all events',
                    'cta_url'    => '/events',
                ],
            ],
            [
                'section_key' => 'testimonials',
                'label'       => 'Testimonials',
                'is_visible'  => 1,
                'sort_order'  => 50,
                'config'      => ['heading' => 'What our guests say'],
            ],
            [
                'section_key' => 'gallery_preview',
                'label'       => 'Gallery Preview',
                'is_visible'  => 1,
                'sort_order'  => 60,
                'config'      => [
                    'heading'   => 'See what we\'ve been painting',
                    'cta_label' => 'View gallery',
                    'cta_url'   => '/gallery',
                ],
            ],
            [
                'section_key' => 'cta_banner',
                'label'       => 'Call to Action Banner',
                'is_visible'  => 1,
                'sort_order'  => 70,
                'config'      => [
                    'heading'    => 'Ready to unwind?',
                    'subheading' => 'Book a private event or grab tickets to an upcoming session.',
                    'cta_label'  => 'Get a quote',
                    'cta_url'    => '/quote',
                ],
            ],
        ];

        foreach ($sections as $section) {
            $existing = $db->fetchOne(
                "SELECT id FROM homepage_sections WHERE section_key = ?",
                [$section['section_key']]
            );

            if (!$existing) {
                $db->insert('homepage_sections', [
                    'section_key' => $section['section_key'],
                    'label'       => $section['label'],
                    'is_visible'  => $section['is_visible'],
                    'sort_order'  => $section['sort_order'],
                    'config'      => json_encode($section['config']),
                ]);
                echo "Homepage section created: {$section['section_key']}" . PHP_EOL;
            } else {
                echo "Homepage section exists (skipped): {$section['section_key']}" . PHP_EOL;
            }
        }
    }
}
