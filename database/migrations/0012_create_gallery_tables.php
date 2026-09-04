<?php
return [
'up' => "
CREATE TABLE gallery_albums (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref        VARCHAR(20) NOT NULL UNIQUE,
    slug              VARCHAR(250) NOT NULL UNIQUE,
    title             VARCHAR(500) NOT NULL,
    description       MEDIUMTEXT NULL,
    cover_image_id    BIGINT UNSIGNED NULL,
    event_date        DATE NULL,
    event_type        VARCHAR(100) NULL,
    venue             VARCHAR(300) NULL,
    location          VARCHAR(200) NULL,
    segment           ENUM('corporate','restaurant','bridal','baby_shower','birthday',
                           'couples','private','public_event','other') NOT NULL DEFAULT 'other',
    linked_event_id   BIGINT UNSIGNED NULL,
    linked_booking_id BIGINT UNSIGNED NULL,
    is_featured       TINYINT(1) NOT NULL DEFAULT 0,
    status            ENUM('draft','scheduled','published','private','archived') NOT NULL DEFAULT 'draft',
    scheduled_at      DATETIME NULL,
    published_at      DATETIME NULL,
    is_downloadable   TINYINT(1) NOT NULL DEFAULT 0,
    customer_approved TINYINT(1) NOT NULL DEFAULT 0,
    approved_at       DATETIME NULL,
    approved_by       BIGINT UNSIGNED NULL,
    meta_title        VARCHAR(500) NULL,
    meta_description  VARCHAR(500) NULL,
    og_image          VARCHAR(500) NULL,
    sort_order        INT NOT NULL DEFAULT 0,
    created_by        BIGINT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME NULL,
    FOREIGN KEY (linked_event_id)   REFERENCES public_events(id)    ON DELETE SET NULL,
    FOREIGN KEY (linked_booking_id) REFERENCES private_bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)        REFERENCES users(id)            ON DELETE SET NULL,
    FOREIGN KEY (approved_by)       REFERENCES users(id)            ON DELETE SET NULL,
    INDEX idx_ga_slug      (slug),
    INDEX idx_ga_status    (status),
    INDEX idx_ga_segment   (segment),
    INDEX idx_ga_featured  (is_featured),
    INDEX idx_ga_event     (event_date),
    INDEX idx_ga_deleted   (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE gallery_images (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id     BIGINT UNSIGNED NOT NULL,
    media_id     BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(300) NULL,
    caption      TEXT NULL,
    is_featured  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order   INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id)          ON DELETE RESTRICT,
    INDEX idx_gallery_images_album (album_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE gallery_access_tokens (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id      BIGINT UNSIGNED NOT NULL,
    token_hash    VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    expires_at    DATETIME NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    revoked_at    DATETIME NULL,
    created_by    BIGINT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id)   REFERENCES gallery_albums(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)          ON DELETE SET NULL,
    INDEX idx_gat_album      (album_id),
    INDEX idx_gat_token_hash (token_hash),
    INDEX idx_gat_active     (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE gallery_access_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_id        BIGINT UNSIGNED NOT NULL,
    album_id        BIGINT UNSIGNED NOT NULL,
    event_type      ENUM('view','download','password_attempt','password_fail') NOT NULL DEFAULT 'view',
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES gallery_access_tokens(id) ON DELETE CASCADE,
    INDEX idx_gal_token   (token_id),
    INDEX idx_gal_album   (album_id),
    INDEX idx_gal_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE gallery_publish_consents (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id      BIGINT UNSIGNED NOT NULL,
    customer_id   BIGINT UNSIGNED NULL,
    consented_by  VARCHAR(200) NULL,
    source        ENUM('quote_form','contract','manual','email','verbal') NOT NULL DEFAULT 'manual',
    recorded_by   BIGINT UNSIGNED NULL,
    notes         TEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id)    REFERENCES gallery_albums(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id)      ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id)          ON DELETE SET NULL,
    INDEX idx_gpc_album (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS gallery_publish_consents;
DROP TABLE IF EXISTS gallery_access_logs;
DROP TABLE IF EXISTS gallery_access_tokens;
DROP TABLE IF EXISTS gallery_images;
DROP TABLE IF EXISTS gallery_albums;
",
];
