<?php

declare(strict_types=1);

use Unwinded\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->execute("
            CREATE TABLE scheduled_tasks (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name            VARCHAR(100)    NOT NULL,
                handler         VARCHAR(200)    NOT NULL,
                schedule        VARCHAR(100)    NOT NULL,
                last_run_at     DATETIME,
                last_run_status ENUM('success','failure','running') DEFAULT NULL,
                last_run_output TEXT,
                next_run_at     DATETIME,
                is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
                run_count       INT UNSIGNED    NOT NULL DEFAULT 0,
                failure_count   INT UNSIGNED    NOT NULL DEFAULT 0,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_task_name (name),
                KEY idx_next_run (next_run_at, is_enabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE rate_limits (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                key_hash    VARCHAR(64)     NOT NULL,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_key_hash_created (key_hash, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->execute("DROP TABLE IF EXISTS rate_limits");
        $db->execute("DROP TABLE IF EXISTS scheduled_tasks");
    }
};
