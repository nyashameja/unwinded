<?php
return [
'up' => "
CREATE TABLE media (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref      VARCHAR(20) NOT NULL UNIQUE,
    filename        VARCHAR(255) NOT NULL,
    original_name   VARCHAR(500) NOT NULL,
    mime_type       VARCHAR(100) NOT NULL,
    extension       VARCHAR(10) NOT NULL,
    size_bytes      INT UNSIGNED NOT NULL DEFAULT 0,
    width           INT UNSIGNED NULL,
    height          INT UNSIGNED NULL,
    alt_text        VARCHAR(500) NULL,
    caption         TEXT NULL,
    storage_path    VARCHAR(1000) NOT NULL,
    is_private      TINYINT(1) NOT NULL DEFAULT 0,
    year            SMALLINT UNSIGNED NOT NULL,
    month           TINYINT UNSIGNED NOT NULL,
    uploaded_by     BIGINT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_media_public_ref  (public_ref),
    INDEX idx_media_private     (is_private),
    INDEX idx_media_year_month  (year, month),
    INDEX idx_media_deleted     (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_usages (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id      BIGINT UNSIGNED NOT NULL,
    entity_type   VARCHAR(100) NOT NULL,
    entity_id     BIGINT UNSIGNED NOT NULL,
    field_name    VARCHAR(100) NOT NULL DEFAULT 'image',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_usage (media_id, entity_type, entity_id, field_name),
    INDEX idx_media_usages_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
'down' => "
DROP TABLE IF EXISTS media_usages;
DROP TABLE IF EXISTS media;
",
];
