<?php

require_once __DIR__ . "/db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
if ($method !== "POST") {
    sendResponse("error", "Method not allowed. Use POST.", null, 405);
}

$input = getJsonInput();

$userId = resolveUserId($pdo, $input);
$macAddress = strtoupper(trim((string)($input["mac_address"] ?? $input["device_address"] ?? "")));
$deviceId = isset($input["device_id"]) && is_numeric($input["device_id"]) ? (int)$input["device_id"] : null;

if ($userId <= 0) {
    sendResponse("error", "Valid user_id or registered email is required", null, 400);
}

// Extract readings array
$readings = [];
if (isset($input["readings"]) && is_array($input["readings"])) {
    $readings = $input["readings"];
} elseif (isset($input["vitals"]) && is_array($input["vitals"])) {
    $readings = $input["vitals"];
} elseif (isset($input["heart_rate"]) || isset($input["spo2"]) || isset($input["spo2_percent"]) || isset($input["systolic_bp"]) || isset($input["bp"])) {
    // Single reading sent at root level
    $readings = [$input];
}

if (empty($readings)) {
    sendResponse("error", "No vital readings provided in request payload", null, 400);
}

try {
    // 1. Verify user exists
    $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        sendResponse("error", "User not found with id: $userId", null, 404);
    }

    // 2. Resolve device_id from mac_address
    if ($deviceId === null && !empty($macAddress)) {
        $devStmt = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? AND mac_address = ? LIMIT 1");
        $devStmt->execute([$userId, $macAddress]);
        $devRow = $devStmt->fetch();
        if ($devRow) {
            $deviceId = (int)$devRow["id"];
        } else {
            // Auto-register device if not already paired
            $autoStmt = $pdo->prepare("INSERT INTO user_devices (user_id, device_name, mac_address, device_category, is_connected, last_synced_at) VALUES (?, 'Bluetooth Health Tracker', ?, 'smartwatch', 1, NOW())");
            $autoStmt->execute([$userId, $macAddress]);
            $deviceId = (int)$pdo->lastInsertId();
        }
    }

    if ($deviceId === null) {
        // Fallback to user's most recent connected device
        $devStmt2 = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? ORDER BY is_connected DESC, last_synced_at DESC LIMIT 1");
        $devStmt2->execute([$userId]);
        $devRow2 = $devStmt2->fetch();
        if ($devRow2) {
            $deviceId = (int)$devRow2["id"];
        }
    }

    // 3. Begin PDO Transaction
    $pdo->beginTransaction();

    $insertSql = "INSERT INTO vitals_logs (user_id, device_id, heart_rate, spo2, systolic_bp, diastolic_bp, source, logged_at)
                  VALUES (:user_id, :device_id, :heart_rate, :spo2, :systolic_bp, :diastolic_bp, :source, :logged_at)";
    $stmt = $pdo->prepare($insertSql);

    // Prepare vitals_readings for dashboard backward compatibility
    $vrSql = "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, recorded_at)
              VALUES (:user_id, :type, :val1, :val2, :text, :unit, :status, :source, :logged_at)";
    $vrStmt = $pdo->prepare($vrSql);

    $syncedCount = 0;
    $latestLoggedAt = date("Y-m-d H:i:s");

    foreach ($readings as $item) {
        if (!is_array($item)) continue;

        $hr = isset($item["heart_rate"]) && is_numeric($item["heart_rate"]) ? (int)$item["heart_rate"] : null;
        
        $spo2 = null;
        if (isset($item["spo2"]) && is_numeric($item["spo2"])) {
            $spo2 = (int)$item["spo2"];
        } elseif (isset($item["spo2_percent"]) && is_numeric($item["spo2_percent"])) {
            $spo2 = (int)$item["spo2_percent"];
        }

        $sys = null;
        $dia = null;
        if (isset($item["systolic_bp"]) && is_numeric($item["systolic_bp"])) {
            $sys = (int)$item["systolic_bp"];
        } elseif (isset($item["systolic"]) && is_numeric($item["systolic"])) {
            $sys = (int)$item["systolic"];
        }

        if (isset($item["diastolic_bp"]) && is_numeric($item["diastolic_bp"])) {
            $dia = (int)$item["diastolic_bp"];
        } elseif (isset($item["diastolic"]) && is_numeric($item["diastolic"])) {
            $dia = (int)$item["diastolic"];
        }

        // Support string format "120/80"
        if ($sys === null && !empty($item["bp"]) && is_string($item["bp"]) && strpos($item["bp"], "/") !== false) {
            $parts = explode("/", $item["bp"]);
            $sys = (int)trim($parts[0]);
            $dia = isset($parts[1]) ? (int)trim($parts[1]) : null;
        }

        // Timestamp parsing
        $tsRaw = !empty($item["logged_at"]) ? $item["logged_at"] : (!empty($item["timestamp"]) ? $item["timestamp"] : date("Y-m-d H:i:s"));
        if (is_numeric($tsRaw)) {
            $tsVal = (int)($tsRaw > 9999999999 ? $tsRaw / 1000 : $tsRaw);
            $loggedAt = date("Y-m-d H:i:s", $tsVal);
        } else {
            $parsed = strtotime($tsRaw);
            $loggedAt = $parsed !== false ? date("Y-m-d H:i:s", $parsed) : date("Y-m-d H:i:s");
        }
        $latestLoggedAt = $loggedAt;

        $source = !empty($item["source"]) ? trim((string)$item["source"]) : "bluetooth";

        // Physiological range boundaries
        if ($hr !== null && ($hr < 30 || $hr > 250)) $hr = null;
        if ($spo2 !== null && ($spo2 < 60 || $spo2 > 100)) $spo2 = null;
        if ($sys !== null && ($sys < 40 || $sys > 260)) $sys = null;
        if ($dia !== null && ($dia < 30 || $dia > 180)) $dia = null;

        if ($hr === null && $spo2 === null && $sys === null && $dia === null) {
            continue;
        }

        // Insert into vitals_logs
        $stmt->execute([
            ":user_id" => $userId,
            ":device_id" => $deviceId,
            ":heart_rate" => $hr,
            ":spo2" => $spo2,
            ":systolic_bp" => $sys,
            ":diastolic_bp" => $dia,
            ":source" => $source,
            ":logged_at" => $loggedAt
        ]);

        // Sync HR into vitals_readings
        if ($hr !== null && $hr > 0) {
            $hrStatus = ($hr >= 60 && $hr <= 100) ? "Normal" : ($hr < 60 ? "Low Rate" : "Elevated");
            $vrStmt->execute([
                ":user_id" => $userId,
                ":type" => "heart_rate",
                ":val1" => (float)$hr,
                ":val2" => null,
                ":text" => "$hr BPM",
                ":unit" => "bpm",
                ":status" => $hrStatus,
                ":source" => "Bluetooth Device",
                ":logged_at" => $loggedAt
            ]);
        }

        // Sync SpO2 into vitals_readings
        if ($spo2 !== null && $spo2 > 0) {
            $spo2Status = $spo2 >= 95 ? "Optimal" : "Low";
            $vrStmt->execute([
                ":user_id" => $userId,
                ":type" => "spo2",
                ":val1" => (float)$spo2,
                ":val2" => null,
                ":text" => "$spo2%",
                ":unit" => "%",
                ":status" => $spo2Status,
                ":source" => "Bluetooth Device",
                ":logged_at" => $loggedAt
            ]);
        }

        // Sync BP into vitals_readings
        if ($sys !== null && $dia !== null) {
            $bpEval = evaluateBloodPressure($sys, $dia);
            $bpStatus = $bpEval["status_title"];
            $vrStmt->execute([
                ":user_id" => $userId,
                ":type" => "blood_pressure",
                ":val1" => (float)$sys,
                ":val2" => (float)$dia,
                ":text" => "$sys/$dia mmHg",
                ":unit" => "mmHg",
                ":status" => $bpStatus,
                ":source" => "Bluetooth Device",
                ":logged_at" => $loggedAt
            ]);
        }

        $syncedCount++;
    }

    // Update battery and sync timestamp on device
    if ($deviceId !== null) {
        $battery = isset($input["battery_level"]) && is_numeric($input["battery_level"]) ? (int)$input["battery_level"] : null;
        if ($battery !== null) {
            $battery = max(0, min(100, $battery));
            $upDev = $pdo->prepare("UPDATE user_devices SET last_synced_at = NOW(), is_connected = 1, battery_level = ? WHERE id = ?");
            $upDev->execute([$battery, $deviceId]);
        } else {
            $upDev = $pdo->prepare("UPDATE user_devices SET last_synced_at = NOW(), is_connected = 1 WHERE id = ?");
            $upDev->execute([$deviceId]);
        }
    }

    // Commit Transaction
    $pdo->commit();

    sendResponse("success", "Vitals synchronized successfully", [
        "user_id" => $userId,
        "device_id" => $deviceId,
        "synced_count" => $syncedCount,
        "last_synced_at" => $latestLoggedAt
    ], 200);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse("error", "Transaction failed to sync vitals: " . $e->getMessage(), null, 500);
}
