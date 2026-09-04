<?php
return [
'up' => "
CREATE TABLE ticket_orders (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref            VARCHAR(20) NOT NULL UNIQUE,
    event_id              BIGINT UNSIGNED NOT NULL,
    customer_id           BIGINT UNSIGNED NULL,
    purchaser_name        VARCHAR(200) NOT NULL,
    purchaser_email       VARCHAR(254) NOT NULL,
    purchaser_phone       VARCHAR(30) NULL,
    discount_id           BIGINT UNSIGNED NULL,
    discount_code         VARCHAR(50) NULL,
    discount_amount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    subtotal_cents        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_cents           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status                ENUM('pending','paid','cancelled','refunded',
                               'requires_attention','expired') NOT NULL DEFAULT 'pending',
    reserved_until        DATETIME NULL,
    paid_at               DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id)    REFERENCES public_events(id)  ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)      ON DELETE SET NULL,
    FOREIGN KEY (discount_id) REFERENCES discount_codes(id) ON DELETE SET NULL,
    INDEX idx_to_public_ref  (public_ref),
    INDEX idx_to_status      (status),
    INDEX idx_to_event       (event_id),
    INDEX idx_to_customer    (customer_id),
    INDEX idx_to_reserved    (reserved_until),
    INDEX idx_to_purchaser   (purchaser_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_items (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id         BIGINT UNSIGNED NOT NULL,
    ticket_type_id   BIGINT UNSIGNED NOT NULL,
    quantity         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price_cents BIGINT UNSIGNED NOT NULL,
    line_total_cents BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (order_id)       REFERENCES ticket_orders(id)     ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES event_ticket_types(id) ON DELETE RESTRICT,
    INDEX idx_order_items_order  (order_id),
    INDEX idx_order_items_type   (ticket_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE ticket_reservations (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_type_id BIGINT UNSIGNED NOT NULL,
    order_id       BIGINT UNSIGNED NULL,
    quantity       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    expires_at     DATETIME NOT NULL,
    released_at    DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_type_id) REFERENCES event_ticket_types(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)       REFERENCES ticket_orders(id)      ON DELETE CASCADE,
    INDEX idx_tr_type    (ticket_type_id),
    INDEX idx_tr_order   (order_id),
    INDEX idx_tr_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tickets (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_uid       VARCHAR(40) NOT NULL UNIQUE,
    order_id         BIGINT UNSIGNED NOT NULL,
    order_item_id    BIGINT UNSIGNED NOT NULL,
    ticket_type_id   BIGINT UNSIGNED NOT NULL,
    event_id         BIGINT UNSIGNED NOT NULL,
    attendee_name    VARCHAR(200) NULL,
    qr_payload       VARCHAR(500) NOT NULL,
    status           ENUM('valid','used','cancelled','refunded') NOT NULL DEFAULT 'valid',
    issued_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)       REFERENCES ticket_orders(id)      ON DELETE RESTRICT,
    FOREIGN KEY (order_item_id)  REFERENCES order_items(id)        ON DELETE RESTRICT,
    FOREIGN KEY (ticket_type_id) REFERENCES event_ticket_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (event_id)       REFERENCES public_events(id)      ON DELETE RESTRICT,
    INDEX idx_tickets_order  (order_id),
    INDEX idx_tickets_event  (event_id),
    INDEX idx_tickets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE ticket_checkins (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    checked_in_by  BIGINT UNSIGNED NULL,
    checked_in_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    method         ENUM('qr_scan','manual','override') NOT NULL DEFAULT 'qr_scan',
    override_reason TEXT NULL,
    override_by    BIGINT UNSIGNED NULL,
    ip_address     VARCHAR(45) NULL,
    FOREIGN KEY (ticket_id)    REFERENCES tickets(id) ON DELETE RESTRICT,
    FOREIGN KEY (checked_in_by)REFERENCES users(id)   ON DELETE SET NULL,
    FOREIGN KEY (override_by)  REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS ticket_checkins;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS ticket_reservations;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS ticket_orders;
",
];
