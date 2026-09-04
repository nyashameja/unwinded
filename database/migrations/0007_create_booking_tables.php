<?php
return [
'up' => "
CREATE TABLE private_bookings (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref              VARCHAR(20) NOT NULL UNIQUE,
    quote_id                BIGINT UNSIGNED NULL,
    customer_id             BIGINT UNSIGNED NOT NULL,
    event_type              VARCHAR(100) NULL,
    event_date              DATE NOT NULL,
    event_date_utc          DATETIME NOT NULL,
    start_time              TIME NULL,
    end_time                TIME NULL,
    guest_count             SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    venue_name              VARCHAR(300) NULL,
    venue_address           TEXT NULL,
    venue_city              VARCHAR(100) NULL,
    package_id              BIGINT UNSIGNED NULL,
    subtotal_cents          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    discount_type           ENUM('none','percentage','fixed') NOT NULL DEFAULT 'none',
    discount_value          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount_cents   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_cents             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    deposit_cents           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    deposit_soft_deadline   DATETIME NULL,
    deposit_hard_deadline   DATETIME NULL,
    balance_due_date        DATE NULL,
    amount_paid_cents       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    outstanding_cents       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    booking_status          ENUM('provisional','awaiting_deposit','confirmed','planning',
                                 'ready','completed','cancelled','refunded') NOT NULL DEFAULT 'provisional',
    payment_status          ENUM('unpaid','partially_paid','paid','payment_pending',
                                 'payment_failed','partially_refunded','refunded') NOT NULL DEFAULT 'unpaid',
    creative_direction      TEXT NULL,
    internal_notes          TEXT NULL,
    cancellation_reason     TEXT NULL,
    assigned_to             BIGINT UNSIGNED NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              DATETIME NULL,
    FOREIGN KEY (quote_id)   REFERENCES quotes(id)    ON DELETE SET NULL,
    FOREIGN KEY (customer_id)REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (package_id) REFERENCES packages(id)  ON DELETE SET NULL,
    FOREIGN KEY (assigned_to)REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_pb_public_ref      (public_ref),
    INDEX idx_pb_booking_status  (booking_status),
    INDEX idx_pb_payment_status  (payment_status),
    INDEX idx_pb_customer        (customer_id),
    INDEX idx_pb_event_date      (event_date),
    INDEX idx_pb_deleted         (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE booking_items (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id       BIGINT UNSIGNED NOT NULL,
    sort_order       INT NOT NULL DEFAULT 0,
    type             ENUM('package','extra','custom','discount','travel') NOT NULL DEFAULT 'custom',
    package_id       BIGINT UNSIGNED NULL,
    extra_id         BIGINT UNSIGNED NULL,
    description      VARCHAR(500) NOT NULL,
    unit_price_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    quantity         DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    line_total_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (booking_id) REFERENCES private_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id)          ON DELETE SET NULL,
    FOREIGN KEY (extra_id)   REFERENCES package_extras(id)    ON DELETE SET NULL,
    INDEX idx_booking_items_booking (booking_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE booking_notes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NULL,
    user_name   VARCHAR(200) NULL,
    note        TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES private_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)            ON DELETE SET NULL,
    INDEX idx_booking_notes_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE booking_status_history (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id   BIGINT UNSIGNED NOT NULL,
    from_status  VARCHAR(50) NULL,
    to_status    VARCHAR(50) NOT NULL,
    changed_by   BIGINT UNSIGNED NULL,
    changed_name VARCHAR(200) NULL,
    reason       TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES private_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)            ON DELETE SET NULL,
    INDEX idx_bsh_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE booking_documents (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id   BIGINT UNSIGNED NOT NULL,
    media_id     BIGINT UNSIGNED NULL,
    label        VARCHAR(200) NOT NULL,
    document_type ENUM('contract','proof_of_payment','inspiration','other') NOT NULL DEFAULT 'other',
    notes        TEXT NULL,
    uploaded_by  BIGINT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)  REFERENCES private_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id)    REFERENCES media(id)            ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)            ON DELETE SET NULL,
    INDEX idx_booking_docs_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS booking_documents;
DROP TABLE IF EXISTS booking_status_history;
DROP TABLE IF EXISTS booking_notes;
DROP TABLE IF EXISTS booking_items;
DROP TABLE IF EXISTS private_bookings;
",
];
