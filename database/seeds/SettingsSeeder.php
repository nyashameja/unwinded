<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class SettingsSeeder
{
    public function run(Database $db): void
    {
        $settings = [
            // Brand
            ['key' => 'site.name',           'value' => 'Unwinded',                  'type' => 'string',  'group' => 'brand',    'label' => 'Site Name'],
            ['key' => 'site.tagline',         'value' => 'Sip. Paint. Unwind.',       'type' => 'string',  'group' => 'brand',    'label' => 'Tagline'],
            ['key' => 'site.description',     'value' => 'Johannesburg\'s favourite sip-and-paint studio experience — for private parties, corporate events, and public classes.', 'type' => 'text', 'group' => 'brand', 'label' => 'Site Description'],
            ['key' => 'site.logo_media_id',   'value' => '',                          'type' => 'integer', 'group' => 'brand',    'label' => 'Logo Media ID'],
            ['key' => 'site.favicon_media_id','value' => '',                          'type' => 'integer', 'group' => 'brand',    'label' => 'Favicon Media ID'],

            // Contact
            ['key' => 'contact.email',        'value' => 'hello@unwinded.co.za',      'type' => 'string',  'group' => 'contact',  'label' => 'Contact Email'],
            ['key' => 'contact.phone',        'value' => '+27 11 000 0000',           'type' => 'string',  'group' => 'contact',  'label' => 'Phone Number'],
            ['key' => 'contact.whatsapp',     'value' => '+27820000000',              'type' => 'string',  'group' => 'contact',  'label' => 'WhatsApp Number'],
            ['key' => 'contact.address',      'value' => 'Johannesburg, Gauteng, South Africa', 'type' => 'text', 'group' => 'contact', 'label' => 'Address'],
            ['key' => 'contact.maps_url',     'value' => '',                          'type' => 'string',  'group' => 'contact',  'label' => 'Google Maps URL'],

            // Social
            ['key' => 'social.instagram',     'value' => 'https://instagram.com/unwinded', 'type' => 'string', 'group' => 'social', 'label' => 'Instagram URL'],
            ['key' => 'social.facebook',      'value' => 'https://facebook.com/unwinded', 'type' => 'string', 'group' => 'social', 'label' => 'Facebook URL'],
            ['key' => 'social.tiktok',        'value' => '',                          'type' => 'string',  'group' => 'social',  'label' => 'TikTok URL'],

            // Business
            ['key' => 'business.vat_mode',          'value' => 'none',      'type' => 'string',  'group' => 'business', 'label' => 'VAT Mode (none/inclusive/exclusive)'],
            ['key' => 'business.vat_rate',           'value' => '15',        'type' => 'integer', 'group' => 'business', 'label' => 'VAT Rate (%)'],
            ['key' => 'business.vat_number',         'value' => '',          'type' => 'string',  'group' => 'business', 'label' => 'VAT Registration Number'],
            ['key' => 'business.deposit_percent',    'value' => '50',        'type' => 'integer', 'group' => 'business', 'label' => 'Deposit Percentage'],
            ['key' => 'business.deposit_soft_days',  'value' => '2',         'type' => 'integer', 'group' => 'business', 'label' => 'Deposit Soft Deadline (days from acceptance)'],
            ['key' => 'business.deposit_hard_days',  'value' => '5',         'type' => 'integer', 'group' => 'business', 'label' => 'Deposit Hard Deadline (business days before event)'],
            ['key' => 'business.quote_validity_days','value' => '14',        'type' => 'integer', 'group' => 'business', 'label' => 'Quote Validity (days)'],
            ['key' => 'business.quote_reminder_day', 'value' => '10',        'type' => 'integer', 'group' => 'business', 'label' => 'Quote Reminder Day (day N of validity period)'],
            ['key' => 'business.refund_days',        'value' => '10',        'type' => 'integer', 'group' => 'business', 'label' => 'Refund Processing Days'],
            ['key' => 'business.min_guests',         'value' => '8',         'type' => 'integer', 'group' => 'business', 'label' => 'Minimum Guests (private)'],
            ['key' => 'business.max_guests',         'value' => '50',        'type' => 'integer', 'group' => 'business', 'label' => 'Maximum Guests (private)'],
            ['key' => 'business.currency',           'value' => 'ZAR',       'type' => 'string',  'group' => 'business', 'label' => 'Currency Code'],
            ['key' => 'business.currency_symbol',    'value' => 'R',         'type' => 'string',  'group' => 'business', 'label' => 'Currency Symbol'],

            // Cancellation fees
            ['key' => 'cancellation.tier_1_days',    'value' => '30',  'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 1: >N days before — fee %'],
            ['key' => 'cancellation.tier_1_fee',     'value' => '0',   'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 1 Fee %'],
            ['key' => 'cancellation.tier_2_days',    'value' => '14',  'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 2: >N days before — fee %'],
            ['key' => 'cancellation.tier_2_fee',     'value' => '25',  'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 2 Fee %'],
            ['key' => 'cancellation.tier_3_days',    'value' => '7',   'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 3: >N days before — fee %'],
            ['key' => 'cancellation.tier_3_fee',     'value' => '50',  'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 3 Fee %'],
            ['key' => 'cancellation.tier_4_fee',     'value' => '100', 'type' => 'integer', 'group' => 'cancellation', 'label' => 'Tier 4: <Tier 3 days — fee %'],

            // SEO defaults
            ['key' => 'seo.default_title_suffix', 'value' => '| Unwinded', 'type' => 'string', 'group' => 'seo', 'label' => 'Default Title Suffix'],
            ['key' => 'seo.robots',               'value' => 'index,follow', 'type' => 'string', 'group' => 'seo', 'label' => 'Default Robots Tag'],

            // Maintenance
            ['key' => 'site.maintenance_mode',    'value' => '0', 'type' => 'boolean', 'group' => 'system', 'label' => 'Maintenance Mode'],
            ['key' => 'site.maintenance_message', 'value' => 'We\'re making some improvements. Check back soon!', 'type' => 'text', 'group' => 'system', 'label' => 'Maintenance Message'],
        ];

        foreach ($settings as $setting) {
            $db->execute(
                "INSERT IGNORE INTO settings (`key`, value, type, group_name, label)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $setting['key'],
                    $setting['value'],
                    $setting['type'],
                    $setting['group'],
                    $setting['label'],
                ]
            );
        }

        echo "Settings seeded." . PHP_EOL;
    }
}
