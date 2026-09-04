<?php

declare(strict_types=1);

use Unwinded\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->execute("
            CREATE TABLE email_templates (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                slug        VARCHAR(100)    NOT NULL,
                name        VARCHAR(200)    NOT NULL,
                description TEXT,
                subject     VARCHAR(500)    NOT NULL,
                body_html   LONGTEXT        NOT NULL,
                body_plain  LONGTEXT,
                variables   JSON,
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_template_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE email_queue (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                idempotency_key VARCHAR(100),
                to_address      VARCHAR(250)    NOT NULL,
                to_name         VARCHAR(150),
                from_address    VARCHAR(250),
                from_name       VARCHAR(150),
                reply_to        VARCHAR(250),
                subject         VARCHAR(500)    NOT NULL,
                body_html       LONGTEXT        NOT NULL,
                body_plain      LONGTEXT,
                attachments     JSON,
                template_slug   VARCHAR(100),
                mailable_type   VARCHAR(100),
                mailable_id     BIGINT UNSIGNED,
                priority        TINYINT         NOT NULL DEFAULT 5,
                attempts        TINYINT         NOT NULL DEFAULT 0,
                max_attempts    TINYINT         NOT NULL DEFAULT 3,
                available_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                reserved_at     DATETIME,
                failed_at       DATETIME,
                sent_at         DATETIME,
                last_error      TEXT,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_idempotency (idempotency_key),
                KEY idx_available_priority (available_at, priority, attempts),
                KEY idx_mailable (mailable_type, mailable_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE email_logs (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                queue_id        BIGINT UNSIGNED,
                to_address      VARCHAR(250)    NOT NULL,
                subject         VARCHAR(500)    NOT NULL,
                template_slug   VARCHAR(100),
                mailable_type   VARCHAR(100),
                mailable_id     BIGINT UNSIGNED,
                status          ENUM('sent','failed','bounced','complained') NOT NULL,
                provider_message_id VARCHAR(250),
                sent_at         DATETIME,
                error_message   TEXT,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_to_sent (to_address, sent_at),
                KEY idx_mailable (mailable_type, mailable_id),
                CONSTRAINT fk_el_queue FOREIGN KEY (queue_id)
                    REFERENCES email_queue (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->execute("DROP TABLE IF EXISTS email_logs");
        $db->execute("DROP TABLE IF EXISTS email_queue");
        $db->execute("DROP TABLE IF EXISTS email_templates");
    }
};
