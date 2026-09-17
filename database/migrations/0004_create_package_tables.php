<?php
return [
'up' => "
CREATE TABLE packages (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref       VARCHAR(20) NOT NULL UNIQUE,
    name             VARCHAR(200) NOT NULL,
    slug             VARCHAR(250) NOT NULL UNIQUE,
    tagline          VARCHAR(500) NULL,
    description      MEDIUMTEXT NULL,
    pricing_model    ENUM('per_person','fixed','price_on_request') NOT NULL DEFAULT 'per_person',
    base_price_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    min_guests       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    max_guests       SMALLINT UNSIGNED NULL,
    status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    is_featured      TINYINT(1) NOT NULL DEFAULT 0,
    sort_order       INT NOT NULL DEFAULT 0,
    highlight_colour VARCHAR(7) NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       DATETIME NULL,
    INDEX idx_packages_slug    (slug),
    INDEX idx_packages_status  (status),
    INDEX idx_packages_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_features (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id   BIGINT UNSIGNED NOT NULL,
    label        VARCHAR(300) NOT NULL,
    icon         VARCHAR(100) NULL,
    is_included  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    INDEX idx_package_features_pkg (package_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_extras (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200) NOT NULL,
    description    TEXT NULL,
    price_model    ENUM('fixed','per_person','per_unit') NOT NULL DEFAULT 'fixed',
    price_cents    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    min_qty        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    max_qty        SMALLINT UNSIGNED NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    sort_order     INT NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_package_extras_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_extra_links (
    package_id      BIGINT UNSIGNED NOT NULL,
    extra_id        BIGINT UNSIGNED NOT NULL,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (package_id, extra_id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (extra_id)   REFERENCES package_extras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
'down' => "
DROP TABLE IF EXISTS package_extra_links;
DROP TABLE IF EXISTS package_extras;
DROP TABLE IF EXISTS package_features;
DROP TABLE IF EXISTS packages;
",
];
