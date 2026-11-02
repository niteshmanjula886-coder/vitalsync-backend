<?php

require_once __DIR__ . "/db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
if ($method !== "GET") {
    sendResponse("error", "Method not allowed. Use GET.", null, 405);
}

$userId = isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
if ($userId <= 0 && !empty($_GET["email"])) {
    $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $uStmt->execute([trim($_GET["email"])]);
    $uRow = $uStmt->fetch();
    if ($uRow) {
        $userId = (int)$uRow["id"];
    }
}

$macAddress = !empty($_GET["mac_address"]) ? strtoupper(trim((string)$_GET["mac_address"])) : "";

if ($userId <= 0) {
    sendResponse("error", "Valid user_id or email query parameter is required", null, 400);
}

try {
    // 1. Check user exists
    $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        sendResponse("error", "User not found with id: $userId", null, 404);
    }

    // 2. Fetch device
    if (!empty($macAddress)) {
        $stmt = $pdo->prepare("SELECT id, user_id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at, created_at, updated_at FROM user_devices WHERE user_id = ? AND mac_address = ? LIMIT 1");
        $stmt->execute([$userId, $macAddress]);
        $device = $stmt->fetch();
    } else {
        // Fetch latest connected or most recently synced device
        $stmt = $pdo->prepare("SELECT id, user_id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at, created_at, updated_at FROM user_devices WHERE user_id = ? ORDER BY is_connected DESC, last_synced_at DESC, id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $device = $stmt->fetch();
    }

    if (!$device) {
        sendResponse("success", "No paired device found for this user", null, 200);
    }

    $devId = (int)$device["id"];

    // 3. Fetch latest vitals recorded from this device
    $vitStmt = $pdo->prepare(
        "SELECT heart_rate, spo2, systolic_bp, diastolic_bp, logged_at 
         FROM vitals_logs 
         WHERE user_id = ? AND (device_id = ? OR device_id IS NULL) 
         ORDER BY logged_at DESC LIMIT 1"
    );
    $vitStmt->execute([$userId, $devId]);
    $latestVitals = $vitStmt->fetch();

    $latestReading = null;
    if ($latestVitals) {
        $latestReading = [
            "heart_rate" => $latestVitals["heart_rate"] !== null ? (int)$latestVitals["heart_rate"] : null,
            "spo2" => $latestVitals["spo2"] !== null ? (int)$latestVitals["spo2"] : null,
            "systolic_bp" => $latestVitals["systolic_bp"] !== null ? (int)$latestVitals["systolic_bp"] : null,
            "diastolic_bp" => $latestVitals["diastolic_bp"] !== null ? (int)$latestVitals["diastolic_bp"] : null,
            "logged_at" => $latestVitals["logged_at"]
        ];
    }

    sendResponse("success", "Device details retrieved successfully", [
        "id" => $devId,
        "device_id" => $devId,
        "user_id" => (int)$device["user_id"],
        "device_name" => $device["device_name"],
        "mac_address" => $device["mac_address"],
        "device_category" => $device["device_category"],
        "is_connected" => (int)$device["is_connected"],
        "battery_level" => $device["battery_level"] !== null ? (int)$device["battery_level"] : null,
        "last_synced_at" => $device["last_synced_at"],
        "created_at" => $device["created_at"],
        "latest_vitals" => $latestReading
    ], 200);

} catch (\PDOException $e) {
    sendResponse("error", "Database error retrieving device: " . $e->getMessage(), null, 500);
}
