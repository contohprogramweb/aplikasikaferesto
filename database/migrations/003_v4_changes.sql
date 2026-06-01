-- ================================================================
-- Migration: 003_v4_changes.sql
-- Description: Perubahan v4.0 sesuai SRS NFR-MAI-07
-- Applied: 
--   - Tambah is_refunded di orders
--   - Tambah bill_requested_at di orders
--   - Tambah order_counters table
--   - Tambah customer_sessions table
-- ================================================================

-- ================================================================
-- Alter TABLE: orders - Add v4.0 columns
-- ================================================================

-- Tambah kolom is_refunded untuk tracking refund status
ALTER TABLE `orders` 
ADD COLUMN `is_refunded` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag untuk menandai order yang sudah di-refund'
AFTER `payment_status`;

-- Tambah index untuk kolom is_refunded
ALTER TABLE `orders` 
ADD INDEX `idx_orders_is_refunded` (`is_refunded`);

-- Tambah kolom bill_requested_at untuk tracking waktu permintaan bill
ALTER TABLE `orders` 
ADD COLUMN `bill_requested_at` DATETIME DEFAULT NULL COMMENT 'Waktu customer meminta bill/tagihan'
AFTER `completed_at`;

-- Tambah index untuk kolom bill_requested_at
ALTER TABLE `orders` 
ADD INDEX `idx_orders_bill_requested_at` (`bill_requested_at`);

-- ================================================================
-- Create TABLE: order_counters
-- Description: Track order numbers untuk generating unique order IDs
-- ================================================================
CREATE TABLE IF NOT EXISTS `order_counters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `counter_name` VARCHAR(50) NOT NULL COMMENT 'Nama counter (e.g., daily_orders, monthly_orders)',
    `prefix` VARCHAR(10) NOT NULL COMMENT 'Prefix untuk order number',
    `current_value` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nilai counter saat ini',
    `reset_period` ENUM('daily', 'monthly', 'yearly', 'never') NOT NULL DEFAULT 'daily' COMMENT 'Periode reset counter',
    `last_reset_date` DATE DEFAULT NULL COMMENT 'Tanggal terakhir reset',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_counters_name` (`counter_name`),
    KEY `idx_order_counters_reset_period` (`reset_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default order counter
INSERT INTO `order_counters` (`counter_name`, `prefix`, `current_value`, `reset_period`)
VALUES ('daily_orders', 'ORD', 0, 'daily')
ON DUPLICATE KEY UPDATE `prefix` = VALUES(`prefix`);

-- ================================================================
-- Create TABLE: customer_sessions
-- Description: Track customer sessions untuk QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
CREATE TABLE IF NOT EXISTS `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items dalam JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes dari creation/activity',
    `ended_at` DATETIME DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_customer_sessions_token` (`token`),
    KEY `fk_customer_sessions_table` (`table_id`),
    KEY `idx_customer_sessions_status` (`status`),
    KEY `idx_customer_sessions_expires_at` (`expires_at`),
    KEY `idx_customer_sessions_last_activity` (`last_activity`),
    CONSTRAINT `fk_customer_sessions_table`
        FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Update foreign key constraint for orders.customer_session_id
-- ================================================================
-- Drop existing foreign key if exists and recreate with proper reference
ALTER TABLE `orders`
DROP FOREIGN KEY IF EXISTS `fk_orders_customer_session`;

ALTER TABLE `orders`
ADD CONSTRAINT `fk_orders_customer_session`
    FOREIGN KEY (`customer_session_id`) REFERENCES `customer_sessions`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ================================================================
-- Add indexes for customer_sessions related queries
-- ================================================================
ALTER TABLE `orders` 
ADD INDEX `idx_orders_customer_session` (`customer_session_id`);

-- ================================================================
-- Create stored procedure untuk reset daily counter
-- ================================================================
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `reset_daily_order_counter`()
BEGIN
    -- Reset counter untuk daily_orders
    UPDATE `order_counters` 
    SET `current_value` = 0, 
        `last_reset_date` = CURDATE()
    WHERE `reset_period` = 'daily' 
      AND (`last_reset_date` IS NULL OR `last_reset_date` < CURDATE());
END$$

DELIMITER ;

-- ================================================================
-- Create event untuk auto-reset daily counter (jika event scheduler enabled)
-- ================================================================
SET @event_exists = (
    SELECT COUNT(*) 
    FROM information_schema.EVENTS 
    WHERE EVENT_SCHEMA = DATABASE() 
    AND EVENT_NAME = 'daily_counter_reset'
);

SET @sql = IF(@event_exists = 0,
    'CREATE EVENT `daily_counter_reset`
     ON SCHEDULE EVERY 1 DAY
     STARTS CURDATE() + INTERVAL 1 DAY
     DO CALL `reset_daily_order_counter`()',
    'SELECT "Event already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
