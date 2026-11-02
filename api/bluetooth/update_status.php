<?php

require_once __DIR__ . "/db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
if ($method !== "POST") {
    sendResponse("error", "Method not allowed. Use POST.", null, 405);
}

$input = getJsonInput();

$userId = resolveUserId($pdo, $input);
$deviceId = isset($input["device_id"]) && is_numeric($input["device_id"]) ? (int)$input["device_id"] : 0;
$macAddress = strtoupper(trim((string)($input["mac_address"] ?? $input["device_address"] ?? "")));

if ($userId <= 0) {
    sendResponse("error", "Valid user_id or registered email is required", null, 400);
}

if (!isset($input["is_connected"]) && !isset($input["connected"])) {
    sendResponse("error", "is_connected (0 or 1) is required", null, 400);
}

$rawConnected = isset($input["is_connected"]) ? $input["is_connected"] : $input["connected"];
$isConnected = ($rawConnected === true || $rawConnected === 1 || $rawConnected === "1" || $rawConnected === "true") ? 1 : 0;
$batteryLevel = isset($input["battery_level"]) && is_numeric($input["battery_level"]) ? (int)$input["battery_level"] : null;
if ($batteryLevel !== null) {
    $batteryLevel = max(0, min(100, $batteryLevel));
}

try {
    // 1. Locate device record
    $targetId = 0;
    if ($deviceId > 0) {
        $stmtFind = $pdo->prepare("SELECT id FROM user_devices WHERE id = ? AND user_id = ? LIMIT 1");
        $stmtFind->execute([$deviceId, $userId]);
        $row = $stmtFind->fetch();
        if ($row) $targetId = (int)$row["id"];
    } elseif (!empty($macAddress)) {
        $stmtFind = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? AND mac_address = ? LIMIT 1");
        $stmtFind->execute([$userId, $macAddress]);
        $row = $stmtFind->fetch();
        if ($row) $targetId = (int)$row["id"];
    } else {
        // Fallback to most recent device for this user
        $stmtFind = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? ORDER BY last_synced_at DESC, id DESC LIMIT 1");
        $stmtFind->execute([$userId]);
        $row = $stmtFind->fetch();
        if ($row) $targetId = (int)$row["id"];
    }

    if ($targetId <= 0) {
        sendResponse("error", "No matching device found for user_id: $userId", null, 404);
    }

    // 2. Perform update
    if ($batteryLevel !== null) {
        $updateSql = "UPDATE user_devices SET is_connected = ?, battery_level = ?, last_synced_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$isConnected, $batteryLevel, $targetId]);
    } else {
        $updateSql = "UPDATE user_devices SET is_connected = ?, last_synced_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$isConnected, $targetId]);
    }

    // 3. Fetch updated device details
    $fetchStmt = $pdo->prepare("SELECT id, user_id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at, updated_at FROM user_devices WHERE id = ? LIMIT 1");
    $fetchStmt->execute([$targetId]);
    $updated = $fetchStmt->fetch();

    // Also update legacy connected_devices if present
    try {
        $legStmt = $pdo->prepare("UPDATE connected_devices SET is_connected = ?, last_synced = NOW() WHERE user_id = ? AND device_name = ?");
        $legStmt->execute([$isConnected, $userId, $updated["device_name"]]);
    } catch (\Exception $ex) {
        // Silently skip
    }

    sendResponse("success", "Device status updated successfully", [
        "id" => (int)$updated["id"],
        "device_id" => (int)$updated["id"],
        "user_id" => (int)$updated["user_id"],
        "device_name" => $updated["device_name"],
        "mac_address" => $updated["mac_address"],
        "device_category" => $updated["device_category"],
        "is_connected" => (int)$updated["is_connected"],
        "battery_level" => $updated["battery_level"] !== null ? (int)$updated["battery_level"] : null,
        "last_synced_at" => $updated["last_synced_at"]
    ], 200);

} catch (\PDOException $e) {
    sendResponse("error", "Database error updating device status: " . $e->getMessage(), null, 500);
}
