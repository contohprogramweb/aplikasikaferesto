-- ================================================================
-- Migration: 001_initial.sql
-- Description: Semua CREATE TABLE dari Bab 7.4.1 SRS v4.0
-- Applied: Initial database schema for Smart Restaurant POS
-- ================================================================

-- ================================================================
-- TABLE: users
-- Description: Store all user accounts (admin, staff, etc.)
-- ================================================================
CREATE TABLE IF NOT EXISTS `users` (
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
CREATE TABLE IF NOT EXISTS `tables` (
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
CREATE TABLE IF NOT EXISTS `categories` (
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
CREATE TABLE IF NOT EXISTS `menu_items` (
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
-- Description: Customer orders (base structure without v4 changes)
-- ================================================================
CREATE TABLE IF NOT EXISTS `orders` (
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
    CONSTRAINT `fk_orders_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: order_items
-- Description: Items in each order
-- ================================================================
CREATE TABLE IF NOT EXISTS `order_items` (
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
-- TABLE: transactions
-- Description: Payment transactions
-- ================================================================
CREATE TABLE IF NOT EXISTS `transactions` (
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
    `notes` TEXT DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transactions_transaction_number` (`transaction_number`),
    KEY `fk_transactions_order` (`order_id`),
    KEY `fk_transactions_processed_by` (`processed_by`),
    KEY `idx_transactions_status` (`status`),
    KEY `idx_transactions_created_at` (`created_at`),
    CONSTRAINT `fk_transactions_order` 
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_transactions_processed_by` 
        FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: reservations
-- Description: Table reservations
-- ================================================================
CREATE TABLE IF NOT EXISTS `reservations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_number` VARCHAR(50) NOT NULL,
    `table_id` INT UNSIGNED NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(100) DEFAULT NULL,
    `guest_count` INT UNSIGNED NOT NULL,
    `reservation_date` DATE NOT NULL,
    `reservation_time` TIME NOT NULL,
    `duration_minutes` INT UNSIGNED DEFAULT 120,
    `status` ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending',
    `special_requests` TEXT DEFAULT NULL,
    `deposit_amount` DECIMAL(10,2) DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `cancelled_at` DATETIME DEFAULT NULL,
    `cancel_reason` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reservations_reservation_number` (`reservation_number`),
    KEY `fk_reservations_table` (`table_id`),
    KEY `fk_reservations_created_by` (`created_by`),
    KEY `idx_reservations_date` (`reservation_date`),
    KEY `idx_reservations_status` (`status`),
    CONSTRAINT `fk_reservations_table` 
        FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_reservations_created_by` 
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: inventory
-- Description: Inventory management
-- ================================================================
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_name` VARCHAR(100) NOT NULL,
    `item_code` VARCHAR(50) NOT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `current_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
    `min_stock` DECIMAL(10,2) DEFAULT 0.00,
    `max_stock` DECIMAL(10,2) DEFAULT 0.00,
    `reorder_point` DECIMAL(10,2) DEFAULT 0.00,
    `supplier` VARCHAR(100) DEFAULT NULL,
    `last_purchase_price` DECIMAL(10,2) DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_inventory_item_code` (`item_code`),
    KEY `idx_inventory_current_stock` (`current_stock`),
    KEY `idx_inventory_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: inventory_movements
-- Description: Track inventory movements (in/out)
-- ================================================================
CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `inventory_id` INT UNSIGNED NOT NULL,
    `movement_type` ENUM('in', 'out', 'adjustment', 'waste') NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `previous_stock` DECIMAL(10,2) NOT NULL,
    `new_stock` DECIMAL(10,2) NOT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'purchase_order, order, adjustment, etc.',
    `reference_id` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `performed_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_inventory_movements_inventory` (`inventory_id`),
    KEY `fk_inventory_movements_performed_by` (`performed_by`),
    KEY `idx_inventory_movements_type` (`movement_type`),
    KEY `idx_inventory_movements_created_at` (`created_at`),
    CONSTRAINT `fk_inventory_movements_inventory` 
        FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inventory_movements_performed_by` 
        FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: suppliers
-- Description: Supplier information
-- ================================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `payment_terms` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_suppliers_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: purchase_orders
-- Description: Purchase orders for inventory
-- ================================================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL,
    `supplier_id` INT UNSIGNED NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_delivery_date` DATE DEFAULT NULL,
    `actual_delivery_date` DATE DEFAULT NULL,
    `status` ENUM('pending', 'ordered', 'received', 'partial', 'cancelled') NOT NULL DEFAULT 'pending',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `ordered_by` INT UNSIGNED DEFAULT NULL,
    `received_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_purchase_orders_po_number` (`po_number`),
    KEY `fk_purchase_orders_supplier` (`supplier_id`),
    KEY `fk_purchase_orders_ordered_by` (`ordered_by`),
    KEY `fk_purchase_orders_received_by` (`received_by`),
    KEY `idx_purchase_orders_status` (`status`),
    KEY `idx_purchase_orders_order_date` (`order_date`),
    CONSTRAINT `fk_purchase_orders_supplier` 
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_orders_ordered_by` 
        FOREIGN KEY (`ordered_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_orders_received_by` 
        FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: purchase_order_items
-- Description: Items in purchase orders
-- ================================================================
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` INT UNSIGNED NOT NULL,
    `inventory_id` INT UNSIGNED NOT NULL,
    `quantity_ordered` DECIMAL(10,2) NOT NULL,
    `quantity_received` DECIMAL(10,2) DEFAULT 0.00,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_po_items_purchase_order` (`purchase_order_id`),
    KEY `fk_po_items_inventory` (`inventory_id`),
    CONSTRAINT `fk_po_items_purchase_order` 
        FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_po_items_inventory` 
        FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: expense_categories
-- Description: Categories for expenses
-- ================================================================
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expense_categories_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: expenses
-- Description: Track business expenses
-- ================================================================
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_number` VARCHAR(50) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `payment_method` ENUM('cash', 'card', 'transfer') DEFAULT NULL,
    `description` TEXT NOT NULL,
    `receipt_image` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `recorded_by` INT UNSIGNED DEFAULT NULL,
    `approved_by` INT UNSIGNED DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_expenses_expense_number` (`expense_number`),
    KEY `fk_expenses_category` (`category_id`),
    KEY `fk_expenses_recorded_by` (`recorded_by`),
    KEY `fk_expenses_approved_by` (`approved_by`),
    KEY `idx_expenses_expense_date` (`expense_date`),
    KEY `idx_expenses_status` (`status`),
    CONSTRAINT `fk_expenses_category` 
        FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_expenses_recorded_by` 
        FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_expenses_approved_by` 
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: reports_daily
-- Description: Daily sales reports
-- ================================================================
CREATE TABLE IF NOT EXISTS `reports_daily` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_date` DATE NOT NULL,
    `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_revenue` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_service_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_discounts` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `cash_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `card_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `qris_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `transfer_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `e_wallet_sales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `generated_by` INT UNSIGNED DEFAULT NULL,
    `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reports_daily_date` (`report_date`),
    KEY `fk_reports_daily_generated_by` (`generated_by`),
    CONSTRAINT `fk_reports_daily_generated_by` 
        FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: settings
-- Description: Application settings
-- ================================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_setting_key` (`setting_key`),
    KEY `fk_settings_updated_by` (`updated_by`),
    CONSTRAINT `fk_settings_updated_by` 
        FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('restaurant_name', 'Smart Restaurant', 'string', 'Nama restoran', 1),
('tax_rate', '10', 'number', 'Persentase pajak (%)', 0),
('service_charge_rate', '5', 'number', 'Persentase service charge (%)', 0),
('currency', 'IDR', 'string', 'Mata uang', 1),
('timezone', 'Asia/Jakarta', 'string', 'Zona waktu', 0);
