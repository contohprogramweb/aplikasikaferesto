-- ================================================================
-- Smart Restaurant POS Database Schema
-- Based on SRS v4.0 Bab 7.4.1 (SQL DDL)
-- ================================================================
-- Database: smart_restaurant_pos
-- Charset: utf8mb4
-- Collation: utf8mb4_unicode_ci
-- Engine: InnoDB
-- ================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS `smart_restaurant_pos`
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `smart_restaurant_pos`;

-- ================================================================
-- TABLE: users
-- Description: Store all user accounts (admin, staff, etc.)
-- ================================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'staff', 'waiter', 'cashier', 'kitchen') NOT NULL DEFAULT 'staff',
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_is_active` (`is_active`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: tables
-- Description: Store restaurant table information
-- ================================================================
DROP TABLE IF EXISTS `tables`;
CREATE TABLE `tables` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `table_number` VARCHAR(10) NOT NULL,
    `table_name` VARCHAR(50) DEFAULT NULL,
    `capacity` INT UNSIGNED NOT NULL DEFAULT 4,
    `location` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('available', 'occupied', 'reserved', 'maintenance') NOT NULL DEFAULT 'available',
    `qr_code` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tables_table_number` (`table_number`),
    KEY `idx_tables_status` (`status`),
    KEY `idx_tables_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: categories
-- Description: Menu item categories
-- ================================================================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_categories_sort_order` (`sort_order`),
    KEY `idx_categories_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: menu_items
-- Description: Restaurant menu items
-- ================================================================
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `cost` DECIMAL(10,2) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `sku` VARCHAR(50) DEFAULT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `preparation_time` INT UNSIGNED DEFAULT NULL COMMENT 'in minutes',
    `allergens` VARCHAR(255) DEFAULT NULL,
    `nutritional_info` JSON DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_menu_items_category` (`category_id`),
    KEY `idx_menu_items_is_available` (`is_available`),
    KEY `idx_menu_items_is_featured` (`is_featured`),
    KEY `idx_menu_items_sort_order` (`sort_order`),
    CONSTRAINT `fk_menu_items_category` 
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: orders
-- Description: Customer orders
-- ================================================================
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(50) NOT NULL,
    `table_id` INT UNSIGNED NOT NULL,
    `customer_session_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'Staff who created the order',
    `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `order_type` ENUM('dine_in', 'takeaway', 'delivery') NOT NULL DEFAULT 'dine_in',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `service_charge_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `payment_status` ENUM('unpaid', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
    `payment_method` ENUM('cash', 'card', 'qris', 'transfer', 'e_wallet') DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `confirmed_at` DATETIME DEFAULT NULL,
    `prepared_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    `cancel_reason` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_order_number` (`order_number`),
    KEY `fk_orders_table` (`table_id`),
    KEY `fk_orders_customer_session` (`customer_session_id`),
    KEY `fk_orders_user` (`user_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    KEY `idx_orders_created_at` (`created_at`),
    CONSTRAINT `fk_orders_table` 
        FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_customer_session` 
        FOREIGN KEY (`customer_session_id`) REFERENCES `customer_sessions`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: order_items
-- Description: Items in each order
-- ================================================================
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `menu_item_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_order_items_order` (`order_id`),
    KEY `fk_order_items_menu_item` (`menu_item_id`),
    KEY `idx_order_items_status` (`status`),
    CONSTRAINT `fk_order_items_order` 
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_menu_item` 
        FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: customer_sessions
-- Description: Track customer sessions for QR ordering
-- Based on SRS v4.0 - Customer Session Architecture
-- ================================================================
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL COMMENT 'Token format: tbl_[table_id]_[random_hash_16char]',
    `table_id` INT UNSIGNED NOT NULL,
    `cart_data` JSON DEFAULT NULL COMMENT 'Cart items in JSON format',
    `status` ENUM('active', 'expired', 'ended') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Session expires at +30 minutes from creation/activity',
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
-- TABLE: order_counters
-- Description: Track order numbers for generating unique order IDs
-- ================================================================
DROP TABLE IF EXISTS `order_counters`;
CREATE TABLE `order_counters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `counter_name` VARCHAR(50) NOT NULL,
    `prefix` VARCHAR(10) NOT NULL,
    `current_value` INT UNSIGNED NOT NULL DEFAULT 0,
    `reset_period` ENUM('daily', 'monthly', 'yearly', 'never') NOT NULL DEFAULT 'daily',
    `last_reset_date` DATE DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_counters_name` (`counter_name`),
    KEY `idx_order_counters_reset_period` (`reset_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: transactions
-- Description: Payment transactions
-- ================================================================
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `payment_method` ENUM('cash', 'card', 'qris', 'transfer', 'e_wallet') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `change_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `payment_gateway_response` JSON DEFAULT NULL,
    `processed_by` INT UNSIGNED DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transactions_transaction_number` (`transaction_number`),
    KEY `fk_transactions_order` (`order_id`),
    KEY `fk_transactions_processed_by` (`processed_by`),
    KEY `idx_transactions_status` (`status`),
    KEY `idx_transactions_payment_method` (`payment_method`),
    KEY `idx_transactions_created_at` (`created_at`),
    CONSTRAINT `fk_transactions_order` 
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_transactions_processed_by` 
        FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: activity_logs
-- Description: System activity logs for auditing
-- ================================================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `related_table` VARCHAR(50) DEFAULT NULL,
    `related_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_activity_logs_user` (`user_id`),
    KEY `idx_activity_logs_action` (`action`),
    KEY `idx_activity_logs_related` (`related_table`, `related_id`),
    KEY `idx_activity_logs_priority` (`priority`),
    KEY `idx_activity_logs_created_at` (`created_at`),
    CONSTRAINT `fk_activity_logs_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SEED DATA
-- ================================================================

-- Insert default admin user
-- Password: Admin123 (hashed with BCRYPT cost 10)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `phone`, `is_active`) 
VALUES 
('admin', 'admin@smartrestaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '+62 812 3456 7890', 1);

-- Insert 5 tables
INSERT INTO `tables` (`table_number`, `table_name`, `capacity`, `location`, `status`, `is_active`) 
VALUES 
('T001', 'Table 1', 4, 'Main Hall', 'available', 1),
('T002', 'Table 2', 4, 'Main Hall', 'available', 1),
('T003', 'Table 3', 6, 'Main Hall', 'available', 1),
('T004', 'Table 4', 2, 'Window Side', 'available', 1),
('T005', 'Table 5', 8, 'VIP Area', 'available', 1);

-- Insert 3 categories
INSERT INTO `categories` (`name`, `description`, `icon`, `sort_order`, `is_active`) 
VALUES 
('Main Course', 'Delicious main dishes', 'fa-utensils', 1, 1),
('Beverages', 'Refreshing drinks', 'fa-coffee', 2, 1),
('Desserts', 'Sweet treats', 'fa-ice-cream', 3, 1);

-- Insert 10 menu items
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `cost`, `is_available`, `is_featured`, `preparation_time`, `sort_order`) 
VALUES 
(1, 'Nasi Goreng Spesial', 'Special fried rice with chicken, egg, and vegetables', 45000.00, 20000.00, 1, 1, 15, 1),
(1, 'Mie Goreng Jawa', 'Javanese style fried noodles', 40000.00, 18000.00, 1, 0, 15, 2),
(1, 'Ayam Bakar Madu', 'Honey grilled chicken with steamed rice', 55000.00, 25000.00, 1, 1, 20, 3),
(1, 'Sate Ayam Lontong', 'Chicken satay with rice cake', 50000.00, 22000.00, 1, 0, 20, 4),
(1, 'Ikan Bakar Jimbaran', 'Jimbaran style grilled fish', 65000.00, 30000.00, 1, 1, 25, 5),
(2, 'Es Teh Manis', 'Sweet iced tea', 10000.00, 3000.00, 1, 0, 5, 1),
(2, 'Jus Jeruk Segar', 'Fresh orange juice', 18000.00, 8000.00, 1, 1, 5, 2),
(2, 'Kopi Hitam', 'Black coffee', 15000.00, 5000.00, 1, 0, 5, 3),
(3, 'Es Krim Coklat', 'Chocolate ice cream', 25000.00, 10000.00, 1, 1, 5, 1),
(3, 'Puding Mangga', 'Mango pudding', 22000.00, 9000.00, 1, 0, 5, 2);

-- Insert order counter
INSERT INTO `order_counters` (`counter_name`, `prefix`, `current_value`, `reset_period`) 
VALUES 
('daily_orders', 'ORD', 0, 'daily');

-- ================================================================
-- END OF SCHEMA
-- ================================================================
