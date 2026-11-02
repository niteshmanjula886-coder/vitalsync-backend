-- =========================================================================
-- VitalSync Master Backend Architecture: User-Scoped Relational Integrity
-- =========================================================================

USE vitalsync;

-- 0. Core users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Extend or ensure user_profiles table exists with CASCADE deletion
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `user_id` INT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `age` INT DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT NULL,
    `height_cm` DECIMAL(5,1) DEFAULT NULL,
    `weight_kg` DECIMAL(5,1) DEFAULT NULL,
    `blood_group` VARCHAR(10) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar_url` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. User Health Goals table
CREATE TABLE IF NOT EXISTS `user_health_goals` (
    `user_id` INT PRIMARY KEY,
    `primary_goal` VARCHAR(100) DEFAULT 'Improve Fitness',
    `target_steps` INT DEFAULT 10000,
    `target_sleep_hours` DECIMAL(3,1) DEFAULT 8.0,
    `target_water_ml` INT DEFAULT 2500,
    `target_calories` INT DEFAULT 2200,
    `target_weight_kg` DECIMAL(5,1) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. User App Settings table
CREATE TABLE IF NOT EXISTS `user_app_settings` (
    `user_id` INT PRIMARY KEY,
    `push_notifications` TINYINT(1) DEFAULT 1,
    `vital_alerts` TINYINT(1) DEFAULT 1,
    `daily_reminders` TINYINT(1) DEFAULT 1,
    `metric_units` VARCHAR(10) DEFAULT 'metric',
    `dark_mode` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Sleep Logs table
CREATE TABLE IF NOT EXISTS `sleep_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `duration_minutes` INT NOT NULL,
    `quality_score` INT DEFAULT NULL,
    `logged_at` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_sleep` (`user_id`, `logged_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure compatibility columns exist on sleep_logs
SET @col_st := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'sleep_logs' AND COLUMN_NAME = 'start_time');
SET @sql_st := IF(@col_st = 0, 'ALTER TABLE `sleep_logs` ADD COLUMN `start_time` DATETIME NULL AFTER `user_id`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `quality_score` INT DEFAULT NULL AFTER `duration_minutes`, ADD COLUMN `logged_at` DATE NULL AFTER `quality_score`;', 'SELECT 1;');
PREPARE stmt_st FROM @sql_st;
EXECUTE stmt_st;
DEALLOCATE PREPARE stmt_st;

-- 5. Hydration Logs table
CREATE TABLE IF NOT EXISTS `hydration_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `volume_ml` INT NOT NULL,
    `logged_at` DATETIME NOT NULL,
    `log_date` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_water` (`user_id`, `log_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure compatibility columns exist on hydration_logs
SET @col_vol := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'hydration_logs' AND COLUMN_NAME = 'volume_ml');
SET @sql_vol := IF(@col_vol = 0, 'ALTER TABLE `hydration_logs` ADD COLUMN `volume_ml` INT NULL AFTER `user_id`, ADD COLUMN `log_date` DATE NULL AFTER `logged_at`;', 'SELECT 1;');
PREPARE stmt_vol FROM @sql_vol;
EXECUTE stmt_vol;
DEALLOCATE PREPARE stmt_vol;

-- 6. Workout Sessions table (Outdoor & Activity tracking)
CREATE TABLE IF NOT EXISTS `workout_sessions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `session_uuid` VARCHAR(64) NOT NULL UNIQUE,
    `activity_type` ENUM('Walking', 'Cycling', 'Running') NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `duration_seconds` INT NOT NULL,
    `distance_meters` DECIMAL(8,2) DEFAULT 0.00,
    `steps` INT DEFAULT 0,
    `calories` INT DEFAULT 0,
    `avg_heart_rate` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_workout` (`user_id`, `start_time`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure compatibility columns exist on workout_sessions if table was pre-existing
SET @col_wuuid := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'workout_sessions' AND COLUMN_NAME = 'session_uuid');
SET @sql_wuuid := IF(@col_wuuid = 0, 'ALTER TABLE `workout_sessions` ADD COLUMN `session_uuid` VARCHAR(64) NULL AFTER `user_id`, ADD COLUMN `activity_type` VARCHAR(50) NULL AFTER `session_uuid`, ADD COLUMN `start_time` DATETIME NULL AFTER `activity_type`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `duration_seconds` INT NULL DEFAULT 0 AFTER `end_time`, ADD COLUMN `distance_meters` DECIMAL(8,2) DEFAULT 0.00 AFTER `duration_seconds`, ADD COLUMN `steps` INT DEFAULT 0 AFTER `distance_meters`, ADD COLUMN `calories` INT DEFAULT 0 AFTER `steps`, ADD COLUMN `avg_heart_rate` INT DEFAULT NULL AFTER `calories`;', 'SELECT 1;');
PREPARE stmt_wuuid FROM @sql_wuuid;
EXECUTE stmt_wuuid;
DEALLOCATE PREPARE stmt_wuuid;

-- 7. Vitals Logs table
CREATE TABLE IF NOT EXISTS `vitals_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `heart_rate` INT DEFAULT NULL,
    `spo2` INT DEFAULT NULL,
    `logged_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_vitals` (`user_id`, `logged_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

