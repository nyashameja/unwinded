<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class RolesAndPermissionsSeeder
{
    public function run(Database $db): void
    {
        // permissions table columns: name, label, group_name
        $permissions = [
            ['name' => 'dashboard.view',        'label' => 'View admin dashboard',          'group_name' => 'dashboard'],
            ['name' => 'users.view',             'label' => 'View admin users',              'group_name' => 'users'],
            ['name' => 'users.create',           'label' => 'Create admin users',            'group_name' => 'users'],
            ['name' => 'users.edit',             'label' => 'Edit admin users',              'group_name' => 'users'],
            ['name' => 'users.delete',           'label' => 'Delete admin users',            'group_name' => 'users'],
            ['name' => 'roles.view',             'label' => 'View roles',                    'group_name' => 'roles'],
            ['name' => 'roles.manage',           'label' => 'Manage roles and permissions',  'group_name' => 'roles'],
            ['name' => 'pages.view',             'label' => 'View pages',                    'group_name' => 'content'],
            ['name' => 'pages.edit',             'label' => 'Edit pages',                    'group_name' => 'content'],
            ['name' => 'media.view',             'label' => 'View media library',            'group_name' => 'content'],
            ['name' => 'media.upload',           'label' => 'Upload media files',            'group_name' => 'content'],
            ['name' => 'media.delete',           'label' => 'Delete media files',            'group_name' => 'content'],
            ['name' => 'packages.view',          'label' => 'View packages',                 'group_name' => 'packages'],
            ['name' => 'packages.edit',          'label' => 'Edit packages',                 'group_name' => 'packages'],
            ['name' => 'packages.delete',        'label' => 'Delete packages',               'group_name' => 'packages'],
            ['name' => 'quote_requests.view',    'label' => 'View quote requests',           'group_name' => 'quotes'],
            ['name' => 'quote_requests.manage',  'label' => 'Manage quote requests',         'group_name' => 'quotes'],
            ['name' => 'quotes.view',            'label' => 'View quotes',                   'group_name' => 'quotes'],
            ['name' => 'quotes.create',          'label' => 'Create quotes',                 'group_name' => 'quotes'],
            ['name' => 'quotes.edit',            'label' => 'Edit quotes',                   'group_name' => 'quotes'],
            ['name' => 'quotes.delete',          'label' => 'Delete quotes',                 'group_name' => 'quotes'],
            ['name' => 'bookings.view',          'label' => 'View bookings',                 'group_name' => 'bookings'],
            ['name' => 'bookings.create',        'label' => 'Create bookings',               'group_name' => 'bookings'],
            ['name' => 'bookings.edit',          'label' => 'Edit bookings',                 'group_name' => 'bookings'],
            ['name' => 'bookings.delete',        'label' => 'Delete bookings',               'group_name' => 'bookings'],
            ['name' => 'events.view',            'label' => 'View public events',            'group_name' => 'events'],
            ['name' => 'events.create',          'label' => 'Create public events',          'group_name' => 'events'],
            ['name' => 'events.edit',            'label' => 'Edit public events',            'group_name' => 'events'],
            ['name' => 'events.delete',          'label' => 'Delete public events',          'group_name' => 'events'],
            ['name' => 'orders.view',            'label' => 'View ticket orders',            'group_name' => 'ticketing'],
            ['name' => 'tickets.checkin',        'label' => 'Check in tickets at the door',  'group_name' => 'ticketing'],
            ['name' => 'customers.view',         'label' => 'View customers',                'group_name' => 'customers'],
            ['name' => 'customers.edit',         'label' => 'Edit customer records',         'group_name' => 'customers'],
            ['name' => 'customers.delete',       'label' => 'Delete customers (soft)',        'group_name' => 'customers'],
            ['name' => 'payments.view',          'label' => 'View payments',                 'group_name' => 'payments'],
            ['name' => 'payments.refund',        'label' => 'Approve refunds',               'group_name' => 'payments'],
            ['name' => 'gallery.view',           'label' => 'View gallery albums',           'group_name' => 'gallery'],
            ['name' => 'gallery.manage',         'label' => 'Manage gallery albums',         'group_name' => 'gallery'],
            ['name' => 'gallery.private',        'label' => 'Manage private galleries',      'group_name' => 'gallery'],
            ['name' => 'checklists.view',        'label' => 'View checklists',               'group_name' => 'operations'],
            ['name' => 'checklists.manage',      'label' => 'Manage checklists',             'group_name' => 'operations'],
            ['name' => 'staff.assign',           'label' => 'Assign staff to events',        'group_name' => 'operations'],
            ['name' => 'testimonials.manage',    'label' => 'Manage testimonials',           'group_name' => 'marketing'],
            ['name' => 'faqs.manage',            'label' => 'Manage FAQs',                   'group_name' => 'marketing'],
            ['name' => 'enquiries.view',         'label' => 'View enquiries',                'group_name' => 'marketing'],
            ['name' => 'enquiries.reply',        'label' => 'Reply to enquiries',            'group_name' => 'marketing'],
            ['name' => 'newsletter.manage',      'label' => 'Manage newsletter',             'group_name' => 'marketing'],
            ['name' => 'reports.view',           'label' => 'View reports',                  'group_name' => 'reports'],
            ['name' => 'reports.export',         'label' => 'Export report data',            'group_name' => 'reports'],
            ['name' => 'settings.view',          'label' => 'View settings',                 'group_name' => 'system'],
            ['name' => 'settings.edit',          'label' => 'Edit settings',                 'group_name' => 'system'],
            ['name' => 'seo.manage',             'label' => 'Manage SEO and redirects',      'group_name' => 'system'],
        ];

        foreach ($permissions as $perm) {
            $db->execute(
                "INSERT IGNORE INTO permissions (name, label, group_name) VALUES (?, ?, ?)",
                [$perm['name'], $perm['label'], $perm['group_name']]
            );
        }

        // roles table columns: name, label, is_super, description
        $roles = [
            [
                'name'        => 'super_admin',
                'label'       => 'Super Admin',
                'is_super'    => 1,
                'description' => 'Full unrestricted access',
                'permissions' => null,
            ],
            [
                'name'        => 'admin',
                'label'       => 'Administrator',
                'is_super'    => 0,
                'description' => 'Full CMS access except user/role management',
                'permissions' => [
                    'dashboard.view','pages.view','pages.edit','media.view','media.upload','media.delete',
                    'packages.view','packages.edit','packages.delete',
                    'quote_requests.view','quote_requests.manage',
                    'quotes.view','quotes.create','quotes.edit','quotes.delete',
                    'bookings.view','bookings.create','bookings.edit','bookings.delete',
                    'events.view','events.create','events.edit','events.delete',
                    'orders.view','tickets.checkin',
                    'customers.view','customers.edit','customers.delete',
                    'payments.view','payments.refund',
                    'gallery.view','gallery.manage','gallery.private',
                    'checklists.view','checklists.manage','staff.assign',
                    'testimonials.manage','faqs.manage','enquiries.view','enquiries.reply','newsletter.manage',
                    'reports.view','reports.export','settings.view','settings.edit','seo.manage',
                ],
            ],
            [
                'name'        => 'coordinator',
                'label'       => 'Coordinator',
                'is_super'    => 0,
                'description' => 'Manages events, bookings, and operations',
                'permissions' => [
                    'dashboard.view',
                    'quote_requests.view','quote_requests.manage',
                    'quotes.view','quotes.create','quotes.edit',
                    'bookings.view','bookings.create','bookings.edit',
                    'events.view','events.create','events.edit',
                    'orders.view','tickets.checkin',
                    'customers.view','customers.edit',
                    'payments.view',
                    'gallery.view','gallery.manage',
                    'checklists.view','checklists.manage','staff.assign',
                    'enquiries.view','enquiries.reply',
                    'reports.view',
                ],
            ],
            [
                'name'        => 'facilitator',
                'label'       => 'Facilitator',
                'is_super'    => 0,
                'description' => 'On-the-day staff: view assigned events, check in tickets',
                'permissions' => [
                    'dashboard.view','events.view','bookings.view','tickets.checkin','checklists.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            $existing = $db->fetchOne("SELECT id FROM roles WHERE name = ?", [$role['name']]);

            if ($existing) {
                $roleId = (int) $existing['id'];
            } else {
                $roleId = $db->insert('roles', [
                    'name'        => $role['name'],
                    'label'       => $role['label'],
                    'is_super'    => $role['is_super'],
                    'description' => $role['description'],
                ]);
            }

            if ($role['permissions'] !== null) {
                foreach ($role['permissions'] as $permName) {
                    $perm = $db->fetchOne("SELECT id FROM permissions WHERE name = ?", [$permName]);
                    if ($perm) {
                        $db->execute(
                            "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                            [$roleId, $perm['id']]
                        );
                    }
                }
            }
        }

        echo "Roles and permissions seeded." . PHP_EOL;
    }
}
