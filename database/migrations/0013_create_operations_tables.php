<?php

declare(strict_types=1);

use Unwinded\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->execute("
            CREATE TABLE checklist_templates (
                id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name          VARCHAR(200)    NOT NULL,
                description   TEXT,
                event_type    ENUM('private_booking','public_event','both') NOT NULL DEFAULT 'both',
                is_active     TINYINT(1)      NOT NULL DEFAULT 1,
                sort_order    SMALLINT        NOT NULL DEFAULT 0,
                created_by    BIGINT UNSIGNED,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_event_type_active (event_type, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE checklist_template_items (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                template_id     BIGINT UNSIGNED NOT NULL,
                label           VARCHAR(300)    NOT NULL,
                description     TEXT,
                is_required     TINYINT(1)      NOT NULL DEFAULT 0,
                due_offset_days SMALLINT,
                sort_order      SMALLINT        NOT NULL DEFAULT 0,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_template_sort (template_id, sort_order),
                CONSTRAINT fk_cti_template FOREIGN KEY (template_id)
                    REFERENCES checklist_templates (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE event_checklists (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                checkable_type  ENUM('private_booking','public_event') NOT NULL,
                checkable_id    BIGINT UNSIGNED NOT NULL,
                template_id     BIGINT UNSIGNED,
                name            VARCHAR(200)    NOT NULL,
                created_by      BIGINT UNSIGNED,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_checkable (checkable_type, checkable_id),
                CONSTRAINT fk_ec_template FOREIGN KEY (template_id)
                    REFERENCES checklist_templates (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE checklist_items (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                checklist_id    BIGINT UNSIGNED NOT NULL,
                template_item_id BIGINT UNSIGNED,
                label           VARCHAR(300)    NOT NULL,
                description     TEXT,
                is_required     TINYINT(1)      NOT NULL DEFAULT 0,
                due_date_utc    DATETIME,
                is_completed    TINYINT(1)      NOT NULL DEFAULT 0,
                completed_by    BIGINT UNSIGNED,
                completed_at    DATETIME,
                sort_order      SMALLINT        NOT NULL DEFAULT 0,
                notes           TEXT,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_checklist_sort (checklist_id, sort_order),
                CONSTRAINT fk_ci_checklist FOREIGN KEY (checklist_id)
                    REFERENCES event_checklists (id) ON DELETE CASCADE,
                CONSTRAINT fk_ci_template_item FOREIGN KEY (template_item_id)
                    REFERENCES checklist_template_items (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE staff_assignments (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                assignable_type ENUM('private_booking','public_event') NOT NULL,
                assignable_id   BIGINT UNSIGNED NOT NULL,
                user_id         BIGINT UNSIGNED NOT NULL,
                role            VARCHAR(100)    NOT NULL DEFAULT 'facilitator',
                notes           TEXT,
                assigned_by     BIGINT UNSIGNED,
                assigned_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                confirmed_at    DATETIME,
                PRIMARY KEY (id),
                UNIQUE KEY uq_staff_event_role (assignable_type, assignable_id, user_id, role),
                KEY idx_user_assignments (user_id),
                CONSTRAINT fk_sa_user FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->execute("DROP TABLE IF EXISTS staff_assignments");
        $db->execute("DROP TABLE IF EXISTS checklist_items");
        $db->execute("DROP TABLE IF EXISTS event_checklists");
        $db->execute("DROP TABLE IF EXISTS checklist_template_items");
        $db->execute("DROP TABLE IF EXISTS checklist_templates");
    }
};
