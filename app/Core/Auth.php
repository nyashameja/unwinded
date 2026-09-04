<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * Admin authentication.
 * Customers are NOT logged-in users; they access records via signed links.
 */
class Auth
{
    private ?array $user = null;
    private ?array $permissions = null;
    private const SESSION_KEY = '_auth_user_id';
    private const LAST_ACTIVITY_KEY = '_auth_last_activity';

    public function __construct(
        private Database $db,
        private ?Session $session,
        private Logger   $logger
    ) {}

    public function attempt(string $email, string $password, string $ip): bool
    {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL",
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->db->insert('login_attempts', [
                'email'      => strtolower(trim($email)),
                'ip_address' => $ip,
                'success'    => 0,
                'created_at' => now(),
            ]);
            return false;
        }
        $this->db->insert('login_attempts', [
            'email'      => $user['email'],
            'ip_address' => $ip,
            'success'    => 1,
            'created_at' => now(),
        ]);
        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, $user['id']);
        $this->session->set(self::LAST_ACTIVITY_KEY, time());
        $this->user = $user;
        $this->logger->info("User logged in", ['user_id' => $user['id'], 'ip' => $ip]);
        return true;
    }

    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }
        if ($this->session === null) {
            return null;
        }
        $userId = $this->session->get(self::SESSION_KEY);
        if ($userId === null) {
            return null;
        }
        // Idle timeout check
        $lastActivity = $this->session->get(self::LAST_ACTIVITY_KEY, 0);
        $idleMinutes  = (int) (config('security.session_idle_timeout') ?? 120);
        if (time() - $lastActivity > $idleMinutes * 60) {
            $this->logout();
            return null;
        }
        $this->session->set(self::LAST_ACTIVITY_KEY, time());
        $this->user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ? AND is_active = 1 AND deleted_at IS NULL",
            [$userId]
        );
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user()['id'] ?? null;
    }

    public function can(string $permission): bool
    {
        $user = $this->user();
        if (!$user) return false;
        $perms = $this->loadPermissions($user['id']);
        // Super-admin bypasses all permission checks.
        if ($perms['_is_super'] ?? false) return true;
        return in_array($permission, $perms['permissions'], true);
    }

    private function loadPermissions(int $userId): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }
        // Check if any of the user's roles is super-admin.
        $isSuper = (bool) $this->db->fetchScalar(
            "SELECT COUNT(*) FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ? AND r.is_super = 1",
            [$userId]
        );
        $permissions = [];
        if (!$isSuper) {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT p.name FROM permissions p
                 JOIN role_permissions rp ON rp.permission_id = p.id
                 JOIN user_roles ur ON ur.role_id = rp.role_id
                 WHERE ur.user_id = ?",
                [$userId]
            );
            $permissions = array_column($rows, 'name');
        }
        $this->permissions = ['_is_super' => $isSuper, 'permissions' => $permissions];
        return $this->permissions;
    }

    public function logout(): void
    {
        if ($this->session) {
            $this->session->destroy();
        }
        $this->user        = null;
        $this->permissions = null;
    }
}
