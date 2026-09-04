<?php

declare(strict_types=1);

namespace Unwinded\Services;

use Unwinded\Core\Database;
use Unwinded\Core\Auth;

/**
 * Writes to activity_logs for auditable actions.
 * Intentionally not transactional — audit records must survive even if the
 * surrounding business transaction rolls back.
 */
class ActivityLogger
{
    public function __construct(
        private Database $db,
        private Auth     $auth
    ) {}

    public function log(
        string  $action,
        string  $entityType,
        mixed   $entityId  = null,
        array   $context   = [],
        ?string $ip        = null,
        ?int    $userId    = null,
        ?string $userName  = null,
    ): void {
        $user     = $this->auth->user();
        $userId   = $userId   ?? ($user['id']   ?? null);
        $userName = $userName ?? ($user['name'] ?? null);
        $ip       = $ip       ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        $this->db->execute(
            "INSERT INTO activity_logs
             (user_id, user_name, action, entity_type, entity_id, context, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $userId,
                $userName,
                $action,
                $entityType,
                $entityId !== null ? (string) $entityId : null,
                !empty($context) ? json_encode($context) : null,
                $ip,
            ]
        );
    }
}
