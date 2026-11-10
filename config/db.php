<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "vitalsync_db";

try {
    $conn = new mysqli($host, $username, $password, $database);
} catch (\Throwable $e) {
    try {
        $database = "vitalsync";
        $conn = new mysqli($host, $username, $password, $database);
    } catch (\Throwable $e2) {
        error_log("VitalSync database connection failed: " . $e2->getMessage());

        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "message" => "Database connection error"
        ], JSON_UNESCAPED_SLASHES);
        exit();
    }
}

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false,
        "message" => "Database connection error"
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

$conn->set_charset("utf8mb4");

// Ensure Master Backend Architecture relational integrity & user-scoped schema
function initVitalSyncMasterSchema(mysqli $conn): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // 1. user_profiles
    $conn->query("CREATE TABLE IF NOT EXISTS `user_profiles` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. user_health_goals
    $conn->query("CREATE TABLE IF NOT EXISTS `user_health_goals` (
        `user_id` INT PRIMARY KEY,
        `primary_goal` VARCHAR(100) DEFAULT 'Improve Fitness',
        `target_steps` INT DEFAULT 10000,
        `target_sleep_hours` DECIMAL(3,1) DEFAULT 8.0,
        `target_water_ml` INT DEFAULT 2500,
        `target_calories` INT DEFAULT 2200,
        `target_weight_kg` DECIMAL(5,1) DEFAULT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. user_app_settings
    $conn->query("CREATE TABLE IF NOT EXISTS `user_app_settings` (
        `user_id` INT PRIMARY KEY,
        `push_notifications` TINYINT(1) DEFAULT 1,
        `vital_alerts` TINYINT(1) DEFAULT 1,
        `daily_reminders` TINYINT(1) DEFAULT 1,
        `metric_units` VARCHAR(10) DEFAULT 'metric',
        `dark_mode` TINYINT(1) DEFAULT 0,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 4. sleep_logs
    $conn->query("CREATE TABLE IF NOT EXISTS `sleep_logs` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 5. hydration_logs
    $conn->query("CREATE TABLE IF NOT EXISTS `hydration_logs` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `volume_ml` INT NOT NULL,
        `logged_at` DATETIME NOT NULL,
        `log_date` DATE NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_water` (`user_id`, `log_date`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 6. workout_sessions
    $conn->query("CREATE TABLE IF NOT EXISTS `workout_sessions` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 7. vitals_logs
    $conn->query("CREATE TABLE IF NOT EXISTS `vitals_logs` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `heart_rate` INT DEFAULT NULL,
        `spo2` INT DEFAULT NULL,
        `logged_at` DATETIME NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_vitals` (`user_id`, `logged_at`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Column compatibility for pre-existing tables
    $chkAvatar = $conn->query("SHOW COLUMNS FROM `user_profiles` LIKE 'avatar_url'");
    if ($chkAvatar && $chkAvatar->num_rows === 0) {
        @$conn->query("ALTER TABLE `user_profiles` ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `phone`");
    }

    $chkTw = $conn->query("SHOW COLUMNS FROM `user_health_goals` LIKE 'target_weight_kg'");
    if ($chkTw && $chkTw->num_rows === 0) {
        @$conn->query("ALTER TABLE `user_health_goals` ADD COLUMN `target_weight_kg` DECIMAL(5,1) DEFAULT NULL AFTER `target_calories`");
    }

    $chkSleep = $conn->query("SHOW COLUMNS FROM `sleep_logs` LIKE 'start_time'");
    if ($chkSleep && $chkSleep->num_rows === 0) {
        @$conn->query("ALTER TABLE `sleep_logs` ADD COLUMN `start_time` DATETIME NULL AFTER `user_id`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `quality_score` INT DEFAULT NULL AFTER `duration_minutes`, ADD COLUMN `logged_at` DATE NULL AFTER `quality_score`");
        @$conn->query("ALTER TABLE `sleep_logs` ADD INDEX `idx_user_sleep` (`user_id`, `logged_at`)");
    }

    $chkHyd = $conn->query("SHOW COLUMNS FROM `hydration_logs` LIKE 'volume_ml'");
    if ($chkHyd && $chkHyd->num_rows === 0) {
        @$conn->query("ALTER TABLE `hydration_logs` ADD COLUMN `volume_ml` INT NULL AFTER `user_id`, ADD COLUMN `log_date` DATE NULL AFTER `logged_at`");
        @$conn->query("ALTER TABLE `hydration_logs` ADD INDEX `idx_user_water` (`user_id`, `log_date`)");
    }

    $chkWk = $conn->query("SHOW COLUMNS FROM `workout_sessions` LIKE 'session_uuid'");
    if ($chkWk && $chkWk->num_rows === 0) {
        @$conn->query("ALTER TABLE `workout_sessions` ADD COLUMN `session_uuid` VARCHAR(64) NULL AFTER `user_id`, ADD COLUMN `activity_type` VARCHAR(50) NULL AFTER `session_uuid`, ADD COLUMN `start_time` DATETIME NULL AFTER `activity_type`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `duration_seconds` INT NULL DEFAULT 0 AFTER `end_time`, ADD COLUMN `distance_meters` DECIMAL(8,2) DEFAULT 0.00 AFTER `duration_seconds`, ADD COLUMN `steps` INT DEFAULT 0 AFTER `distance_meters`, ADD COLUMN `calories` INT DEFAULT 0 AFTER `steps`, ADD COLUMN `avg_heart_rate` INT DEFAULT NULL AFTER `calories`");
        @$conn->query("ALTER TABLE `workout_sessions` ADD INDEX `idx_user_workout` (`user_id`, `start_time`)");
    }

    // 8. ai_chat_sessions & session_id linkage on ai_chat_messages
    @$conn->query("CREATE TABLE IF NOT EXISTS `ai_chat_sessions` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `title` VARCHAR(120) NOT NULL DEFAULT 'New Chat',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_ai_sessions_user` (`user_id`, `updated_at`),
        CONSTRAINT `fk_ai_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $chkChatSess = $conn->query("SHOW COLUMNS FROM `ai_chat_messages` LIKE 'session_id'");
    if ($chkChatSess && $chkChatSess->num_rows === 0) {
        @$conn->query("ALTER TABLE `ai_chat_messages` ADD COLUMN `session_id` BIGINT UNSIGNED DEFAULT NULL AFTER `user_id`, ADD INDEX `idx_ai_chat_session` (`user_id`, `session_id`, `created_at`)");
    }
    @$conn->query("ALTER TABLE `ai_chat_messages` MODIFY COLUMN `message` TEXT NULL");
}

try {
    initVitalSyncMasterSchema($conn);
} catch (\Throwable $e) {
    error_log("Schema init warning: " . $e->getMessage());
}