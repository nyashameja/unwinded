<?php

declare(strict_types=1);

use Unwinded\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->execute("
            CREATE TABLE testimonials (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_name   VARCHAR(150)    NOT NULL,
                customer_title  VARCHAR(150),
                body            TEXT            NOT NULL,
                rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
                event_type      VARCHAR(100),
                photo_media_id  BIGINT UNSIGNED,
                is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
                is_published    TINYINT(1)      NOT NULL DEFAULT 0,
                sort_order      SMALLINT        NOT NULL DEFAULT 0,
                published_at    DATETIME,
                deleted_at      DATETIME,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_published_featured (is_published, is_featured, sort_order),
                CONSTRAINT chk_testimonial_rating CHECK (rating BETWEEN 1 AND 5)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE faq_groups (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(150)    NOT NULL,
                slug        VARCHAR(160)    NOT NULL,
                sort_order  SMALLINT        NOT NULL DEFAULT 0,
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_faq_group_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE faqs (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                group_id    BIGINT UNSIGNED,
                question    VARCHAR(500)    NOT NULL,
                answer      TEXT            NOT NULL,
                is_featured TINYINT(1)      NOT NULL DEFAULT 0,
                is_published TINYINT(1)     NOT NULL DEFAULT 0,
                sort_order  SMALLINT        NOT NULL DEFAULT 0,
                deleted_at  DATETIME,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_group_sort (group_id, sort_order, is_published),
                CONSTRAINT fk_faq_group FOREIGN KEY (group_id)
                    REFERENCES faq_groups (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE enquiries (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ref             VARCHAR(30)     NOT NULL,
                name            VARCHAR(150)    NOT NULL,
                email           VARCHAR(250)    NOT NULL,
                phone           VARCHAR(30),
                subject         VARCHAR(300)    NOT NULL,
                message         TEXT            NOT NULL,
                source          VARCHAR(100),
                status          ENUM('new','in_progress','resolved','spam') NOT NULL DEFAULT 'new',
                assigned_to     BIGINT UNSIGNED,
                reply_body      TEXT,
                replied_at      DATETIME,
                replied_by      BIGINT UNSIGNED,
                ip_address      VARCHAR(45),
                user_agent      VARCHAR(500),
                spam_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_enquiry_ref (ref),
                KEY idx_status_created (status, created_at),
                KEY idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->execute("
            CREATE TABLE newsletter_subscribers (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email           VARCHAR(250)    NOT NULL,
                name            VARCHAR(150),
                status          ENUM('pending','confirmed','unsubscribed','bounced','complained') NOT NULL DEFAULT 'pending',
                confirm_token_hash VARCHAR(64),
                confirm_token_expires_at DATETIME,
                confirmed_at    DATETIME,
                unsubscribed_at DATETIME,
                unsubscribe_token_hash VARCHAR(64),
                ip_address      VARCHAR(45),
                source          VARCHAR(100),
                tags            JSON,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_newsletter_email (email),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->execute("DROP TABLE IF EXISTS newsletter_subscribers");
        $db->execute("DROP TABLE IF EXISTS enquiries");
        $db->execute("DROP TABLE IF EXISTS faqs");
        $db->execute("DROP TABLE IF EXISTS faq_groups");
        $db->execute("DROP TABLE IF EXISTS testimonials");
    }
};
