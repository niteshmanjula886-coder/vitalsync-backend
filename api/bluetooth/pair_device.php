<?php

require_once __DIR__ . "/db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
if ($method !== "POST") {
    sendResponse("error", "Method not allowed. Use POST.", null, 405);
}

$input = getJsonInput();

$userId = resolveUserId($pdo, $input);
$macAddress = strtoupper(trim((string)($input["mac_address"] ?? $input["device_address"] ?? "")));
$deviceName = trim((string)($input["device_name"] ?? ""));
$deviceCategory = strtolower(trim((string)($input["device_category"] ?? $input["device_type"] ?? "smartwatch")));
$batteryLevel = isset($input["battery_level"]) && is_numeric($input["battery_level"]) ? (int)$input["battery_level"] : null;

if ($userId <= 0) {
    sendResponse("error", "Valid user_id or registered email is required", null, 400);
}

if (empty($macAddress)) {
    sendResponse("error", "mac_address (or device_address) is required", null, 400);
}

if (empty($deviceName)) {
    $deviceName = "Bluetooth Health Tracker";
}

if (empty($deviceCategory)) {
    $deviceCategory = "smartwatch";
}

try {
    // 1. Verify user exists
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    if (!$userStmt->fetch()) {
        sendResponse("error", "User not found with id: $userId", null, 404);
    }

    // 2. Upsert device into user_devices
    $sql = "INSERT INTO user_devices (user_id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at)
            VALUES (:user_id, :device_name, :mac_address, :device_category, 1, :battery_level, NOW())
            ON DUPLICATE KEY UPDATE 
                device_name = VALUES(device_name),
                device_category = VALUES(device_category),
                is_connected = 1,
                battery_level = COALESCE(VALUES(battery_level), battery_level),
                last_synced_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":user_id" => $userId,
        ":device_name" => $deviceName,
        ":mac_address" => $macAddress,
        ":device_category" => $deviceCategory,
        ":battery_level" => $batteryLevel
    ]);

    // 3. Fetch current device record
    $fetchStmt = $pdo->prepare("SELECT id, user_id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at, created_at FROM user_devices WHERE user_id = ? AND mac_address = ? LIMIT 1");
    $fetchStmt->execute([$userId, $macAddress]);
    $device = $fetchStmt->fetch();

    if (!$device) {
        sendResponse("error", "Failed to retrieve paired device record", null, 500);
    }

    $deviceId = (int)$device["id"];

    // 4. Also keep legacy connected_devices synced if present
    try {
        $legacyStmt = $pdo->prepare(
            "INSERT INTO connected_devices (user_id, device_name, device_type, is_connected, battery_level, last_synced)
             VALUES (?, ?, ?, 1, ?, NOW())
             ON DUPLICATE KEY UPDATE is_connected = 1, battery_level = VALUES(battery_level), last_synced = NOW()"
        );
        $legacyStmt->execute([$userId, $deviceName, $deviceCategory, ($batteryLevel ?? 85)]);
    } catch (\Exception $ex) {
        // Silently skip if table does not exist
    }

    sendResponse("success", "Device paired successfully", [
        "device_id" => $deviceId,
        "id" => $deviceId,
        "user_id" => (int)$device["user_id"],
        "device_name" => $device["device_name"],
        "mac_address" => $device["mac_address"],
        "device_category" => $device["device_category"],
        "is_connected" => (int)$device["is_connected"],
        "battery_level" => $device["battery_level"] !== null ? (int)$device["battery_level"] : null,
        "last_synced_at" => $device["last_synced_at"],
        "created_at" => $device["created_at"]
    ], 200, [
        "device_id" => $deviceId
    ]);

} catch (\PDOException $e) {
    sendResponse("error", "Database error pairing device: " . $e->getMessage(), null, 500);
}
