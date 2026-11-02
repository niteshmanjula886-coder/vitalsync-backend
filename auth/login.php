<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "POST method required"
    ]);
    exit;
}

// Support JSON input and form data
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Invalid or empty request data"
    ]);
    exit;
}

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$deviceName = trim($data["device_name"] ?? $_SERVER["HTTP_USER_AGENT"] ?? "Mobile Device");
if (strlen($deviceName) > 100) {
    $deviceName = substr($deviceName, 0, 100);
}

// Validate input
if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 255 ||
    !is_string($password) ||
    $password === "" ||
    strlen($password) > 72
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Enter a valid email and password"
    ]);
    exit;
}

// Find user account
$stmt = $conn->prepare(
    "SELECT id, full_name, email, password_hash, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Database statement preparation failed"
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    $conn->close();
    exit;
}

$userId = (int)$user["id"];

// Record login session
$ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
$sessionStmt = $conn->prepare(
    "INSERT INTO login_sessions (user_id, device_name, ip_address, is_current)
     VALUES (?, ?, ?, 1)"
);
if ($sessionStmt) {
    $sessionStmt->bind_param("iss", $userId, $deviceName, $ip);
    $sessionStmt->execute();
    $sessionStmt->close();
}

// 1. Fetch or initialize user_profiles
$pStmt = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
$pStmt->bind_param("i", $userId);
$pStmt->execute();
$userProfile = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$userProfile) {
    $initP = $conn->prepare("INSERT INTO user_profiles (user_id, full_name, age, gender, height_cm, weight_kg, phone, avatar_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
    $initP->bind_param("isisddss", $userId, $user["full_name"], $user["age"], $user["gender"], $user["height"], $user["weight"], $user["phone"], $user["profile_image"]);
    $initP->execute();
    $initP->close();

    $pStmt2 = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
    $pStmt2->bind_param("i", $userId);
    $pStmt2->execute();
    $userProfile = $pStmt2->get_result()->fetch_assoc();
    $pStmt2->close();
}

// 2. Fetch or initialize user_health_goals
$gStmt = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
$gStmt->bind_param("i", $userId);
$gStmt->execute();
$userGoals = $gStmt->get_result()->fetch_assoc();
$gStmt->close();

if (!$userGoals) {
    $pGoal = !empty($user["primary_goal"]) ? $user["primary_goal"] : null;
    $initG = $conn->prepare("INSERT INTO user_health_goals (user_id, primary_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE primary_goal = VALUES(primary_goal)");
    $initG->bind_param("is", $userId, $pGoal);
    $initG->execute();
    $initG->close();

    $gStmt2 = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
    $gStmt2->bind_param("i", $userId);
    $gStmt2->execute();
    $userGoals = $gStmt2->get_result()->fetch_assoc();
    $gStmt2->close();
}

// 3. Fetch or initialize user_app_settings
$sStmt = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
$sStmt->bind_param("i", $userId);
$sStmt->execute();
$userSettings = $sStmt->get_result()->fetch_assoc();
$sStmt->close();

if (!$userSettings) {
    $initS = $conn->prepare("INSERT INTO user_app_settings (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id = user_id");
    $initS->bind_param("i", $userId);
    $initS->execute();
    $initS->close();

    $sStmt2 = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
    $sStmt2->bind_param("i", $userId);
    $sStmt2->execute();
    $userSettings = $sStmt2->get_result()->fetch_assoc();
    $sStmt2->close();
}

$conn->close();

// Build profile image / avatar URL
$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$baseUrl = $scheme . "://" . $host . "/vitalsync_background/";

$rawAvatar = !empty($userProfile["avatar_url"]) ? $userProfile["avatar_url"] : $user["profile_image"];
$avatarUrl = !empty($rawAvatar) ? (preg_match('#^https?://#i', $rawAvatar) ? $rawAvatar : $baseUrl . ltrim($rawAvatar, "/")) : null;

// Return full bundle
http_response_code(200);
echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => "Login successful",
    "user" => [
        "id" => (int)$user["id"],
        "name" => $userProfile["full_name"] ?: $user["full_name"],
        "full_name" => $userProfile["full_name"] ?: $user["full_name"],
        "email" => $user["email"],
        "phone" => $userProfile["phone"] ?: $user["phone"],
        "dob" => $user["dob"],
        "age" => $userProfile["age"] !== null ? (int)$userProfile["age"] : ($user["age"] !== null ? (int)$user["age"] : null),
        "gender" => $userProfile["gender"] ?: ($user["gender"] ?: "Not Specified"),
        "height" => $userProfile["height_cm"] !== null ? (float)$userProfile["height_cm"] : ($user["height"] !== null ? (float)$user["height"] : null),
        "weight" => $userProfile["weight_kg"] !== null ? (float)$userProfile["weight_kg"] : ($user["weight"] !== null ? (float)$user["weight"] : null),
        "activity_level" => $user["activity_level"],
        "primary_goal" => $userGoals["primary_goal"] ?: ($user["primary_goal"] ?? "Improve Fitness"),
        "profile_image" => $user["profile_image"],
        "image_url" => $avatarUrl,
        "avatar_url" => $avatarUrl,
        "created_at" => $user["created_at"],
        "updated_at" => $userProfile["updated_at"] ?? $user["updated_at"]
    ],
    "profile" => [
        "user_id" => (int)$userProfile["user_id"],
        "full_name" => $userProfile["full_name"],
        "age" => (int)$userProfile["age"],
        "gender" => $userProfile["gender"],
        "height_cm" => (float)$userProfile["height_cm"],
        "weight_kg" => (float)$userProfile["weight_kg"],
        "blood_group" => $userProfile["blood_group"],
        "phone" => $userProfile["phone"],
        "avatar_url" => $avatarUrl,
        "updated_at" => $userProfile["updated_at"]
    ],
    "health_goals" => [
        "user_id" => (int)$userGoals["user_id"],
        "primary_goal" => $userGoals["primary_goal"],
        "target_steps" => (int)$userGoals["target_steps"],
        "target_sleep_hours" => (float)$userGoals["target_sleep_hours"],
        "target_water_ml" => (int)$userGoals["target_water_ml"],
        "target_weight_kg" => $userGoals["target_weight_kg"] !== null ? (float)$userGoals["target_weight_kg"] : null,
        "target_calories" => (int)$userGoals["target_calories"],
        "updated_at" => $userGoals["updated_at"]
    ],
    "app_settings" => [
        "user_id" => (int)$userSettings["user_id"],
        "push_notifications" => (bool)$userSettings["push_notifications"],
        "vital_alerts" => (bool)$userSettings["vital_alerts"],
        "daily_reminders" => (bool)$userSettings["daily_reminders"],
        "metric_units" => $userSettings["metric_units"] ?: "metric",
        "dark_mode" => (bool)$userSettings["dark_mode"],
        "data_sync_interval" => (int)($userSettings["data_sync_interval"] ?? 15),
        "updated_at" => $userSettings["updated_at"]
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
