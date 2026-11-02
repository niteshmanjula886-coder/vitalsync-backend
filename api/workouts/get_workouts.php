<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use GET.",
        "data" => null
    ]);
}

$limit = isset($_GET["limit"]) ? max(1, min(100, (int)$_GET["limit"])) : 30;

$stmt = $conn->prepare("SELECT id, workout_type, status, duration_seconds, calories_burned, started_at, ended_at, created_at FROM workouts WHERE user_id = ? ORDER BY started_at DESC LIMIT ?");
$stmt->bind_param("ii", $userId, $limit);
$stmt->execute();
$result = $stmt->get_result();

$workouts = [];
$totalSeconds = 0;
$totalCalories = 0;

while ($row = $result->fetch_assoc()) {
    $sec = (int)$row["duration_seconds"];
    $totalSeconds += $sec;
    $totalCalories += (int)$row["calories_burned"];

    $workouts[] = [
        "id" => (int)$row["id"],
        "workout_type" => $row["workout_type"],
        "status" => $row["status"],
        "duration_seconds" => $sec,
        "duration_formatted" => gmdate("H:i:s", $sec),
        "calories_burned" => (int)$row["calories_burned"],
        "started_at" => $row["started_at"],
        "ended_at" => $row["ended_at"],
        "created_at" => $row["created_at"]
    ];
}
$stmt->close();

respondJson(200, [
    "status" => "success",
    "message" => "Workouts retrieved successfully",
    "data" => [
        "user_id" => $userId,
        "count" => count($workouts),
        "total_duration_minutes" => (int)round($totalSeconds / 60.0),
        "total_calories_burned" => $totalCalories,
        "workouts" => $workouts
    ]
]);

