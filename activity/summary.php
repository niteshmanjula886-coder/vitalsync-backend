<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$today = date("Y-m-d");

// Get goals
$goalStmt = $conn->prepare("SELECT daily_step_goal, daily_calorie_goal, daily_active_minutes_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
$goalStmt->bind_param("i", $userId);
$goalStmt->execute();
$goalRow = $goalStmt->get_result()->fetch_assoc();
$goalStmt->close();

$stepGoal = $goalRow ? (int)$goalRow["daily_step_goal"] : 8000;
$calorieGoal = $goalRow ? (int)$goalRow["daily_calorie_goal"] : 500;
$activeMinGoal = $goalRow ? (int)$goalRow["daily_active_minutes_goal"] : 45;

// Aggregate today's sessions
$aggStmt = $conn->prepare(
    "SELECT 
        COALESCE(SUM(steps), 0) AS total_steps,
        COALESCE(SUM(distance_km), 0.00) AS total_distance,
        COALESCE(SUM(calories_burned), 0) AS total_calories,
        COALESCE(SUM(duration_seconds), 0) AS total_seconds,
        COUNT(id) AS session_count
     FROM activity_sessions 
     WHERE user_id = ? AND DATE(start_time) = ?"
);
$aggStmt->bind_param("is", $userId, $today);
$aggStmt->execute();
$agg = $aggStmt->get_result()->fetch_assoc();
$aggStmt->close();

$steps = (int)$agg["total_steps"];
$distance = (float)$agg["total_distance"];
$calories = (int)$agg["total_calories"];
$activeMinutes = intdiv($agg["total_seconds"], 60);

$conn->close();

respondJson(200, [
    "success" => true,
    "date" => $today,
    "summary" => [
        "steps" => [
            "current" => $steps,
            "goal" => $stepGoal,
            "progress_percent" => min(100, (int)(($steps / $stepGoal) * 100))
        ],
        "distance_km" => $distance,
        "calories_burned_kcal" => [
            "current" => $calories,
            "goal" => $calorieGoal,
            "progress_percent" => min(100, (int)(($calories / $calorieGoal) * 100))
        ],
        "active_minutes" => [
            "current" => $activeMinutes,
            "goal" => $activeMinGoal,
            "progress_percent" => min(100, (int)(($activeMinutes / $activeMinGoal) * 100))
        ],
        "sessions_count" => (int)$agg["session_count"]
    ]
]);

