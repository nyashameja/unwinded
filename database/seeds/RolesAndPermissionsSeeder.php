<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class RolesAndPermissionsSeeder
{
    public function run(Database $db): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'description' => 'View admin dashboard'],

            // Users
            ['name' => 'users.view',   'description' => 'View admin users'],
            ['name' => 'users.create', 'description' => 'Create admin users'],
            ['name' => 'users.edit',   'description' => 'Edit admin users'],
            ['name' => 'users.delete', 'description' => 'Delete admin users'],

            // Roles
            ['name' => 'roles.view',   'description' => 'View roles'],
            ['name' => 'roles.manage', 'description' => 'Manage roles and permissions'],

            // Content
            ['name' => 'pages.view',   'description' => 'View pages'],
            ['name' => 'pages.edit',   'description' => 'Edit pages'],
            ['name' => 'media.view',   'description' => 'View media library'],
            ['name' => 'media.upload', 'description' => 'Upload media files'],
            ['name' => 'media.delete', 'description' => 'Delete media files'],

            // Packages & Experiences
            ['name' => 'packages.view',   'description' => 'View packages'],
            ['name' => 'packages.edit',   'description' => 'Edit packages'],
            ['name' => 'packages.delete', 'description' => 'Delete packages'],

            // Quote Requests
            ['name' => 'quote_requests.view',   'description' => 'View quote requests'],
            ['name' => 'quote_requests.manage', 'description' => 'Manage quote requests'],

            // Quotes
            ['name' => 'quotes.view',   'description' => 'View quotes'],
            ['name' => 'quotes.create', 'description' => 'Create quotes'],
            ['name' => 'quotes.edit',   'description' => 'Edit quotes'],
            ['name' => 'quotes.delete', 'description' => 'Delete quotes'],

            // Bookings
            ['name' => 'bookings.view',   'description' => 'View bookings'],
            ['name' => 'bookings.create', 'description' => 'Create bookings'],
            ['name' => 'bookings.edit',   'description' => 'Edit bookings'],
            ['name' => 'bookings.delete', 'description' => 'Delete bookings'],

            // Events
            ['name' => 'events.view',   'description' => 'View public events'],
            ['name' => 'events.create', 'description' => 'Create public events'],
            ['name' => 'events.edit',   'description' => 'Edit public events'],
            ['name' => 'events.delete', 'description' => 'Delete public events'],

            // Ticketing
            ['name' => 'orders.view',    'description' => 'View ticket orders'],
            ['name' => 'tickets.checkin','description' => 'Check in tickets at the door'],

            // Customers
            ['name' => 'customers.view',   'description' => 'View customers'],
            ['name' => 'customers.edit',   'description' => 'Edit customer records'],
            ['name' => 'customers.delete', 'description' => 'Delete customers (soft)'],

            // Payments
            ['name' => 'payments.view',   'description' => 'View payments'],
            ['name' => 'payments.refund', 'description' => 'Approve refunds'],

            // Gallery
            ['name' => 'gallery.view',    'description' => 'View gallery albums'],
            ['name' => 'gallery.manage',  'description' => 'Manage gallery albums'],
            ['name' => 'gallery.private', 'description' => 'Manage private galleries'],

            // Operations
            ['name' => 'checklists.view',   'description' => 'View checklists'],
            ['name' => 'checklists.manage', 'description' => 'Manage checklists'],
            ['name' => 'staff.assign',      'description' => 'Assign staff to events'],

            // Marketing
            ['name' => 'testimonials.manage', 'description' => 'Manage testimonials'],
            ['name' => 'faqs.manage',         'description' => 'Manage FAQs'],
            ['name' => 'enquiries.view',      'description' => 'View enquiries'],
            ['name' => 'enquiries.reply',     'description' => 'Reply to enquiries'],
            ['name' => 'newsletter.manage',   'description' => 'Manage newsletter subscribers'],

            // Reports
            ['name' => 'reports.view',   'description' => 'View reports'],
            ['name' => 'reports.export', 'description' => 'Export report data'],

            // Settings
            ['name' => 'settings.view', 'description' => 'View settings'],
            ['name' => 'settings.edit', 'description' => 'Edit settings'],

            // SEO / Redirects
            ['name' => 'seo.manage', 'description' => 'Manage SEO and redirects'],
        ];

        foreach ($permissions as $perm) {
            $db->execute(
                "INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)",
                [$perm['name'], $perm['description']]
            );
        }

        $roles = [
            [
                'name'        => 'super_admin',
                'description' => 'Full unrestricted access',
                'permissions' => null, // granted via is_super_admin flag on users
            ],
            [
                'name'        => 'admin',
                'description' => 'Full CMS access except user/role management',
                'permissions' => [
                    'dashboard.view',
                    'pages.view', 'pages.edit',
                    'media.view', 'media.upload', 'media.delete',
                    'packages.view', 'packages.edit', 'packages.delete',
                    'quote_requests.view', 'quote_requests.manage',
                    'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.delete',
                    'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
                    'events.view', 'events.create', 'events.edit', 'events.delete',
                    'orders.view', 'tickets.checkin',
                    'customers.view', 'customers.edit', 'customers.delete',
                    'payments.view', 'payments.refund',
                    'gallery.view', 'gallery.manage', 'gallery.private',
                    'checklists.view', 'checklists.manage', 'staff.assign',
                    'testimonials.manage', 'faqs.manage',
                    'enquiries.view', 'enquiries.reply',
                    'newsletter.manage',
                    'reports.view', 'reports.export',
                    'settings.view', 'settings.edit',
                    'seo.manage',
                ],
            ],
            [
                'name'        => 'coordinator',
                'description' => 'Manages events, bookings, and operations',
                'permissions' => [
                    'dashboard.view',
                    'quote_requests.view', 'quote_requests.manage',
                    'quotes.view', 'quotes.create', 'quotes.edit',
                    'bookings.view', 'bookings.create', 'bookings.edit',
                    'events.view', 'events.create', 'events.edit',
                    'orders.view', 'tickets.checkin',
                    'customers.view', 'customers.edit',
                    'payments.view',
                    'gallery.view', 'gallery.manage',
                    'checklists.view', 'checklists.manage', 'staff.assign',
                    'enquiries.view', 'enquiries.reply',
                    'reports.view',
                ],
            ],
            [
                'name'        => 'facilitator',
                'description' => 'On-the-day staff: view assigned events, check in tickets',
                'permissions' => [
                    'dashboard.view',
                    'events.view',
                    'bookings.view',
                    'tickets.checkin',
                    'checklists.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            $existing = $db->fetchOne(
                "SELECT id FROM roles WHERE name = ?",
                [$role['name']]
            );

            if ($existing) {
                $roleId = (int) $existing['id'];
            } else {
                $roleId = $db->insert('roles', [
                    'name'        => $role['name'],
                    'description' => $role['description'],
                ]);
            }

            if ($role['permissions'] !== null) {
                foreach ($role['permissions'] as $permName) {
                    $perm = $db->fetchOne(
                        "SELECT id FROM permissions WHERE name = ?",
                        [$permName]
                    );
                    if ($perm) {
                        $db->execute(
                            "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                            [$roleId, $perm['id']]
                        );
                    }
                }
            }
        }
    }
}
