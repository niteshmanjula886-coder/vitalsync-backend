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

$bedtime = trim((string)($input["bedtime"] ?? "23:00"));
$wakeTime = trim((string)($input["wake_time"] ?? "07:00"));
$durationMinutes = isset($input["duration_minutes"]) ? (int)$input["duration_minutes"] : 0;
$loggedDate = !empty($input["logged_date"]) ? trim((string)$input["logged_date"]) : (!empty($input["date"]) ? trim((string)$input["date"]) : date("Y-m-d"));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loggedDate)) {
    $loggedDate = date("Y-m-d");
}

// If duration not provided directly, calculate from bedtime and wake_time
if ($durationMinutes <= 0 && !empty($bedtime) && !empty($wakeTime)) {
    $bt = strtotime($bedtime);
    $wt = strtotime($wakeTime);
    if ($wt < $bt) {
        $wt += 86400; // Passed midnight
    }
    $durationMinutes = (int)round(($wt - $bt) / 60);
}

if ($durationMinutes <= 0 || $durationMinutes > 1440) {
    respondJson(400, [
        "status" => "error",
        "message" => "Invalid sleep duration. Must be between 1 and 1440 minutes.",
        "data" => null
    ]);
}

// Prompt requirement: Automatically categorize status
// `critical` if < 6 hrs (< 360 min)
// `warning` if 6–8 hrs (360–480 min)
// `optimal` if >= 8 hrs (>= 480 min)
$statusColor = "optimal";
$qualityLabel = "Optimal Sleep";

if ($durationMinutes < 360) {
    $statusColor = "critical";
    $qualityLabel = "Insufficient Sleep";
} elseif ($durationMinutes < 480) {
    $statusColor = "warning";
    $qualityLabel = "Fair Sleep";
} else {
    $statusColor = "optimal";
    $qualityLabel = "Restful Sleep";
}

$hours = intdiv($durationMinutes, 60);
$mins = $durationMinutes % 60;
$durationFormatted = "{$hours}h {$mins}m";
$sleepScore = min(100, max(40, (int)round(($durationMinutes / 480.0) * 90)));
$qualityScore = isset($input["quality_score"]) ? (int)$input["quality_score"] : $sleepScore;

$startTime = !empty($input["start_time"]) ? trim((string)$input["start_time"]) : date("Y-m-d H:i:s", strtotime("$loggedDate $bedtime"));
$endTime = !empty($input["end_time"]) ? trim((string)$input["end_time"]) : date("Y-m-d H:i:s", strtotime("$loggedDate $wakeTime"));
if (strtotime($endTime) <= strtotime($startTime)) {
    $endTime = date("Y-m-d H:i:s", strtotime($startTime) + ($durationMinutes * 60));
}

// Upsert into `sleep_logs` with full user-scoped schema
$checkStmt = $conn->prepare("SELECT id FROM sleep_logs WHERE user_id = ? AND (logged_at = ? OR logged_date = ?) LIMIT 1");
$checkStmt->bind_param("iss", $userId, $loggedDate, $loggedDate);
$checkStmt->execute();
$existingLog = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

$recordId = 0;
if ($existingLog) {
    $recordId = (int)$existingLog["id"];
    $uStmt = $conn->prepare("UPDATE sleep_logs SET start_time = ?, end_time = ?, duration_minutes = ?, quality_score = ?, logged_at = ?, bedtime = ?, wake_time = ?, status_color = ?, logged_date = ? WHERE id = ?");
    $uStmt->bind_param("ssiisssssi", $startTime, $endTime, $durationMinutes, $qualityScore, $loggedDate, $bedtime, $wakeTime, $statusColor, $loggedDate, $recordId);
    $uStmt->execute();
    $uStmt->close();
} else {
    $iStmt = $conn->prepare("INSERT INTO sleep_logs (user_id, start_time, end_time, duration_minutes, quality_score, logged_at, bedtime, wake_time, status_color, logged_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $iStmt->bind_param("isssiissss", $userId, $startTime, $endTime, $durationMinutes, $qualityScore, $loggedDate, $bedtime, $wakeTime, $statusColor, $loggedDate);
    $iStmt->execute();
    $recordId = $iStmt->insert_id;
    $iStmt->close();
}

// Also sync with `sleep_records` table if present
$deepMin = (int)round($durationMinutes * 0.22);
$lightMin = (int)round($durationMinutes * 0.55);
$remMin = (int)round($durationMinutes * 0.18);
$awakeMin = (int)round($durationMinutes * 0.05);

$srStmt = $conn->prepare(
    "INSERT INTO sleep_records (user_id, date_string, duration_minutes, sleep_score, quality_label, bedtime, wake_time, deep_sleep_minutes, light_sleep_minutes, rem_sleep_minutes, awake_minutes)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE 
        duration_minutes = VALUES(duration_minutes),
        sleep_score = VALUES(sleep_score),
        quality_label = VALUES(quality_label),
        bedtime = VALUES(bedtime),
        wake_time = VALUES(wake_time),
        deep_sleep_minutes = VALUES(deep_sleep_minutes),
        light_sleep_minutes = VALUES(light_sleep_minutes),
        rem_sleep_minutes = VALUES(rem_sleep_minutes),
        awake_minutes = VALUES(awake_minutes)"
);
if ($srStmt) {
    $srStmt->bind_param("isiisssiiii", $userId, $loggedDate, $durationMinutes, $sleepScore, $qualityLabel, $bedtime, $wakeTime, $deepMin, $lightMin, $remMin, $awakeMin);
    $srStmt->execute();
    $srStmt->close();
}

// Also record into `vitals_readings`
$vrStmt = $conn->prepare(
    "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at)
     VALUES (?, 'sleep', ?, ?, 'minutes', ?, 'Sleep Tracking', NOW())"
);
if ($vrStmt) {
    $valNum = (float)$durationMinutes;
    $vrStmt->bind_param("idss", $userId, $valNum, $durationFormatted, $qualityLabel);
    $vrStmt->execute();
    $vrStmt->close();
}

respondJson(200, [
    "status" => "success",
    "message" => "Sleep log recorded successfully",
    "data" => [
        "id" => $recordId,
        "user_id" => $userId,
        "bedtime" => $bedtime,
        "wake_time" => $wakeTime,
        "duration_minutes" => $durationMinutes,
        "duration_formatted" => $durationFormatted,
        "status_color" => $statusColor,
        "sleep_score" => $sleepScore,
        "quality_label" => $qualityLabel,
        "logged_date" => $loggedDate,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);

