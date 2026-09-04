<?php
return [
'up' => "
CREATE TABLE public_events (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref        VARCHAR(20) NOT NULL UNIQUE,
    slug              VARCHAR(250) NOT NULL UNIQUE,
    title             VARCHAR(500) NOT NULL,
    short_description TEXT NULL,
    description       MEDIUMTEXT NULL,
    event_date        DATE NOT NULL,
    event_date_utc    DATETIME NOT NULL,
    start_time        TIME NOT NULL,
    end_time          TIME NULL,
    venue_name        VARCHAR(300) NULL,
    venue_address     TEXT NULL,
    venue_city        VARCHAR(100) NULL,
    map_link          VARCHAR(500) NULL,
    featured_image    VARCHAR(500) NULL,
    total_capacity    SMALLINT UNSIGNED NULL,
    age_restriction   VARCHAR(100) NULL,
    what_is_included  TEXT NULL,
    what_to_bring     TEXT NULL,
    dress_code        VARCHAR(200) NULL,
    cancellation_policy TEXT NULL,
    status            ENUM('draft','scheduled','on_sale','sold_out','sales_closed',
                           'completed','cancelled','postponed') NOT NULL DEFAULT 'draft',
    sales_open_at     DATETIME NULL,
    sales_close_at    DATETIME NULL,
    meta_title        VARCHAR(500) NULL,
    meta_description  VARCHAR(500) NULL,
    og_image          VARCHAR(500) NULL,
    created_by        BIGINT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pe_slug       (slug),
    INDEX idx_pe_public_ref (public_ref),
    INDEX idx_pe_status     (status),
    INDEX idx_pe_event_date (event_date),
    INDEX idx_pe_deleted    (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE event_images (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id    BIGINT UNSIGNED NOT NULL,
    media_id    BIGINT UNSIGNED NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    FOREIGN KEY (event_id) REFERENCES public_events(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id)         ON DELETE CASCADE,
    INDEX idx_event_images_event (event_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE event_ticket_types (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    description     TEXT NULL,
    admissions      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    price_cents     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    qty_available   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    qty_reserved    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    qty_sold        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    min_per_order   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    max_per_order   TINYINT UNSIGNED NOT NULL DEFAULT 10,
    sales_open_at   DATETIME NULL,
    sales_close_at  DATETIME NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES public_events(id) ON DELETE CASCADE,
    CHECK (qty_reserved + qty_sold <= qty_available),
    INDEX idx_ett_event  (event_id, sort_order),
    INDEX idx_ett_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS event_ticket_types;
DROP TABLE IF EXISTS event_images;
DROP TABLE IF EXISTS public_events;
",
];
