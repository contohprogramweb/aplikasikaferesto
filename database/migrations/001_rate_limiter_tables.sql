-- Rate Limiter Database Tables
-- Berdasarkan SRS v4.0 Bab 3.4.7 dan NFR-SEC-16

-- Tabel untuk menyimpan rate limit data (blocking information)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(128) NOT NULL COMMENT 'IP address atau session_id dengan prefix (ip:xxx atau session:xxx)',
    `endpoint_group` VARCHAR(50) NOT NULL COMMENT 'table_check, session, polling, login, admin',
    `request_count` INT UNSIGNED DEFAULT 0 COMMENT 'Jumlah request dalam window saat ini',
    `blocked_until` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Timestamp ketika block berakhir (0 = tidak diblokir)',
    `created_at` INT UNSIGNED NOT NULL,
    `updated_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_identifier_endpoint` (`identifier`, `endpoint_group`),
    KEY `idx_blocked_until` (`blocked_until`),
    KEY `idx_endpoint_group` (`endpoint_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan request timestamps (sliding window implementation)
CREATE TABLE IF NOT EXISTS `rate_limit_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(128) NOT NULL COMMENT 'IP address atau session_id dengan prefix',
    `endpoint_group` VARCHAR(50) NOT NULL COMMENT 'table_check, session, polling, login, admin',
    `request_time` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp request',
    PRIMARY KEY (`id`),
    KEY `idx_identifier_endpoint` (`identifier`, `endpoint_group`),
    KEY `idx_request_time` (`request_time`),
    KEY `idx_endpoint_time` (`endpoint_group`, `request_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk tracking login attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IPv4 atau IPv6',
    `username` VARCHAR(255) DEFAULT NULL COMMENT 'Username yang dicoba',
    `attempted_at` DATETIME NOT NULL COMMENT 'Waktu percobaan login',
    `success` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = berhasil, 0 = gagal',
    PRIMARY KEY (`id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_attempted_at` (`attempted_at`),
    KEY `idx_ip_success` (`ip_address`, `success`),
    KEY `idx_failed_attempts` (`ip_address`, `success`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel activity logs untuk audit trail
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_id` VARCHAR(255) DEFAULT NULL COMMENT 'User ID atau username',
    `action` VARCHAR(100) NOT NULL COMMENT 'Action yang dilakukan (login_failed, login_success, rate_limit_exceeded, dll)',
    `category` VARCHAR(50) DEFAULT NULL COMMENT 'Kategori (login, rate_limit, order, dll)',
    `timestamp` DATETIME NOT NULL,
    `details` TEXT DEFAULT NULL COMMENT 'JSON encoded details',
    PRIMARY KEY (`id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index tambahan untuk performa query rate limiting
ALTER TABLE `rate_limit_requests` 
ADD INDEX `idx_cleanup` (`request_time`);

ALTER TABLE `login_attempts` 
ADD INDEX `idx_failed_recent` (`success`, `attempted_at`);

-- Trigger untuk auto-cleanup rate_limit_requests yang sudah expired (optional)
-- Ini bisa dijalankan via scheduled job/cron juga
DELIMITER $$

CREATE EVENT IF NOT EXISTS `cleanup_rate_limits`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    -- Hapus request timestamps yang lebih tua dari 15 menit (window terbesar)
    DELETE FROM `rate_limit_requests` WHERE `request_time` < UNIX_TIMESTAMP() - 900;
    
    -- Reset blocked status yang sudah expired
    UPDATE `rate_limits` 
    SET `blocked_until` = 0, `request_count` = 0 
    WHERE `blocked_until` > 0 AND `blocked_until` < UNIX_TIMESTAMP();
END$$

DELIMITER ;

-- Data sample untuk testing (optional)
-- INSERT INTO `rate_limits` (`identifier`, `endpoint_group`, `request_count`, `blocked_until`, `created_at`, `updated_at`) 
-- VALUES ('ip:192.168.1.100', 'login', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
