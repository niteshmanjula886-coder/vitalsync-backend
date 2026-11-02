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

$workoutType = trim((string)($input["workout_type"] ?? $input["type"] ?? "General Workout"));
$status = trim((string)($input["status"] ?? "COMPLETED"));
$durationSeconds = isset($input["duration_seconds"]) ? max(0, (int)$input["duration_seconds"]) : (isset($input["duration_minutes"]) ? (int)$input["duration_minutes"] * 60 : 0);
$calories = isset($input["calories_burned"]) ? max(0, (int)$input["calories_burned"]) : (int)round($durationSeconds * 0.12);
$startedAt = !empty($input["started_at"]) ? trim((string)$input["started_at"]) : date("Y-m-d H:i:s", time() - $durationSeconds);
$endedAt = !empty($input["ended_at"]) ? trim((string)$input["ended_at"]) : date("Y-m-d H:i:s");

if ($durationSeconds <= 0) {
    respondJson(400, [
        "status" => "error",
        "message" => "duration_seconds must be greater than 0",
        "data" => null
    ]);
}

$stmt = $conn->prepare("INSERT INTO workouts (user_id, workout_type, status, duration_seconds, calories_burned, started_at, ended_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ississs", $userId, $workoutType, $status, $durationSeconds, $calories, $startedAt, $endedAt);
$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

$durationFormatted = gmdate("H:i:s", $durationSeconds);

respondJson(201, [
    "status" => "success",
    "message" => "Workout session logged successfully",
    "data" => [
        "id" => $newId,
        "user_id" => $userId,
        "workout_type" => $workoutType,
        "status" => $status,
        "duration_seconds" => $durationSeconds,
        "duration_formatted" => $durationFormatted,
        "calories_burned" => $calories,
        "started_at" => $startedAt,
        "ended_at" => $endedAt,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);

