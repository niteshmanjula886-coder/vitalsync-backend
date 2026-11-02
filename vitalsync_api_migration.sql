-- VitalSync API Migration: Tables for Workouts, Hydration, Sleep, Steps, and Vitals Logs

USE vitalsync;

-- 1. Ensure `name` exists on users table
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'name');
SET @sql_users := IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN name VARCHAR(100) NULL AFTER full_name;', 'SELECT 1;');
PREPARE stmt FROM @sql_users;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE users SET name = full_name WHERE name IS NULL OR name = '';

-- 2. Workouts table
CREATE TABLE IF NOT EXISTS workouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    workout_type VARCHAR(100) NOT NULL DEFAULT 'General Workout',
    status VARCHAR(50) NOT NULL DEFAULT 'COMPLETED',
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    calories_burned INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_workout (user_id, started_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Update hydration_logs to include intake_ml, target_ml, logged_date
SET @col_intake := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'vitalsync' AND TABLE_NAME = 'hydration_logs' AND COLUMN_NAME = 'intake_ml');
SET @sql_hyd := IF(@col_intake = 0, 'ALTER TABLE hydration_logs ADD COLUMN intake_ml INT UNSIGNED NULL AFTER user_id, ADD COLUMN target_ml INT UNSIGNED NOT NULL DEFAULT 2000 AFTER intake_ml, ADD COLUMN logged_date DATE NULL AFTER target_ml;', 'SELECT 1;');
PREPARE stmt FROM @sql_hyd;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE hydration_logs SET intake_ml = amount_ml WHERE intake_ml IS NULL;
UPDATE hydration_logs SET logged_date = date_string WHERE logged_date IS NULL;

-- 4. Sleep logs table
CREATE TABLE IF NOT EXISTS sleep_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bedtime VARCHAR(50) NOT NULL DEFAULT '23:00',
    wake_time VARCHAR(50) NOT NULL DEFAULT '07:00',
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 480,
    status_color VARCHAR(20) NOT NULL DEFAULT 'optimal',
    logged_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_sleep_date (user_id, logged_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Steps logs table
CREATE TABLE IF NOT EXISTS steps_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    step_count INT UNSIGNED NOT NULL DEFAULT 0,
    target_steps INT UNSIGNED NOT NULL DEFAULT 8000,
    distance_km DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    calories_kcal INT UNSIGNED NOT NULL DEFAULT 0,
    active_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    logged_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_step_date (user_id, logged_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Vitals logs table
CREATE TABLE IF NOT EXISTS vitals_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    heart_rate INT UNSIGNED NULL,
    spo2 INT UNSIGNED NULL,
    systolic_bp INT UNSIGNED NULL,
    diastolic_bp INT UNSIGNED NULL,
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_vitals_date (user_id, logged_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

