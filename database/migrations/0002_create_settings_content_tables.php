<?php
return [
'up' => "
CREATE TABLE settings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(150) NOT NULL UNIQUE,
    `value`     MEDIUMTEXT NULL,
    type        ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
    group_name  VARCHAR(100) NOT NULL DEFAULT 'general',
    label       VARCHAR(200) NOT NULL DEFAULT '',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_settings_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE redirects (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_url   VARCHAR(500) NOT NULL UNIQUE,
    target_url   VARCHAR(500) NOT NULL,
    status_code  SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    hit_count    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_redirects_source (source_url),
    INDEX idx_redirects_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE not_found_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url         VARCHAR(1000) NOT NULL,
    referrer    VARCHAR(1000) NULL,
    ip_address  VARCHAR(45) NULL,
    user_agent  VARCHAR(500) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nfl_url        (url(191)),
    INDEX idx_nfl_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE menus (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    label       VARCHAR(200) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE menu_items (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id      BIGINT UNSIGNED NOT NULL,
    parent_id    BIGINT UNSIGNED NULL,
    label        VARCHAR(200) NOT NULL,
    url          VARCHAR(500) NOT NULL DEFAULT '',
    target       VARCHAR(20) NOT NULL DEFAULT '_self',
    sort_order   INT NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id)   REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_menu_items_menu   (menu_id),
    INDEX idx_menu_items_parent (parent_id),
    INDEX idx_menu_items_sort   (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pages (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug             VARCHAR(250) NOT NULL UNIQUE,
    title            VARCHAR(500) NOT NULL,
    meta_description VARCHAR(500) NULL,
    og_image         VARCHAR(500) NULL,
    content          MEDIUMTEXT NULL,
    template         VARCHAR(100) NOT NULL DEFAULT 'default',
    status           ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
    published_at     DATETIME NULL,
    scheduled_at     DATETIME NULL,
    is_system        TINYINT(1) NOT NULL DEFAULT 0,
    sort_order       INT NOT NULL DEFAULT 0,
    created_by       BIGINT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pages_slug      (slug),
    INDEX idx_pages_status    (status),
    INDEX idx_pages_deleted   (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE page_revisions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id     BIGINT UNSIGNED NOT NULL,
    title       VARCHAR(500) NOT NULL,
    content     MEDIUMTEXT NULL,
    saved_by    BIGINT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id)  REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (saved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_page_revisions_page (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE homepage_sections (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key  VARCHAR(100) NOT NULL UNIQUE,
    label        VARCHAR(200) NOT NULL,
    is_visible   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    config       JSON NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_homepage_sections_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE experiences (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug             VARCHAR(250) NOT NULL UNIQUE,
    type             ENUM('corporate','restaurant','private','other') NOT NULL DEFAULT 'other',
    title            VARCHAR(500) NOT NULL,
    short_desc       TEXT NULL,
    body             MEDIUMTEXT NULL,
    hero_image       VARCHAR(500) NULL,
    meta_title       VARCHAR(500) NULL,
    meta_description VARCHAR(500) NULL,
    cta_text         VARCHAR(200) NULL,
    cta_url          VARCHAR(500) NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_experiences_slug   (slug),
    INDEX idx_experiences_type   (type),
    INDEX idx_experiences_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS experiences;
DROP TABLE IF EXISTS homepage_sections;
DROP TABLE IF EXISTS page_revisions;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menus;
DROP TABLE IF EXISTS not_found_log;
DROP TABLE IF EXISTS redirects;
DROP TABLE IF EXISTS settings;
",
];
