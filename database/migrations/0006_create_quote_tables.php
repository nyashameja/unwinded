<?php
return [
'up' => "
CREATE TABLE quote_requests (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref           VARCHAR(20) NOT NULL UNIQUE,
    customer_id          BIGINT UNSIGNED NULL,
    customer_name        VARCHAR(200) NOT NULL,
    customer_email       VARCHAR(254) NOT NULL,
    customer_phone       VARCHAR(30) NULL,
    customer_company     VARCHAR(200) NULL,
    customer_whatsapp    VARCHAR(30) NULL,
    preferred_contact    ENUM('email','phone','whatsapp') NOT NULL DEFAULT 'email',
    event_type           VARCHAR(100) NULL,
    preferred_date       DATE NULL,
    alternative_date     DATE NULL,
    start_time           TIME NULL,
    expected_duration    SMALLINT UNSIGNED NULL,
    guest_count          SMALLINT UNSIGNED NULL,
    age_group            VARCHAR(100) NULL,
    location_pref        ENUM('indoor','outdoor','either') NULL,
    has_venue            TINYINT(1) NULL,
    venue_name           VARCHAR(300) NULL,
    venue_address        TEXT NULL,
    venue_city           VARCHAR(100) NULL,
    venue_sourcing_req   TINYINT(1) NOT NULL DEFAULT 0,
    venue_notes          TEXT NULL,
    package_id           BIGINT UNSIGNED NULL,
    preferred_artwork    TEXT NULL,
    event_theme          TEXT NULL,
    colour_palette       VARCHAR(200) NULL,
    branding_req         TEXT NULL,
    decor_req            TEXT NULL,
    food_drink_req       TEXT NULL,
    budget_estimate_cents BIGINT UNSIGNED NULL,
    extra_notes          TEXT NULL,
    contact_consent      TINYINT(1) NOT NULL DEFAULT 0,
    terms_consent        TINYINT(1) NOT NULL DEFAULT 0,
    status               ENUM('new','reviewing','info_required','quote_prepared','quote_sent',
                              'accepted','declined','expired','converted') NOT NULL DEFAULT 'new',
    assigned_to          BIGINT UNSIGNED NULL,
    source_ip            VARCHAR(45) NULL,
    honeypot_flagged     TINYINT(1) NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id)  REFERENCES packages(id)  ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_qr_public_ref  (public_ref),
    INDEX idx_qr_status      (status),
    INDEX idx_qr_customer    (customer_id),
    INDEX idx_qr_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quote_request_attachments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id      BIGINT UNSIGNED NOT NULL,
    media_id        BIGINT UNSIGNED NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id)   REFERENCES media(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quotes (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref           VARCHAR(20) NOT NULL UNIQUE,
    version              SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    request_id           BIGINT UNSIGNED NULL,
    customer_id          BIGINT UNSIGNED NOT NULL,
    created_by           BIGINT UNSIGNED NULL,
    assigned_to          BIGINT UNSIGNED NULL,
    event_type           VARCHAR(100) NULL,
    event_date           DATE NULL,
    guest_count          SMALLINT UNSIGNED NULL,
    venue_name           VARCHAR(300) NULL,
    venue_address        TEXT NULL,
    notes_to_customer    TEXT NULL,
    internal_notes       TEXT NULL,
    subtotal_cents       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    discount_type        ENUM('none','percentage','fixed') NOT NULL DEFAULT 'none',
    discount_value       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_cents          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    deposit_type         ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    deposit_value        DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    deposit_cents        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    vat_mode             ENUM('none','inclusive','exclusive') NOT NULL DEFAULT 'none',
    tax_rate             DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax_amount_cents     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status               ENUM('draft','sent','accepted','declined','expired','superseded') NOT NULL DEFAULT 'draft',
    valid_until          DATE NULL,
    reminded_at          DATETIME NULL,
    accepted_at          DATETIME NULL,
    declined_at          DATETIME NULL,
    accepted_by_name     VARCHAR(200) NULL,
    customer_ip          VARCHAR(45) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id)  REFERENCES quote_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id)      ON DELETE RESTRICT,
    FOREIGN KEY (created_by)  REFERENCES users(id)          ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id)          ON DELETE SET NULL,
    INDEX idx_quotes_public_ref  (public_ref),
    INDEX idx_quotes_status      (status),
    INDEX idx_quotes_customer    (customer_id),
    INDEX idx_quotes_valid_until (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quote_access_tokens (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_id     BIGINT UNSIGNED NOT NULL,
    token_hash   VARCHAR(64) NOT NULL UNIQUE,
    expires_at   DATETIME NULL,
    used_at      DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_qat_quote_id   (quote_id),
    INDEX idx_qat_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quote_items (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_id          BIGINT UNSIGNED NOT NULL,
    sort_order        INT NOT NULL DEFAULT 0,
    type              ENUM('package','extra','custom','discount','travel') NOT NULL DEFAULT 'custom',
    package_id        BIGINT UNSIGNED NULL,
    extra_id          BIGINT UNSIGNED NULL,
    description       VARCHAR(500) NOT NULL,
    unit_price_cents  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    quantity          DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    line_total_cents  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    tax_rate          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (quote_id)   REFERENCES quotes(id)         ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id)       ON DELETE SET NULL,
    FOREIGN KEY (extra_id)   REFERENCES package_extras(id) ON DELETE SET NULL,
    INDEX idx_quote_items_quote (quote_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quote_notes (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_id     BIGINT UNSIGNED NOT NULL,
    user_id      BIGINT UNSIGNED NULL,
    user_name    VARCHAR(200) NULL,
    note         TEXT NOT NULL,
    is_internal  TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE SET NULL,
    INDEX idx_quote_notes_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE quote_status_history (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_id     BIGINT UNSIGNED NULL,
    request_id   BIGINT UNSIGNED NULL,
    from_status  VARCHAR(50) NULL,
    to_status    VARCHAR(50) NOT NULL,
    changed_by   BIGINT UNSIGNED NULL,
    changed_name VARCHAR(200) NULL,
    reason       TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id)   REFERENCES quotes(id)         ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)          ON DELETE SET NULL,
    INDEX idx_qsh_quote   (quote_id),
    INDEX idx_qsh_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS quote_status_history;
DROP TABLE IF EXISTS quote_notes;
DROP TABLE IF EXISTS quote_items;
DROP TABLE IF EXISTS quote_access_tokens;
DROP TABLE IF EXISTS quotes;
DROP TABLE IF EXISTS quote_request_attachments;
DROP TABLE IF EXISTS quote_requests;
",
];
