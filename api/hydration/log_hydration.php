<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "POST") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use POST.",
        "data" => null
    ]);
}

$input = getInputData();

$intakeMl = isset($input["volume_ml"]) ? (int)$input["volume_ml"] : (isset($input["intake_ml"]) ? (int)$input["intake_ml"] : (isset($input["amount_ml"]) ? (int)$input["amount_ml"] : 0));
$targetMl = isset($input["target_ml"]) ? (int)$input["target_ml"] : 2000;
$loggedDate = !empty($input["log_date"]) ? trim((string)$input["log_date"]) : (!empty($input["logged_date"]) ? trim((string)$input["logged_date"]) : (!empty($input["date"]) ? trim((string)$input["date"]) : date("Y-m-d")));
$loggedAt = !empty($input["logged_at"]) ? trim((string)$input["logged_at"]) : date("Y-m-d H:i:s");
$mode = strtolower(trim((string)($input["mode"] ?? "add"))); // "add" to increment or "set" to overwrite

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loggedDate)) {
    $loggedDate = date("Y-m-d");
}

if ($intakeMl < 0) {
    respondJson(400, [
        "status" => "error",
        "message" => "volume_ml/intake_ml must be greater than or equal to 0",
        "data" => null
    ]);
}

if ($targetMl <= 0) {
    $targetMl = 2000;
}

// 1. Check existing record for this user and date
$stmt = $conn->prepare("SELECT id, volume_ml, intake_ml, amount_ml, target_ml FROM hydration_logs WHERE user_id = ? AND (log_date = ? OR logged_date = ? OR date_string = ?) ORDER BY id DESC LIMIT 1");
$stmt->bind_param("isss", $userId, $loggedDate, $loggedDate, $loggedDate);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$finalIntake = $intakeMl;
$recordId = 0;

if ($existing) {
    $recordId = (int)$existing["id"];
    $currentVal = (int)($existing["volume_ml"] !== null ? $existing["volume_ml"] : ($existing["intake_ml"] !== null ? $existing["intake_ml"] : $existing["amount_ml"]));
    if ($mode === "add") {
        $finalIntake = $currentVal + $intakeMl;
    } else {
        $finalIntake = $intakeMl;
    }

    $uStmt = $conn->prepare("UPDATE hydration_logs SET volume_ml = ?, intake_ml = ?, amount_ml = ?, target_ml = ?, log_date = ?, logged_date = ?, date_string = ?, logged_at = ? WHERE id = ?");
    $uStmt->bind_param("iiiissssi", $finalIntake, $finalIntake, $finalIntake, $targetMl, $loggedDate, $loggedDate, $loggedDate, $loggedAt, $recordId);
    $uStmt->execute();
    $uStmt->close();
} else {
    $iStmt = $conn->prepare("INSERT INTO hydration_logs (user_id, volume_ml, intake_ml, amount_ml, target_ml, log_date, logged_date, date_string, logged_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $iStmt->bind_param("iiiiissss", $userId, $finalIntake, $finalIntake, $finalIntake, $targetMl, $loggedDate, $loggedDate, $loggedDate, $loggedAt);
    $iStmt->execute();
    $recordId = $iStmt->insert_id;
    $iStmt->close();
}

// 2. Also update hydration_goals table if exists
$gStmt = $conn->prepare("INSERT INTO hydration_goals (user_id, daily_target_ml) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_target_ml = VALUES(daily_target_ml)");
if ($gStmt) {
    $gStmt->bind_param("ii", $userId, $targetMl);
    $gStmt->execute();
    $gStmt->close();
}

$progressPercent = $targetMl > 0 ? min(100, (int)round(($finalIntake / $targetMl) * 100)) : 0;
$glasses = round($finalIntake / 250.0, 1);

respondJson(200, [
    "status" => "success",
    "message" => "Hydration logged successfully",
    "data" => [
        "id" => $recordId,
        "user_id" => $userId,
        "intake_ml" => $finalIntake,
        "target_ml" => $targetMl,
        "glasses" => $glasses,
        "progress_percent" => $progressPercent,
        "logged_date" => $loggedDate,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);

