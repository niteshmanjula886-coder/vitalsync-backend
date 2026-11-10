<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$today = date("Y-m-d");

// Get goals safely across activity_goals and user_health_goals
$goalRow = null;
$goalStmt = $conn->prepare("SELECT daily_step_goal, daily_calorie_goal, daily_active_minutes_goal, updated_at FROM activity_goals WHERE user_id = ? LIMIT 1");
if ($goalStmt) {
    $goalStmt->bind_param("i", $userId);
    $goalStmt->execute();
    $goalRow = $goalStmt->get_result()->fetch_assoc();
    $goalStmt->close();
}

$hgStepGoal = 0;
$hgUpdatedAt = null;
$hgGoalStmt = $conn->prepare("SELECT target_steps, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
if ($hgGoalStmt) {
    $hgGoalStmt->bind_param("i", $userId);
    $hgGoalStmt->execute();
    $hgGoalRow = $hgGoalStmt->get_result()->fetch_assoc();
    $hgGoalStmt->close();
    if ($hgGoalRow && (int)($hgGoalRow["target_steps"] ?? 0) > 0) {
        $hgStepGoal = (int)$hgGoalRow["target_steps"];
        $hgUpdatedAt = $hgGoalRow["updated_at"] ?? null;
    }
}

$agStepGoal = ($goalRow && (int)($goalRow["daily_step_goal"] ?? 0) > 0) ? (int)$goalRow["daily_step_goal"] : 0;
$agUpdatedAt = $goalRow["updated_at"] ?? null;
if ($agStepGoal > 0 && $hgStepGoal > 0) {
    $stepGoal = ($hgUpdatedAt !== null && $agUpdatedAt !== null && strtotime($hgUpdatedAt) > strtotime($agUpdatedAt))
        ? $hgStepGoal : $agStepGoal;
} elseif ($agStepGoal > 0) {
    $stepGoal = $agStepGoal;
} elseif ($hgStepGoal > 0) {
    $stepGoal = $hgStepGoal;
} else {
    $stepGoal = 8000;
}
$calorieGoal = ($goalRow && (int)($goalRow["daily_calorie_goal"] ?? 0) > 0) ? (int)$goalRow["daily_calorie_goal"] : 500;
$activeMinGoal = ($goalRow && (int)($goalRow["daily_active_minutes_goal"] ?? 0) > 0) ? (int)$goalRow["daily_active_minutes_goal"] : 45;

// Aggregate today's sessions (workout_sessions first, fallback to activity_sessions)
$steps = 0;
$distance = 0.00;
$calories = 0;
$totalSeconds = 0;
$sessionCount = 0;

$wsAgg = $conn->prepare(
    "SELECT 
        COALESCE(SUM(steps), 0) AS total_steps,
        COALESCE(SUM(CASE WHEN distance_km > 0 THEN distance_km ELSE distance_meters/1000.0 END), 0.00) AS total_distance,
        COALESCE(SUM(calories), 0) AS total_calories,
        COALESCE(SUM(duration_seconds), 0) AS total_seconds,
        COUNT(id) AS session_count
     FROM workout_sessions 
     WHERE user_id = ? AND DATE(start_time) = ?"
);
if ($wsAgg) {
    $wsAgg->bind_param("is", $userId, $today);
    $wsAgg->execute();
    $wsRow = $wsAgg->get_result()->fetch_assoc();
    if ($wsRow && (int)$wsRow["session_count"] > 0) {
        $steps = (int)$wsRow["total_steps"];
        $distance = round((float)$wsRow["total_distance"], 2);
        $calories = (int)$wsRow["total_calories"];
        $totalSeconds = (int)$wsRow["total_seconds"];
        $sessionCount = (int)$wsRow["session_count"];
    }
    $wsAgg->close();
}

if ($sessionCount === 0) {
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
    if ($aggStmt) {
        $aggStmt->bind_param("is", $userId, $today);
        $aggStmt->execute();
        $agg = $aggStmt->get_result()->fetch_assoc();
        if ($agg) {
            $steps = (int)$agg["total_steps"];
            $distance = round((float)$agg["total_distance"], 2);
            $calories = (int)$agg["total_calories"];
            $totalSeconds = (int)$agg["total_seconds"];
            $sessionCount = (int)$agg["session_count"];
        }
        $aggStmt->close();
    }
}

$activeMinutes = intdiv($totalSeconds, 60);

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
        "sessions_count" => $sessionCount
    ]
]);

