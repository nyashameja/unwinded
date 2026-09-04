<?php

declare(strict_types=1);

use Unwinded\Core\Database;
use Unwinded\Support\Ref;

class AdminUserSeeder
{
    public function run(Database $db): void
    {
        $email    = $_ENV['SEED_ADMIN_EMAIL']    ?? 'admin@unwinded.co.za';
        $password = $_ENV['SEED_ADMIN_PASSWORD'] ?? 'ChangeMe!2024';
        $name     = $_ENV['SEED_ADMIN_NAME']     ?? 'Admin';

        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);

        if ($existing) {
            echo "Admin user already exists: {$email}" . PHP_EOL;
            return;
        }

        $userId = $db->insert('users', [
            'public_ref'    => Ref::generate('USR'),
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'is_active'     => 1,
        ]);

        $superAdminRole = $db->fetchOne("SELECT id FROM roles WHERE name = 'super_admin'");
        if ($superAdminRole) {
            $db->execute(
                "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)",
                [$userId, $superAdminRole['id']]
            );
        }

        echo "Admin user created: {$email}" . PHP_EOL;
        echo "IMPORTANT: Change the password after first login!" . PHP_EOL;
    }
}
