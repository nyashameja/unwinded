<?php
return [
'up' => "
CREATE TABLE customers (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_ref            VARCHAR(20) NOT NULL UNIQUE,
    name                  VARCHAR(200) NOT NULL,
    company               VARCHAR(200) NULL,
    email                 VARCHAR(254) NOT NULL,
    phone                 VARCHAR(30) NULL,
    whatsapp              VARCHAR(30) NULL,
    preferred_contact     ENUM('email','phone','whatsapp') NOT NULL DEFAULT 'email',
    marketing_consent     TINYINT(1) NOT NULL DEFAULT 0,
    consent_at            DATETIME NULL,
    notes                 TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            DATETIME NULL,
    INDEX idx_customers_email      (email),
    INDEX idx_customers_public_ref (public_ref),
    INDEX idx_customers_deleted    (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_consents (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id   BIGINT UNSIGNED NOT NULL,
    consent_type  VARCHAR(100) NOT NULL,
    given         TINYINT(1) NOT NULL DEFAULT 1,
    source        VARCHAR(200) NULL,
    ip_address    VARCHAR(45) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_consents_cust (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
'down' => "
DROP TABLE IF EXISTS customer_consents;
DROP TABLE IF EXISTS customers;
",
];
