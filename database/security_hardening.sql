-- Security & Database Hardening Script
-- Jalankan script ini untuk meningkatkan keamanan database

-- 1. Pastikan semua tabel menggunakan InnoDB dan utf8mb4
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE tables CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE orders CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE order_items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE sessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE audit_logs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE order_counters CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- 2. Tambahkan Foreign Key Constraints jika belum ada
-- Orders -> Users (cashier)
ALTER TABLE orders ADD CONSTRAINT fk_orders_cashier 
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL;

-- Orders -> Tables
ALTER TABLE orders ADD CONSTRAINT fk_orders_table 
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE RESTRICT;

-- Order Items -> Orders
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order 
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

-- Order Items -> Items
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_item 
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT;

-- Transactions -> Orders
ALTER TABLE transactions ADD CONSTRAINT fk_transactions_order 
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

-- Transactions -> Users (cashier)
ALTER TABLE transactions ADD CONSTRAINT fk_transactions_cashier 
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL;

-- Audit Logs -> Users
ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_logs_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 3. Tambahkan Index untuk performa query
-- Orders: status, table_id, created_at
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_table_id ON orders(table_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_status_created ON orders(status, created_at);

-- Order Items: order_id, status
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_status ON order_items(status);

-- Sessions: user_id, last_activity
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- Audit Logs: user_id, action, created_at
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX idx_audit_logs_composite ON audit_logs(user_id, action, created_at);

-- Items: category_id, is_available
CREATE INDEX idx_items_category_id ON items(category_id);
CREATE INDEX idx_items_available ON items(is_available);

-- Tables: status
CREATE INDEX idx_tables_status ON tables(status);

-- 4. Buat tabel audit_logs jika belum ada
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT UNSIGNED NOT NULL,
    old_value JSON DEFAULT NULL,
    new_value JSON DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit_logs_user_id (user_id),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Buat tabel rate_limits untuk tracking request
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(64) NOT NULL, -- IP address atau user_id
    endpoint VARCHAR(100) NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 1,
    first_request_at DATETIME NOT NULL,
    last_request_at DATETIME NOT NULL,
    UNIQUE KEY unique_identifier_endpoint (identifier, endpoint),
    INDEX idx_rate_limits_identifier (identifier),
    INDEX idx_rate_limits_endpoint (endpoint),
    INDEX idx_rate_limits_last_request (last_request_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Update kolom password di users menjadi VARCHAR(255) untuk bcrypt
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- 7. Tambahkan kolom security-related di tabel users
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL AFTER password;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) DEFAULT NULL AFTER last_login_at;
ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INT UNSIGNED DEFAULT 0 AFTER last_login_ip;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL AFTER failed_login_attempts;

-- 8. Tambahkan index composite untuk query kompleks di orders
CREATE INDEX idx_orders_table_status ON orders(table_id, status);
CREATE INDEX idx_orders_cashier_status ON orders(cashier_id, status);
CREATE INDEX idx_orders_date_range ON orders(created_at, status);
