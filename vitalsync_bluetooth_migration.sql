USE vitalsync;

-- 1. Create user_devices table
CREATE TABLE IF NOT EXISTS user_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    mac_address VARCHAR(100) NOT NULL,
    device_category VARCHAR(100) NOT NULL DEFAULT 'SMARTWATCH',
    is_connected TINYINT(1) NOT NULL DEFAULT 1,
    battery_level INT NULL DEFAULT NULL,
    last_synced_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_device (user_id, mac_address),
    INDEX idx_user_devices_conn (user_id, is_connected),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add device_id to vitals_logs if missing
SET @col_dev := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'vitals_logs' AND COLUMN_NAME = 'device_id');
SET @sql_dev := IF(@col_dev = 0, 'ALTER TABLE vitals_logs ADD COLUMN device_id INT UNSIGNED NULL AFTER user_id, ADD CONSTRAINT fk_vitals_logs_device FOREIGN KEY (device_id) REFERENCES user_devices(id) ON DELETE SET NULL;', 'SELECT 1;');
PREPARE stmt FROM @sql_dev;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add source to vitals_logs if missing
SET @col_src := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'vitals_logs' AND COLUMN_NAME = 'source');
SET @sql_src := IF(@col_src = 0, "ALTER TABLE vitals_logs ADD COLUMN source VARCHAR(100) NOT NULL DEFAULT 'bluetooth' AFTER diastolic_bp;", 'SELECT 1;');
PREPARE stmt FROM @sql_src;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

