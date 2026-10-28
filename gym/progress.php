<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

// Total lifetime volume & session count
$totStmt = $conn->prepare(
    "SELECT 
        COUNT(id) AS total_workouts, 
        COALESCE(SUM(total_volume_kg), 0.0) AS lifetime_volume, 
        COALESCE(SUM(duration_minutes), 0) AS total_minutes, 
        COALESCE(SUM(estimated_calories_burned), 0) AS total_burned 
     FROM workout_sessions 
     WHERE user_id = ?"
);
$totStmt->bind_param("i", $userId);
$totStmt->execute();
$stats = $totStmt->get_result()->fetch_assoc();
$totStmt->close();

$totalWorkouts = (int)$stats["total_workouts"];
$lifetimeVolume = (float)$stats["lifetime_volume"];
$totalMinutes = (int)$stats["total_minutes"];
$totalBurned = (int)$stats["total_burned"];

// Workouts this week
$startOfWeek = date("Y-m-d", strtotime("monday this week"));
$weekStmt = $conn->prepare(
    "SELECT COUNT(id) AS weekly_count, COALESCE(SUM(total_volume_kg), 0.0) AS weekly_volume 
     FROM workout_sessions 
     WHERE user_id = ? AND date_string >= ?"
);
$weekStmt->bind_param("is", $userId, $startOfWeek);
$weekStmt->execute();
$weekStats = $weekStmt->get_result()->fetch_assoc();
$weekStmt->close();

$weeklyWorkouts = (int)$weekStats["weekly_count"];
$weeklyVolume = (float)$weekStats["weekly_volume"];

// Most frequent split
$splitStmt = $conn->prepare(
    "SELECT split_type, COUNT(id) AS count 
     FROM workout_sessions 
     WHERE user_id = ? 
     GROUP BY split_type 
     ORDER BY count DESC LIMIT 1"
);
$splitStmt->bind_param("i", $userId);
$splitStmt->execute();
$favSplitRow = $splitStmt->get_result()->fetch_assoc();
$splitStmt->close();
$favoriteSplit = $favSplitRow ? $favSplitRow["split_type"] : "PUSH";

$conn->close();

respondJson(200, [
    "success" => true,
    "progress" => [
        "total_workouts" => $totalWorkouts,
        "weekly_workouts" => $weeklyWorkouts,
        "weekly_target_workouts" => 4,
        "lifetime_volume_kg" => $lifetimeVolume,
        "weekly_volume_kg" => $weeklyVolume,
        "total_workout_minutes" => $totalMinutes,
        "total_calories_burned" => $totalBurned,
        "favorite_split" => $favoriteSplit,
        "current_streak_days" => max(1, min(7, $weeklyWorkouts))
    ]
]);

