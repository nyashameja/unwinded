<?php
return [
'up' => "
CREATE TABLE payment_access_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash  CHAR(64) NOT NULL,
    booking_id  BIGINT UNSIGNED NULL,
    order_id    BIGINT UNSIGNED NULL,
    expires_at  DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pat_token (token_hash),
    FOREIGN KEY (booking_id) REFERENCES private_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)   REFERENCES ticket_orders(id)    ON DELETE CASCADE,
    INDEX idx_pat_booking (booking_id),
    INDEX idx_pat_order   (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
",
'down' => "
DROP TABLE IF EXISTS payment_access_tokens;
",
];
