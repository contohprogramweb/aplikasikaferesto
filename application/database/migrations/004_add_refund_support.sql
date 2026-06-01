-- Migration: Add Refund and Report Support Tables
-- Version: 4.1.0
-- Date: 2024-06-01
-- Description: Menambahkan tabel untuk refund logs, void logs, dan kolom tambahan untuk transaksi

-- =====================================================
-- 1. Tambah kolom refund di tabel transactions
-- =====================================================
ALTER TABLE `transactions` 
ADD COLUMN `is_refunded` TINYINT(1) DEFAULT 0 COMMENT '0=belum, 1=sudah refund',
ADD COLUMN `refunded_at` DATETIME NULL COMMENT 'Waktu refund diproses',
ADD COLUMN `refund_reason` TEXT NULL COMMENT 'Alasan refund',
ADD COLUMN `refund_amount` DECIMAL(10,2) NULL COMMENT 'Jumlah yang di-refund',
ADD COLUMN `refund_by` INT NULL COMMENT 'User ID yang memproses refund',
ADD CONSTRAINT `fk_transactions_refund_user` FOREIGN KEY (`refund_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- =====================================================
-- 2. Tambah kolom void di tabel orders
-- =====================================================
ALTER TABLE `orders`
ADD COLUMN `void_reason` TEXT NULL COMMENT 'Alasan void order',
ADD COLUMN `voided_at` DATETIME NULL COMMENT 'Waktu void',
ADD COLUMN `voided_by` INT NULL COMMENT 'User ID yang memproses void',
ADD CONSTRAINT `fk_orders_void_user` FOREIGN KEY (`voided_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- =====================================================
-- 3. Tabel refund_logs (Audit trail refund)
-- =====================================================
CREATE TABLE IF NOT EXISTS `refund_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `refund_amount` DECIMAL(10,2) NOT NULL,
    `refund_type` ENUM('full', 'partial') NOT NULL DEFAULT 'full',
    `reason` TEXT NOT NULL,
    `processed_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_transaction_id` (`transaction_id`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_refund_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_user` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. Tabel void_logs (Audit trail void order)
-- =====================================================
CREATE TABLE IF NOT EXISTS `void_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `processed_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_void_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_void_user` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. Update status enum di orders untuk refund/void
-- =====================================================
ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM('baru', 'diterima', 'dimasak', 'siap', 'disajikan', 'completed', 'cancelled', 'refunded', 'voided') DEFAULT 'baru';

-- =====================================================
-- 6. Update status enum di order_items
-- =====================================================
ALTER TABLE `order_items`
MODIFY COLUMN `status` ENUM('baru', 'diterima', 'dimasak', 'siap', 'disajikan', 'cancelled', 'voided') DEFAULT 'baru';

-- =====================================================
-- 7. Update status enum di transactions
-- =====================================================
ALTER TABLE `transactions`
MODIFY COLUMN `status` ENUM('pending', 'paid', 'refunded', 'partial_refunded') DEFAULT 'pending';

-- =====================================================
-- 8. Insert permission untuk refund (jika ada tabel permissions)
-- =====================================================
-- INSERT INTO `permissions` (`name`, `description`, `created_at`) VALUES 
-- ('refund.create', 'Can create refund', NOW()),
-- ('refund.view', 'Can view refund history', NOW()),
-- ('void.create', 'Can void orders', NOW());

-- =====================================================
-- SEED DATA (Optional - untuk testing)
-- =====================================================
-- Tidak ada seed data untuk tabel audit
