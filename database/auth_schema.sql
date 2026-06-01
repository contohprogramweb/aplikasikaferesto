-- ============================================
-- Database Schema untuk Authentication Module
-- Smart Restaurant POS - SRS v4.0
-- ============================================

-- Tabel Users (untuk menyimpan data user admin/staff)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    last_login DATETIME NULL,
    last_activity DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Login Attempts (untuk tracking failed login dan rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    user_agent TEXT NULL,
    
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Activity Logs (untuk audit trail dan concurrent login logging)
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel CI Sessions (untuk database session driver)
CREATE TABLE IF NOT EXISTS ci_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    timestamp INT UNSIGNED DEFAULT 0 NOT NULL,
    data LONGTEXT NOT NULL,
    
    KEY timestamp (timestamp),
    KEY ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Order Counters (digunakan oleh helper generate_order_number)
CREATE TABLE IF NOT EXISTS order_counters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    counter_type VARCHAR(50) NOT NULL,
    counter_date DATE NOT NULL,
    counter_value INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_counter (counter_type, counter_date),
    INDEX idx_counter_date (counter_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Settings (untuk konfigurasi aplikasi)
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type VARCHAR(20) DEFAULT 'string',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data untuk Testing
-- ============================================

-- Insert default admin user
-- Password: admin123 (hashed dengan BCRYPT cost 10)
INSERT INTO users (username, email, password, full_name, role, is_active) 
VALUES (
    'admin',
    'admin@smartrestopos.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrator',
    'admin',
    1
);

-- Insert sample staff user
-- Password: staff123
INSERT INTO users (username, email, password, full_name, role, is_active) 
VALUES (
    'staff01',
    'staff@smartrestopos.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Staff User',
    'staff',
    1
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('restaurant_name', 'Smart Restaurant POS', 'string', 'Nama restoran'),
('currency_symbol', 'Rp', 'string', 'Simbol mata uang'),
('tax_rate', '11', 'number', 'Persentase pajak'),
('service_charge_rate', '10', 'number', 'Persentase service charge'),
('timezone', 'Asia/Jakarta', 'string', 'Timezone aplikasi');

-- ============================================
-- Catatan Penting:
-- ============================================
-- 1. Password hash di atas adalah contoh saja. 
--    Untuk production, generate hash baru menggunakan:
--    password_hash('password_anda', PASSWORD_BCRYPT, ['cost' => 10])
--
-- 2. Session TTL diatur di config.php:
--    $config['sess_expiration'] = 7200; // 2 jam (default)
--    Di Auth_model, kita override menjadi 8 jam (28800 detik)
--
-- 3. Remember me cookie duration: 7 hari (604800 detik)
--
-- 4. Login attempt lockout: 5x gagal = blokir 15 menit (900 detik)
-- ============================================
