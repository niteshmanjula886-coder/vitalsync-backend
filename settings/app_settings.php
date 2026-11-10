<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function resolveUser($conn, $input) {
    if (isset($input["user_id"]) && is_numeric($input["user_id"]) && (int)$input["user_id"] > 0) {
        return (int)$input["user_id"];
    }
    if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) && (int)$_GET["user_id"] > 0) {
        return (int)$_GET["user_id"];
    }
    $email = strtolower(trim($input["email"] ?? $_GET["email"] ?? ""));
    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row["id"];
        }
    }
    return 0;
}

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: Fetch User App Settings
// ==========================================
if ($method === "GET") {
    $userId = resolveUser($conn, $_GET);

    if ($userId <= 0) {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "user_id or email is required"
        ]);
    }

    $stmt = $conn->prepare(
        "SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval,
                COALESCE(ai_insights_enabled, ai_insights, 1) as ai_insights_enabled,
                COALESCE(user_analytics_enabled, usage_analytics, 1) as user_analytics_enabled,
                COALESCE(reminder_water_interval, 120) as reminder_water_interval,
                COALESCE(reminder_workout_enabled, 1) as reminder_workout_enabled,
                COALESCE(reminder_sleep_enabled, 1) as reminder_sleep_enabled,
                updated_at
         FROM user_app_settings
         WHERE user_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $settings = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // If no row exists, verify user exists in users before inserting default row
    if (!$settings) {
        $uChk = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $uChk->bind_param("i", $userId);
        $uChk->execute();
        $uExists = $uChk->get_result()->fetch_assoc();
        $uChk->close();

        if (!$uExists) {
            $conn->close();
            respond(404, [
                "success" => false,
                "status" => "error",
                "message" => "User not found"
            ]);
        }

        $initStmt = $conn->prepare("INSERT INTO user_app_settings (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id = user_id");
        $initStmt->bind_param("i", $userId);
        $initStmt->execute();
        $initStmt->close();

        $stmt2 = $conn->prepare(
            "SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval,
                    COALESCE(ai_insights_enabled, ai_insights, 1) as ai_insights_enabled,
                    COALESCE(user_analytics_enabled, usage_analytics, 1) as user_analytics_enabled,
                    COALESCE(reminder_water_interval, 120) as reminder_water_interval,
                    COALESCE(reminder_workout_enabled, 1) as reminder_workout_enabled,
                    COALESCE(reminder_sleep_enabled, 1) as reminder_sleep_enabled,
                    updated_at
             FROM user_app_settings WHERE user_id = ? LIMIT 1"
        );
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $settings = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    }

    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "App settings retrieved successfully",
        "settings" => [
            "user_id" => (int)$settings["user_id"],
            "push_notifications" => (bool)$settings["push_notifications"],
            "vital_alerts" => (bool)$settings["vital_alerts"],
            "daily_reminders" => (bool)$settings["daily_reminders"],
            "metric_units" => $settings["metric_units"] ?: "metric",
            "dark_mode" => (bool)$settings["dark_mode"],
            "data_sync_interval" => (int)($settings["data_sync_interval"] ?? 15),
            "ai_insights" => (bool)$settings["ai_insights_enabled"],
            "ai_insights_enabled" => (bool)$settings["ai_insights_enabled"],
            "usage_analytics" => (bool)$settings["user_analytics_enabled"],
            "user_analytics_enabled" => (bool)$settings["user_analytics_enabled"],
            "reminder_water_interval" => (int)$settings["reminder_water_interval"],
            "reminder_workout_enabled" => (bool)$settings["reminder_workout_enabled"],
            "reminder_sleep_enabled" => (bool)$settings["reminder_sleep_enabled"],
            "updated_at" => $settings["updated_at"]
        ]
    ]);
}

// ==========================================
// 2. POST / PUT: Update App Settings
// ==========================================
if ($method === "POST" || $method === "PUT") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = resolveUser($conn, $data);

    if ($userId <= 0) {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "user_id or email is required"
        ]);
    }

    // Ensure user exists
    $uStmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    if (!$uStmt->get_result()->fetch_assoc()) {
        $uStmt->close();
        respond(404, [
            "success" => false,
            "status" => "error",
            "message" => "User not found with id: $userId"
        ]);
    }
    $uStmt->close();

    // Parse incoming values
    $pushNotifs = isset($data["push_notifications"]) ? ($data["push_notifications"] ? 1 : 0) : null;
    $vitalAlerts = isset($data["vital_alerts"]) ? ($data["vital_alerts"] ? 1 : 0) : null;
    $dailyReminders = isset($data["daily_reminders"]) ? ($data["daily_reminders"] ? 1 : 0) : null;
    $metricUnits = isset($data["metric_units"]) ? (strtolower(trim($data["metric_units"])) === "imperial" ? "imperial" : "metric") : null;
    $darkMode = isset($data["dark_mode"]) ? ($data["dark_mode"] ? 1 : 0) : null;
    $syncInterval = isset($data["data_sync_interval"]) && is_numeric($data["data_sync_interval"]) ? max(1, min(1440, (int)$data["data_sync_interval"])) : null;
    
    $aiInsights = isset($data["ai_insights_enabled"]) ? ($data["ai_insights_enabled"] ? 1 : 0) : (isset($data["ai_insights"]) ? ($data["ai_insights"] ? 1 : 0) : null);
    $userAnalytics = isset($data["user_analytics_enabled"]) ? ($data["user_analytics_enabled"] ? 1 : 0) : (isset($data["usage_analytics"]) ? ($data["usage_analytics"] ? 1 : 0) : null);
    $waterInterval = isset($data["reminder_water_interval"]) && is_numeric($data["reminder_water_interval"]) ? (int)$data["reminder_water_interval"] : null;
    $workoutReminder = isset($data["reminder_workout_enabled"]) ? ($data["reminder_workout_enabled"] ? 1 : 0) : null;
    $sleepReminder = isset($data["reminder_sleep_enabled"]) ? ($data["reminder_sleep_enabled"] ? 1 : 0) : null;

    $sql = "INSERT INTO user_app_settings (
                user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval,
                ai_insights, ai_insights_enabled, usage_analytics, user_analytics_enabled,
                reminder_water_interval, reminder_workout_enabled, reminder_sleep_enabled
            ) VALUES (
                ?, COALESCE(?, 1), COALESCE(?, 1), COALESCE(?, 1), COALESCE(?, 'metric'), COALESCE(?, 0), COALESCE(?, 15),
                COALESCE(?, 1), COALESCE(?, 1), COALESCE(?, 1), COALESCE(?, 1),
                COALESCE(?, 120), COALESCE(?, 1), COALESCE(?, 1)
            )
            ON DUPLICATE KEY UPDATE
                push_notifications = COALESCE(?, push_notifications),
                vital_alerts = COALESCE(?, vital_alerts),
                daily_reminders = COALESCE(?, daily_reminders),
                metric_units = COALESCE(?, metric_units),
                dark_mode = COALESCE(?, dark_mode),
                data_sync_interval = COALESCE(?, data_sync_interval),
                ai_insights = COALESCE(?, ai_insights),
                ai_insights_enabled = COALESCE(?, ai_insights_enabled),
                usage_analytics = COALESCE(?, usage_analytics),
                user_analytics_enabled = COALESCE(?, user_analytics_enabled),
                reminder_water_interval = COALESCE(?, reminder_water_interval),
                reminder_workout_enabled = COALESCE(?, reminder_workout_enabled),
                reminder_sleep_enabled = COALESCE(?, reminder_sleep_enabled)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(500, [
            "success" => false,
            "status" => "error",
            "message" => "Database statement preparation failed: " . $conn->error
        ]);
    }

    $stmt->bind_param(
        "iiiisiiiiiiiiiiisiiiiiiii",
        $userId,
        $pushNotifs,
        $vitalAlerts,
        $dailyReminders,
        $metricUnits,
        $darkMode,
        $syncInterval,
        $aiInsights,
        $aiInsights,
        $userAnalytics,
        $userAnalytics,
        $waterInterval,
        $workoutReminder,
        $sleepReminder,
        $pushNotifs,
        $vitalAlerts,
        $dailyReminders,
        $metricUnits,
        $darkMode,
        $syncInterval,
        $aiInsights,
        $aiInsights,
        $userAnalytics,
        $userAnalytics,
        $waterInterval,
        $workoutReminder,
        $sleepReminder
    );

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(500, [
            "success" => false,
            "status" => "error",
            "message" => "Failed to update app settings"
        ]);
    }
    $stmt->close();

    // Fetch updated settings
    $fetchStmt = $conn->prepare(
        "SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval,
                COALESCE(ai_insights_enabled, ai_insights, 1) as ai_insights_enabled,
                COALESCE(user_analytics_enabled, usage_analytics, 1) as user_analytics_enabled,
                COALESCE(reminder_water_interval, 120) as reminder_water_interval,
                COALESCE(reminder_workout_enabled, 1) as reminder_workout_enabled,
                COALESCE(reminder_sleep_enabled, 1) as reminder_sleep_enabled,
                updated_at
         FROM user_app_settings WHERE user_id = ? LIMIT 1"
    );
    $fetchStmt->bind_param("i", $userId);
    $fetchStmt->execute();
    $updated = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "App settings updated successfully",
        "settings" => [
            "user_id" => (int)$updated["user_id"],
            "push_notifications" => (bool)$updated["push_notifications"],
            "vital_alerts" => (bool)$updated["vital_alerts"],
            "daily_reminders" => (bool)$updated["daily_reminders"],
            "metric_units" => $updated["metric_units"] ?: "metric",
            "dark_mode" => (bool)$updated["dark_mode"],
            "data_sync_interval" => (int)($updated["data_sync_interval"] ?? 15),
            "ai_insights" => (bool)$updated["ai_insights_enabled"],
            "ai_insights_enabled" => (bool)$updated["ai_insights_enabled"],
            "usage_analytics" => (bool)$updated["user_analytics_enabled"],
            "user_analytics_enabled" => (bool)$updated["user_analytics_enabled"],
            "reminder_water_interval" => (int)$updated["reminder_water_interval"],
            "reminder_workout_enabled" => (bool)$updated["reminder_workout_enabled"],
            "reminder_sleep_enabled" => (bool)$updated["reminder_sleep_enabled"],
            "updated_at" => $updated["updated_at"]
        ]
    ]);
}

respond(405, [
    "success" => false,
    "status" => "error",
    "message" => "Method not allowed. Use GET, POST, or PUT."
]);
