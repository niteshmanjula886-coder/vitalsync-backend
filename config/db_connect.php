<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db   = "vitalsync";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    ensureVitalSyncCloudSyncSchema($pdo);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage(),
        "data" => null
    ]);
    exit;
}

function ensureVitalSyncCloudSyncSchema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // 1. users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(191) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(120) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. user_profiles
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_profiles` (
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

    // 3. user_health_goals
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_health_goals` (
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

    // 4. user_app_settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_app_settings` (
        `user_id` INT PRIMARY KEY,
        `push_notifications` TINYINT(1) DEFAULT 1,
        `vital_alerts` TINYINT(1) DEFAULT 1,
        `daily_reminders` TINYINT(1) DEFAULT 1,
        `metric_units` VARCHAR(10) DEFAULT 'metric',
        `dark_mode` TINYINT(1) DEFAULT 0,
        `ai_insights_enabled` TINYINT(1) DEFAULT 1,
        `user_analytics_enabled` TINYINT(1) DEFAULT 1,
        `reminder_water_interval` INT DEFAULT 120,
        `reminder_workout_enabled` TINYINT(1) DEFAULT 1,
        `reminder_sleep_enabled` TINYINT(1) DEFAULT 1,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 4b. weekly_health_reports
    $pdo->exec("CREATE TABLE IF NOT EXISTS `weekly_health_reports` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `week_start_date` DATE NOT NULL,
        `week_end_date` DATE NOT NULL,
        `wellness_score` INT NOT NULL,
        `performance_category` ENUM('normal', 'mid', 'low') NOT NULL,
        `heart_rate_score` INT NOT NULL,
        `sleep_score` INT NOT NULL,
        `hydration_score` INT NOT NULL,
        `activity_score` INT NOT NULL,
        `summary_text` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_user_week` (`user_id`, `week_start_date`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 5. sleep_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sleep_logs` (
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

    // 6. hydration_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `hydration_logs` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `volume_ml` INT NOT NULL,
        `logged_at` DATETIME NOT NULL,
        `log_date` DATE NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_water` (`user_id`, `log_date`),
        INDEX `idx_user_hydration_date` (`user_id`, `log_date`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 7. workout_sessions
    $pdo->exec("CREATE TABLE IF NOT EXISTS `workout_sessions` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `session_uuid` VARCHAR(64) NOT NULL UNIQUE,
        `activity_type` ENUM('Walking', 'Cycling', 'Running') NOT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `duration_seconds` INT NOT NULL,
        `target_type` ENUM('time', 'distance') DEFAULT 'time',
        `target_value` INT DEFAULT 45,
        `steps` INT DEFAULT 0,
        `distance_km` DECIMAL(6,2) DEFAULT 0.00,
        `avg_pace` VARCHAR(20) DEFAULT '00:00 /km',
        `calories` INT DEFAULT 0,
        `tracking_source` VARCHAR(30) DEFAULT 'phone_sensor',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_workout_time` (`user_id`, `start_time`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 8. vitals_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vitals_logs` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `heart_rate` INT DEFAULT NULL,
        `spo2` INT DEFAULT NULL,
        `systolic_bp` INT DEFAULT NULL,
        `diastolic_bp` INT DEFAULT NULL,
        `source` VARCHAR(20) DEFAULT 'bluetooth',
        `logged_at` DATETIME NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_vitals` (`user_id`, `logged_at`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 9. user_devices
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_devices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `device_name` VARCHAR(100) NOT NULL,
        `mac_address` VARCHAR(50) NOT NULL,
        `device_category` VARCHAR(50) DEFAULT 'smartwatch',
        `is_connected` TINYINT(1) DEFAULT 0,
        `battery_level` INT DEFAULT NULL,
        `last_synced_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_user_mac` (`user_id`, `mac_address`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Column compatibility
    try {
        $chkAvatar = $pdo->query("SHOW COLUMNS FROM `user_profiles` LIKE 'avatar_url'")->fetch();
        if (!$chkAvatar) {
            $pdo->exec("ALTER TABLE `user_profiles` ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `phone`");
        }
    } catch (\Exception $e) {}

    try {
        $chkTw = $pdo->query("SHOW COLUMNS FROM `user_health_goals` LIKE 'target_weight_kg'")->fetch();
        if (!$chkTw) {
            $pdo->exec("ALTER TABLE `user_health_goals` ADD COLUMN `target_weight_kg` DECIMAL(5,1) DEFAULT NULL AFTER `target_calories`");
        }
    } catch (\Exception $e) {}

    try {
        $chkSleep = $pdo->query("SHOW COLUMNS FROM `sleep_logs` LIKE 'start_time'")->fetch();
        if (!$chkSleep) {
            $pdo->exec("ALTER TABLE `sleep_logs` ADD COLUMN `start_time` DATETIME NULL AFTER `user_id`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `quality_score` INT DEFAULT NULL AFTER `duration_minutes`, ADD COLUMN `logged_at` DATE NULL AFTER `quality_score`");
            $pdo->exec("ALTER TABLE `sleep_logs` ADD INDEX `idx_user_sleep` (`user_id`, `logged_at`)");
        }
    } catch (\Exception $e) {}

    try {
        $chkHyd = $pdo->query("SHOW COLUMNS FROM `hydration_logs` LIKE 'volume_ml'")->fetch();
        if (!$chkHyd) {
            $pdo->exec("ALTER TABLE `hydration_logs` ADD COLUMN `volume_ml` INT NULL AFTER `user_id`, ADD COLUMN `log_date` DATE NULL AFTER `logged_at`");
            $pdo->exec("ALTER TABLE `hydration_logs` ADD INDEX `idx_user_water` (`user_id`, `log_date`)");
        }
    } catch (\Exception $e) {}

    try {
        $chkWk = $pdo->query("SHOW COLUMNS FROM `workout_sessions` LIKE 'session_uuid'")->fetch();
        if (!$chkWk) {
            $pdo->exec("ALTER TABLE `workout_sessions` ADD COLUMN `session_uuid` VARCHAR(64) NULL AFTER `user_id`, ADD COLUMN `activity_type` VARCHAR(50) NULL AFTER `session_uuid`, ADD COLUMN `start_time` DATETIME NULL AFTER `activity_type`, ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`, ADD COLUMN `duration_seconds` INT NULL DEFAULT 0 AFTER `end_time`, ADD COLUMN `distance_meters` DECIMAL(8,2) DEFAULT 0.00 AFTER `duration_seconds`, ADD COLUMN `steps` INT DEFAULT 0 AFTER `distance_meters`, ADD COLUMN `calories` INT DEFAULT 0 AFTER `steps`, ADD COLUMN `avg_heart_rate` INT DEFAULT NULL AFTER `calories`");
            $pdo->exec("ALTER TABLE `workout_sessions` ADD INDEX `idx_user_workout` (`user_id`, `start_time`)");
        }
    } catch (\Exception $e) {}
}

function sendResponse($status, $message, $data = null, $httpCode = 200, $extra = []) {
    http_response_code($httpCode);
    header("Content-Type: application/json; charset=UTF-8");
    $response = [
        "status" => $status,
        "success" => ($status === "success"),
        "message" => $message,
        "data" => $data
    ];
    if (!empty($extra) && is_array($extra)) {
        foreach ($extra as $key => $val) {
            $response[$key] = $val;
        }
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents("php://input");
    if ($raw !== false && $raw !== null) {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $raw = trim($raw);
    }
    $data = !empty($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = $_POST;
    }
    return is_array($data) ? $data : [];
}

/**
 * Resolve user_id from either integer user_id or email string
 */
function resolveUserId($pdo, $input) {
    if (isset($input["user_id"]) && is_numeric($input["user_id"]) && (int)$input["user_id"] > 0) {
        return (int)$input["user_id"];
    }
    if (!empty($input["email"])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([trim($input["email"])]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row["id"];
        }
    }
    return 0;
}
