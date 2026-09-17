<?php
return [
'up' => "
CREATE TABLE payments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref       VARCHAR(20) NOT NULL UNIQUE,
    customer_id      BIGINT UNSIGNED NULL,
    amount_cents     BIGINT UNSIGNED NOT NULL,
    currency         CHAR(3) NOT NULL DEFAULT 'ZAR',
    gateway          VARCHAR(50) NOT NULL DEFAULT 'manual',
    gateway_ref      VARCHAR(200) NULL,
    payment_method   VARCHAR(100) NULL,
    status           ENUM('created','pending','successful','failed',
                          'cancelled','expired','partially_refunded','refunded') NOT NULL DEFAULT 'created',
    notes            TEXT NULL,
    processed_by     BIGINT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id)  REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_payments_public_ref  (public_ref),
    INDEX idx_payments_customer    (customer_id),
    INDEX idx_payments_status      (status),
    INDEX idx_payments_gateway_ref (gateway, gateway_ref),
    INDEX idx_payments_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_allocations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id      BIGINT UNSIGNED NOT NULL,
    payable_type    ENUM('booking','order') NOT NULL,
    payable_id      BIGINT UNSIGNED NOT NULL,
    amount_cents    BIGINT UNSIGNED NOT NULL,
    allocation_type ENUM('deposit','balance','full','adjustment') NOT NULL DEFAULT 'full',
    notes           TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
    INDEX idx_pa_payment  (payment_id),
    INDEX idx_pa_payable  (payable_type, payable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_webhooks (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider         VARCHAR(50) NOT NULL,
    provider_event_id VARCHAR(200) NOT NULL,
    payment_id       BIGINT UNSIGNED NULL,
    payload          MEDIUMTEXT NOT NULL,
    status           ENUM('received','processed','failed','duplicate') NOT NULL DEFAULT 'received',
    error            TEXT NULL,
    received_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at     DATETIME NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_provider_event (provider, provider_event_id),
    INDEX idx_pw_payment   (payment_id),
    INDEX idx_pw_status    (status),
    INDEX idx_pw_received  (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id  BIGINT UNSIGNED NULL,
    event       VARCHAR(100) NOT NULL,
    data        JSON NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    INDEX idx_payment_logs_payment (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refunds (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref           VARCHAR(20) NOT NULL UNIQUE,
    payment_id           BIGINT UNSIGNED NOT NULL,
    amount_cents         BIGINT UNSIGNED NOT NULL,
    reason               TEXT NULL,
    status               ENUM('requested','approved','declined','processed','cancelled') NOT NULL DEFAULT 'requested',
    requested_by_name    VARCHAR(200) NULL,
    requested_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_by          BIGINT UNSIGNED NULL,
    approved_at          DATETIME NULL,
    due_by               DATETIME NULL,
    processed_by         BIGINT UNSIGNED NULL,
    processed_at         DATETIME NULL,
    gateway_ref          VARCHAR(200) NULL,
    notes                TEXT NULL,
    FOREIGN KEY (payment_id)   REFERENCES payments(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by)  REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id)    ON DELETE SET NULL,
    INDEX idx_refunds_payment (payment_id),
    INDEX idx_refunds_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eft_proofs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id    BIGINT UNSIGNED NOT NULL,
    media_id      BIGINT UNSIGNED NULL,
    bank_ref      VARCHAR(200) NULL,
    verified_by   BIGINT UNSIGNED NULL,
    verified_at   DATETIME NULL,
    notes         TEXT NULL,
    uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id)  REFERENCES payments(id) ON DELETE RESTRICT,
    FOREIGN KEY (media_id)    REFERENCES media(id)    ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id)    ON DELETE SET NULL,
    INDEX idx_eft_proofs_payment (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
'down' => "
DROP TABLE IF EXISTS eft_proofs;
DROP TABLE IF EXISTS refunds;
DROP TABLE IF EXISTS payment_logs;
DROP TABLE IF EXISTS payment_webhooks;
DROP TABLE IF EXISTS payment_allocations;
DROP TABLE IF EXISTS payments;
",
];
