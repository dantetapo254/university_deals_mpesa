CREATE DATABASE IF NOT EXISTS university_deals
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE university_deals;

CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,

    status ENUM('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',

    merchant_request_id VARCHAR(100) NULL,
    checkout_request_id VARCHAR(100) NULL,

    response_code VARCHAR(20) NULL,
    response_description VARCHAR(255) NULL,

    result_code INT NULL,
    result_description VARCHAR(255) NULL,

    mpesa_receipt VARCHAR(50) NULL,
    paid_amount DECIMAL(12,2) NULL,
    paid_phone VARCHAR(20) NULL,
    transaction_date VARCHAR(30) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order_id (order_id),
    INDEX idx_checkout_request_id (checkout_request_id),
    INDEX idx_mpesa_receipt (mpesa_receipt),
    INDEX idx_status (status)
) ENGINE=InnoDB;
