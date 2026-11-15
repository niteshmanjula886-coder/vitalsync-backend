<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$conn->query("
    CREATE TABLE IF NOT EXISTS gym_workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        split_name VARCHAR(100) NOT NULL,
        duration_minutes INT NOT NULL DEFAULT 0,
        total_volume_kg FLOAT NOT NULL DEFAULT 0,
        total_sets INT NOT NULL DEFAULT 0,
        calories_burned INT NOT NULL DEFAULT 0,
        exercises_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$userId = resolveUserIdOrDie($conn, true);
$targetDate = isset($_GET["date"]) ? trim($_GET["date"]) : null;

if ($targetDate) {
    $stmt = $conn->prepare("
        SELECT id, split_name, duration_minutes, total_volume_kg, total_sets, calories_burned, exercises_json, created_at
        FROM gym_workouts
        WHERE user_id = ? AND DATE(created_at) = ?
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->bind_param("is", $userId, $targetDate);
} else {
    $stmt = $conn->prepare("
        SELECT id, split_name, duration_minutes, total_volume_kg, total_sets, calories_burned, exercises_json, created_at
        FROM gym_workouts
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
}

if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database prepare error: " . $conn->error
    ]);
}

$stmt->execute();
$res = $stmt->get_result();

$workouts = [];
$todayDateStr = date("Y-m-d");

while ($row = $res->fetch_assoc()) {
    $rawCreated = $row["created_at"];
    $rowDateStr = date("Y-m-d", strtotime($rawCreated));
    $exercises = json_decode($row["exercises_json"] ?? "[]", true) ?: [];

    $timeFormatted = ($rowDateStr === $todayDateStr)
        ? "Today, " . date("h:i A", strtotime($rawCreated))
        : date("M d, h:i A", strtotime($rawCreated));

    $workouts[] = [
        "id" => (string)$row["id"],
        "name" => $row["split_name"] . " Session",
        "split" => $row["split_name"],
        "durationMinutes" => (int)$row["duration_minutes"],
        "totalVolumeKg" => (float)$row["total_volume_kg"],
        "totalSets" => (int)$row["total_sets"],
        "estimatedCaloriesBurned" => (int)$row["calories_burned"],
        "dateString" => $rowDateStr,
        "timestampFormatted" => $timeFormatted,
        "exercises" => $exercises,
        "created_at" => $rawCreated
    ];
}
$stmt->close();

respondJson(200, [
    "success" => true,
    "count" => count($workouts),
    "workout_sessions" => $workouts,
    "workouts" => $workouts
]);
