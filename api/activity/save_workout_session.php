<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "POST") {
    respondJson(405, [
        "success" => false,
        "message" => "Method not allowed. Use POST."
    ]);
}

$data = getInputData();

$activityType = trim((string)($data["activity_type"] ?? $data["type"] ?? "Walking"));
$normType = ucfirst(strtolower($activityType));
if (!in_array($normType, ['Walking', 'Running', 'Cycling'])) {
    $normType = 'Walking';
}

$duration = isset($data["duration_seconds"]) ? (int)$data["duration_seconds"] : (isset($data["duration_minutes"]) ? (int)$data["duration_minutes"] * 60 : 0);
$distance = isset($data["distance_km"]) ? (float)$data["distance_km"] : 0.00;
$steps = isset($data["steps"]) ? (int)$data["steps"] : 0;
$calories = isset($data["calories"]) ? (int)$data["calories"] : (isset($data["calories_burned"]) ? (int)$data["calories_burned"] : (int)round($duration * 0.12));
$pace = trim((string)($data["avg_pace"] ?? ""));
$startTime = !empty($data["start_time"]) ? trim((string)$data["start_time"]) : date("Y-m-d H:i:s", time() - max(1, $duration));
$endTime = !empty($data["end_time"]) ? trim((string)$data["end_time"]) : date("Y-m-d H:i:s");
$sessionUuid = !empty($data["session_uuid"]) ? trim((string)$data["session_uuid"]) : bin2hex(random_bytes(16));
$targetType = strtolower(trim((string)($data["target_type"] ?? "time")));
if (!in_array($targetType, ["time", "distance"])) {
    $targetType = "time";
}
$targetValue = isset($data["target_value"]) ? (int)$data["target_value"] : 45;
$trackingSource = trim((string)($data["tracking_source"] ?? "phone_sensor"));
if ($trackingSource === "") $trackingSource = "phone_sensor";

if ($normType === 'Cycling') {
    $steps = 0;
    if ($trackingSource === "phone_sensor") {
        $trackingSource = "gps";
    }
}

// 1. Insert or update into workout_sessions
$stmt = $conn->prepare(
    "INSERT INTO workout_sessions (
        user_id, session_uuid, activity_type, start_time, end_time,
        duration_seconds, target_type, target_value, steps, distance_km,
        avg_pace, calories, tracking_source
     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        duration_seconds = VALUES(duration_seconds),
        target_type = VALUES(target_type),
        target_value = VALUES(target_value),
        steps = VALUES(steps),
        distance_km = VALUES(distance_km),
        avg_pace = VALUES(avg_pace),
        calories = VALUES(calories),
        tracking_source = VALUES(tracking_source),
        end_time = VALUES(end_time)"
);

if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database error preparing workout_sessions statement: " . $conn->error
    ]);
}

$stmt->bind_param(
    "issssisidssis",
    $userId, $sessionUuid, $normType, $startTime, $endTime,
    $duration, $targetType, $targetValue, $steps, $distance,
    $pace, $calories, $trackingSource
);

$saved = $stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

if (!$saved) {
    respondJson(500, [
        "success" => false,
        "message" => "Failed to save workout session: " . $conn->error
    ]);
}

// 2. Also keep activity_sessions in sync for legacy compatibility
$upperType = strtoupper($normType);
$legacyStmt = $conn->prepare(
    "INSERT INTO activity_sessions (
        user_id, activity_type, duration_seconds, distance_km, calories_burned, avg_pace, steps, start_time, end_time
     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if ($legacyStmt) {
    $legacyStmt->bind_param("isidisiss", $userId, $upperType, $duration, $distance, $calories, $pace, $steps, $startTime, $endTime);
    $legacyStmt->execute();
    $legacyStmt->close();
}

$conn->close();

respondJson(200, [
    "success" => true,
    "message" => "Workout session saved successfully",
    "session" => [
        "id" => $newId,
        "session_uuid" => $sessionUuid,
        "activity_type" => $normType,
        "start_time" => $startTime,
        "end_time" => $endTime,
        "duration_seconds" => $duration,
        "target_type" => $targetType,
        "target_value" => $targetValue,
        "steps" => $steps,
        "distance_km" => $distance,
        "avg_pace" => $pace,
        "calories" => $calories,
        "tracking_source" => $trackingSource
    ]
]);
