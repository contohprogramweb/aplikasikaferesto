-- ================================================================
-- Migration: 004_seed_data.sql
-- Description: Data awal untuk Smart Restaurant POS
-- Applied: Initial seed data
-- ================================================================

-- ================================================================
-- Admin User
-- Password: Admin123 (hashed dengan BCRYPT cost 10)
-- Hash generated using: password_hash('Admin123', PASSWORD_BCRYPT, ['cost' => 10])
-- ================================================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `phone`, `is_active`)
VALUES 
('admin', 'admin@smartrestaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '+62 812 3456 7890', 1)
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `full_name` = VALUES(`full_name`),
    `role` = VALUES(`role`);

-- ================================================================
-- Categories (Kategori Menu)
-- ================================================================
INSERT INTO `categories` (`name`, `description`, `icon`, `sort_order`, `is_active`)
VALUES 
('Makanan Utama', 'Hidangan utama dan makanan berat', 'fa-utensils', 1, 1),
('Minuman', 'Aneka minuman segar dan panas', 'fa-glass-martini', 2, 1),
('Dessert', 'Makanan penutup dan hidangan manis', 'fa-ice-cream', 3, 1)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `sort_order` = VALUES(`sort_order`);

-- ================================================================
-- Tables (5 Meja)
-- ================================================================
INSERT INTO `tables` (`table_number`, `table_name`, `capacity`, `location`, `status`, `is_active`)
VALUES 
('A01', 'Table A01', 4, 'Indoor', 'available', 1),
('A02', 'Table A02', 4, 'Indoor', 'available', 1),
('B01', 'Table B01', 6, 'Outdoor', 'available', 1),
('VIP1', 'VIP Room 1', 8, 'VIP', 'available', 1),
('VIP2', 'VIP Room 2', 8, 'VIP', 'available', 1)
ON DUPLICATE KEY UPDATE 
    `table_name` = VALUES(`table_name`),
    `capacity` = VALUES(`capacity`),
    `location` = VALUES(`location`);

-- ================================================================
-- Menu Items (10 Items)
-- Note: category_id mengacu pada ID dari insert categories di atas
-- ================================================================

-- Makanan Utama (category_id = 1)
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `cost`, `is_available`, `is_featured`, `preparation_time`, `sort_order`)
VALUES 
(1, 'Nasi Goreng Spesial', 'Nasi goreng dengan ayam, telur, dan sayuran', 25000.00, 12000.00, 1, 1, 15, 1),
(1, 'Mie Ayam Bakso', 'Mie ayam dengan bakso homemade', 22000.00, 10000.00, 1, 0, 15, 2)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `price` = VALUES(`price`);

-- Minuman (category_id = 2)
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `cost`, `is_available`, `is_featured`, `preparation_time`, `sort_order`)
VALUES 
(2, 'Es Teh Manis', 'Teh manis dengan es', 8000.00, 3000.00, 1, 0, 5, 1),
(2, 'Kopi Latte', 'Kopi espresso dengan susu', 15000.00, 6000.00, 1, 1, 5, 2)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `price` = VALUES(`price`);

-- Dessert (category_id = 3)
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `cost`, `is_available`, `is_featured`, `preparation_time`, `sort_order`)
VALUES 
(3, 'Pudding Coklat', 'Pudding coklat dengan vla vanila', 12000.00, 5000.00, 1, 0, 5, 1)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `price` = VALUES(`price`);

-- ================================================================
-- Expense Categories
-- ================================================================
INSERT INTO `expense_categories` (`name`, `description`, `is_active`)
VALUES 
('Bahan Baku', 'Pembelian bahan baku makanan dan minuman', 1),
('Operasional', 'Biaya operasional harian (listrik, air, gas)', 1),
('Gaji Karyawan', 'Pembayaran gaji karyawan', 1),
('Maintenance', 'Biaya perawatan dan perbaikan', 1),
('Marketing', 'Biaya promosi dan marketing', 1)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`);

-- ================================================================
-- Supplier (Contoh)
-- ================================================================
INSERT INTO `suppliers` (`name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `is_active`)
VALUES 
('PT Sumber Pangan Jaya', 'Budi Santoso', '+62 812 1111 2222', 'budi@sumberpangan.com', 'Jl. Raya Bogor No. 123, Jakarta', 'NET 30', 1),
('CV Berkah Sentosa', 'Siti Aminah', '+62 813 3333 4444', 'siti@berkahsentosa.com', 'Jl. Sudirman No. 45, Bandung', 'COD', 1)
ON DUPLICATE KEY UPDATE 
    `contact_person` = VALUES(`contact_person`),
    `phone` = VALUES(`phone`);

-- ================================================================
-- Inventory Items (Contoh)
-- ================================================================
INSERT INTO `inventory` (`item_name`, `item_code`, `category`, `current_stock`, `unit`, `min_stock`, `supplier`, `last_purchase_price`, `is_active`)
VALUES 
('Beras Premium', 'INV-001', 'Bahan Baku', 50.00, 'kg', 20.00, 'PT Sumber Pangan Jaya', 12000.00, 1),
('Minyak Goreng', 'INV-002', 'Bahan Baku', 20.00, 'liter', 10.00, 'PT Sumber Pangan Jaya', 25000.00, 1),
('Gula Pasir', 'INV-003', 'Bahan Baku', 30.00, 'kg', 15.00, 'PT Sumber Pangan Jaya', 14000.00, 1),
('Teh Celup', 'INV-004', 'Bahan Baku', 10.00, 'box', 5.00, 'CV Berkah Sentosa', 35000.00, 1),
('Kopi Bubuk', 'INV-005', 'Bahan Baku', 5.00, 'kg', 2.00, 'CV Berkah Sentosa', 80000.00, 1)
ON DUPLICATE KEY UPDATE 
    `category` = VALUES(`category`),
    `current_stock` = VALUES(`current_stock`);

-- ================================================================
-- Order Counter
-- ================================================================
INSERT INTO `order_counters` (`counter_name`, `prefix`, `current_value`, `reset_period`)
VALUES 
('daily_orders', 'ORD', 0, 'daily')
ON DUPLICATE KEY UPDATE 
    `prefix` = VALUES(`prefix`),
    `reset_period` = VALUES(`reset_period`);

-- ================================================================
-- Default Settings Update
-- ================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`)
VALUES 
('restaurant_name', 'Smart Restaurant', 'string', 'Nama restoran', 1),
('tax_rate', '10', 'number', 'Persentase pajak (%)', 0),
('service_charge_rate', '5', 'number', 'Persentase service charge (%)', 0),
('currency', 'IDR', 'string', 'Mata uang', 1),
('timezone', 'Asia/Jakarta', 'string', 'Zona waktu', 0),
('welcome_message', 'Selamat datang di Smart Restaurant!', 'string', 'Pesan selamat datang untuk customer', 1),
('qr_session_timeout', '30', 'number', 'Timeout sesi QR dalam menit', 0)
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `description` = VALUES(`description`);

-- ================================================================
-- Sample Activity Log Entry (untuk testing)
-- ================================================================
INSERT INTO `activity_logs` (`ip_address`, `user_id`, `action`, `category`, `timestamp`, `details`)
VALUES 
('127.0.0.1', 'admin', 'system_init', 'system', NOW(), '{"message": "Initial seed data loaded"}');
