-- ================================================================
-- Migration: 002_add_indexes.sql
-- Description: Index tambahan untuk performa
-- Applied: Performance optimization indexes
-- ================================================================

-- ================================================================
-- Additional indexes for orders table
-- ================================================================
ALTER TABLE `orders` ADD INDEX `idx_orders_table_status` (`table_id`, `status`);
ALTER TABLE `orders` ADD INDEX `idx_orders_payment_method` (`payment_method`);
ALTER TABLE `orders` ADD INDEX `idx_orders_order_type` (`order_type`);
ALTER TABLE `orders` ADD INDEX `idx_orders_completed_at` (`completed_at`);

-- ================================================================
-- Additional indexes for order_items table
-- ================================================================
ALTER TABLE `order_items` ADD INDEX `idx_order_items_menu_item` (`menu_item_id`);
ALTER TABLE `order_items` ADD INDEX `idx_order_items_order_status` (`order_id`, `status`);

-- ================================================================
-- Additional indexes for transactions table
-- ================================================================
ALTER TABLE `transactions` ADD INDEX `idx_transactions_payment_method` (`payment_method`);
ALTER TABLE `transactions` ADD INDEX `idx_transactions_processed_at` (`processed_at`);

-- ================================================================
-- Additional indexes for menu_items table
-- ================================================================
ALTER TABLE `menu_items` ADD INDEX `idx_menu_items_price` (`price`);
ALTER TABLE `menu_items` ADD INDEX `idx_menu_items_category_available` (`category_id`, `is_available`);

-- ================================================================
-- Additional indexes for reservations table
-- ================================================================
ALTER TABLE `reservations` ADD INDEX `idx_reservations_table_date` (`table_id`, `reservation_date`);
ALTER TABLE `reservations` ADD INDEX `idx_reservations_customer_phone` (`customer_phone`);

-- ================================================================
-- Additional indexes for inventory tables
-- ================================================================
ALTER TABLE `inventory` ADD INDEX `idx_inventory_category` (`category`);
ALTER TABLE `inventory_movements` ADD INDEX `idx_inventory_movements_reference` (`reference_type`, `reference_id`);

-- ================================================================
-- Additional indexes for purchase_orders table
-- ================================================================
ALTER TABLE `purchase_orders` ADD INDEX `idx_purchase_orders_supplier_status` (`supplier_id`, `status`);
ALTER TABLE `purchase_orders` ADD INDEX `idx_purchase_orders_expected_delivery` (`expected_delivery_date`);

-- ================================================================
-- Additional indexes for expenses table
-- ================================================================
ALTER TABLE `expenses` ADD INDEX `idx_expenses_category_date` (`category_id`, `expense_date`);
ALTER TABLE `expenses` ADD INDEX `idx_expenses_recorded_by` (`recorded_by`);

-- ================================================================
-- Composite index for daily reports
-- ================================================================
ALTER TABLE `reports_daily` ADD INDEX `idx_reports_generated_at` (`generated_at`);

-- ================================================================
-- Full-text search indexes (for MySQL 5.6+)
-- ================================================================
ALTER TABLE `menu_items` ADD FULLTEXT INDEX `ft_menu_items_search` (`name`, `description`);
ALTER TABLE `users` ADD FULLTEXT INDEX `ft_users_search` (`username`, `full_name`, `email`);
ALTER TABLE `expenses` ADD FULLTEXT INDEX `ft_expenses_description` (`description`);
