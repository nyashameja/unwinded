<?php
return [
'up' => "
CREATE TABLE discount_codes (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code              VARCHAR(50) NOT NULL UNIQUE,
    description       VARCHAR(500) NULL,
    discount_type     ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    discount_value    DECIMAL(10,2) NOT NULL,
    min_order_cents   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    max_uses          INT UNSIGNED NULL,
    max_uses_per_user INT UNSIGNED NOT NULL DEFAULT 1,
    times_used        INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at         DATETIME NULL,
    expires_at        DATETIME NULL,
    applies_to        ENUM('tickets','bookings','both') NOT NULL DEFAULT 'tickets',
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_discount_codes_code    (code),
    INDEX idx_discount_codes_active  (is_active),
    INDEX idx_discount_codes_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discount_code_usage (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discount_id     BIGINT UNSIGNED NOT NULL,
    customer_id     BIGINT UNSIGNED NULL,
    customer_email  VARCHAR(254) NULL,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       BIGINT UNSIGNED NOT NULL,
    discount_cents  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discount_id) REFERENCES discount_codes(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)      ON DELETE SET NULL,
    INDEX idx_dcu_discount  (discount_id),
    INDEX idx_dcu_customer  (customer_id),
    INDEX idx_dcu_entity    (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
'down' => "
DROP TABLE IF EXISTS discount_code_usage;
DROP TABLE IF EXISTS discount_codes;
",
];
