<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class HomepageSectionsSeeder
{
    public function run(Database $db): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'heading'     => 'Sip. Paint. Unwind.',
                'subheading'  => 'Johannesburg\'s most-loved sip-and-paint experience for private parties, corporate events, and public classes.',
                'body'        => null,
                'cta_label'   => 'Book an Experience',
                'cta_url'     => '/experiences',
                'is_enabled'  => 1,
                'sort_order'  => 10,
            ],
            [
                'section_key' => 'intro',
                'heading'     => 'Where creativity meets connection',
                'subheading'  => null,
                'body'        => 'At Unwinded, we believe the best memories are made when people let go, pick up a brush, and enjoy a glass of something good. No experience needed — just good vibes and great company.',
                'cta_label'   => 'Learn more',
                'cta_url'     => '/about',
                'is_enabled'  => 1,
                'sort_order'  => 20,
            ],
            [
                'section_key' => 'featured_packages',
                'heading'     => 'Choose your experience',
                'subheading'  => 'From intimate gatherings to large corporate events, we have a package for every occasion.',
                'body'        => null,
                'cta_label'   => 'View all packages',
                'cta_url'     => '/packages',
                'is_enabled'  => 1,
                'sort_order'  => 30,
            ],
            [
                'section_key' => 'upcoming_events',
                'heading'     => 'Upcoming public events',
                'subheading'  => 'Join us at one of our open sip-and-paint sessions — no group needed, just bring yourself!',
                'body'        => null,
                'cta_label'   => 'See all events',
                'cta_url'     => '/events',
                'is_enabled'  => 1,
                'sort_order'  => 40,
            ],
            [
                'section_key' => 'testimonials',
                'heading'     => 'What our guests say',
                'subheading'  => null,
                'body'        => null,
                'cta_label'   => null,
                'cta_url'     => null,
                'is_enabled'  => 1,
                'sort_order'  => 50,
            ],
            [
                'section_key' => 'gallery_preview',
                'heading'     => 'See what we\'ve been painting',
                'subheading'  => null,
                'body'        => null,
                'cta_label'   => 'View gallery',
                'cta_url'     => '/gallery',
                'is_enabled'  => 1,
                'sort_order'  => 60,
            ],
            [
                'section_key' => 'cta_banner',
                'heading'     => 'Ready to unwind?',
                'subheading'  => 'Book a private event or grab tickets to an upcoming session.',
                'body'        => null,
                'cta_label'   => 'Get a quote',
                'cta_url'     => '/quote',
                'is_enabled'  => 1,
                'sort_order'  => 70,
            ],
        ];

        foreach ($sections as $section) {
            $existing = $db->fetchOne(
                "SELECT id FROM homepage_sections WHERE section_key = ?",
                [$section['section_key']]
            );

            if (!$existing) {
                $db->insert('homepage_sections', $section);
                echo "Homepage section created: {$section['section_key']}" . PHP_EOL;
            } else {
                echo "Homepage section exists (skipped): {$section['section_key']}" . PHP_EOL;
            }
        }
    }
}
