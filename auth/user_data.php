<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

$userId = isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
$email = strtolower(trim($_GET["email"] ?? ""));

if ($userId <= 0 && $email === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "user_id or email query parameter is required"
    ]);
    exit;
}

if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
}
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "User not found"
    ]);
    $conn->close();
    exit;
}

$userId = (int)$user["id"];

// 1. Profile
$pStmt = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
$pStmt->bind_param("i", $userId);
$pStmt->execute();
$profile = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$profile) {
    $initP = $conn->prepare("INSERT INTO user_profiles (user_id, full_name, age, gender, height_cm, weight_kg, phone, avatar_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
    $initP->bind_param("isisddss", $userId, $user["full_name"], $user["age"], $user["gender"], $user["height"], $user["weight"], $user["phone"], $user["profile_image"]);
    $initP->execute();
    $initP->close();

    $pStmt2 = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
    $pStmt2->bind_param("i", $userId);
    $pStmt2->execute();
    $profile = $pStmt2->get_result()->fetch_assoc();
    $pStmt2->close();
}

// 2. Health Goals
$gStmt = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
$gStmt->bind_param("i", $userId);
$gStmt->execute();
$goals = $gStmt->get_result()->fetch_assoc();
$gStmt->close();

if (!$goals) {
    $primary = !empty($user["primary_goal"]) ? $user["primary_goal"] : null;
    $initG = $conn->prepare("INSERT INTO user_health_goals (user_id, primary_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE primary_goal = VALUES(primary_goal)");
    $initG->bind_param("is", $userId, $primary);
    $initG->execute();
    $initG->close();

    $gStmt2 = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
    $gStmt2->bind_param("i", $userId);
    $gStmt2->execute();
    $goals = $gStmt2->get_result()->fetch_assoc();
    $gStmt2->close();
}

// 3. Settings
$sStmt = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
$sStmt->bind_param("i", $userId);
$sStmt->execute();
$settings = $sStmt->get_result()->fetch_assoc();
$sStmt->close();

if (!$settings) {
    $initS = $conn->prepare("INSERT INTO user_app_settings (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id = user_id");
    $initS->bind_param("i", $userId);
    $initS->execute();
    $initS->close();

    $sStmt2 = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
    $sStmt2->bind_param("i", $userId);
    $sStmt2->execute();
    $settings = $sStmt2->get_result()->fetch_assoc();
    $sStmt2->close();
}

$conn->close();

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$baseUrl = $scheme . "://" . $host . "/vitalsync_background/";

$rawAvatar = !empty($profile["avatar_url"]) ? $profile["avatar_url"] : $user["profile_image"];
$avatarUrl = !empty($rawAvatar) ? (preg_match('#^https?://#i', $rawAvatar) ? $rawAvatar : $baseUrl . ltrim($rawAvatar, "/")) : null;

http_response_code(200);
echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => "Full user data retrieved successfully",
    "user" => [
        "id" => (int)$user["id"],
        "name" => $profile["full_name"],
        "full_name" => $profile["full_name"],
        "email" => $user["email"],
        "phone" => $profile["phone"],
        "dob" => $user["dob"],
        "age" => (int)$profile["age"],
        "gender" => $profile["gender"],
        "height" => (float)$profile["height_cm"],
        "weight" => (float)$profile["weight_kg"],
        "activity_level" => $user["activity_level"],
        "primary_goal" => $goals["primary_goal"],
        "profile_image" => $user["profile_image"],
        "image_url" => $avatarUrl,
        "avatar_url" => $avatarUrl,
        "created_at" => $user["created_at"],
        "updated_at" => $profile["updated_at"]
    ],
    "profile" => [
        "user_id" => (int)$profile["user_id"],
        "full_name" => $profile["full_name"],
        "age" => (int)$profile["age"],
        "gender" => $profile["gender"],
        "height_cm" => (float)$profile["height_cm"],
        "weight_kg" => (float)$profile["weight_kg"],
        "blood_group" => $profile["blood_group"],
        "phone" => $profile["phone"],
        "avatar_url" => $avatarUrl,
        "updated_at" => $profile["updated_at"]
    ],
    "health_goals" => [
        "user_id" => (int)$goals["user_id"],
        "primary_goal" => $goals["primary_goal"],
        "target_steps" => (int)$goals["target_steps"],
        "target_sleep_hours" => (float)$goals["target_sleep_hours"],
        "target_water_ml" => (int)$goals["target_water_ml"],
        "target_weight_kg" => $goals["target_weight_kg"] !== null ? (float)$goals["target_weight_kg"] : null,
        "target_calories" => (int)$goals["target_calories"],
        "updated_at" => $goals["updated_at"]
    ],
    "app_settings" => [
        "user_id" => (int)$settings["user_id"],
        "push_notifications" => (bool)$settings["push_notifications"],
        "vital_alerts" => (bool)$settings["vital_alerts"],
        "daily_reminders" => (bool)$settings["daily_reminders"],
        "metric_units" => $settings["metric_units"] ?: "metric",
        "dark_mode" => (bool)$settings["dark_mode"],
        "data_sync_interval" => (int)($settings["data_sync_interval"] ?? 15),
        "updated_at" => $settings["updated_at"]
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

