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

$stepCount = isset($input["step_count"]) ? max(0, (int)$input["step_count"]) : (isset($input["steps"]) ? max(0, (int)$input["steps"]) : 0);
$targetSteps = isset($input["target_steps"]) ? max(1000, (int)$input["target_steps"]) : (isset($input["goal_steps"]) ? max(1000, (int)$input["goal_steps"]) : 8000);
$distanceKm = isset($input["distance_km"]) ? max(0.0, (float)$input["distance_km"]) : round($stepCount * 0.00075, 2);
$caloriesKcal = isset($input["calories_kcal"]) ? max(0, (int)$input["calories_kcal"]) : (isset($input["calories"]) ? max(0, (int)$input["calories"]) : (int)round($stepCount * 0.04));
$activeMinutes = isset($input["active_minutes"]) ? max(0, (int)$input["active_minutes"]) : (int)round($stepCount / 100);
$loggedDate = !empty($input["logged_date"]) ? trim((string)$input["logged_date"]) : (!empty($input["date"]) ? trim((string)$input["date"]) : date("Y-m-d"));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loggedDate)) {
    $loggedDate = date("Y-m-d");
}

// Check existing steps log for this user and date
$checkStmt = $conn->prepare("SELECT id, step_count, target_steps, distance_km, calories_kcal, active_minutes FROM steps_logs WHERE user_id = ? AND logged_date = ? LIMIT 1");
$checkStmt->bind_param("is", $userId, $loggedDate);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

$recordId = 0;
if ($existing) {
    $recordId = (int)$existing["id"];
    $uStmt = $conn->prepare("UPDATE steps_logs SET step_count = ?, target_steps = ?, distance_km = ?, calories_kcal = ?, active_minutes = ? WHERE id = ?");
    $uStmt->bind_param("iidiii", $stepCount, $targetSteps, $distanceKm, $caloriesKcal, $activeMinutes, $recordId);
    $uStmt->execute();
    $uStmt->close();
} else {
    $iStmt = $conn->prepare("INSERT INTO steps_logs (user_id, step_count, target_steps, distance_km, calories_kcal, active_minutes, logged_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $iStmt->bind_param("iidiiis", $userId, $stepCount, $targetSteps, $distanceKm, $caloriesKcal, $activeMinutes, $loggedDate);
    $iStmt->execute();
    $recordId = $iStmt->insert_id;
    $iStmt->close();
}

// Also update `activity_goals` table if exists
$agStmt = $conn->prepare("INSERT INTO activity_goals (user_id, daily_step_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_step_goal = VALUES(daily_step_goal)");
if ($agStmt) {
    $agStmt->bind_param("ii", $userId, $targetSteps);
    $agStmt->execute();
    $agStmt->close();
}

// Also sync with `vitals_readings` for step history
if ($stepCount > 0) {
    $vrStmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at)
         VALUES (?, 'steps', ?, ?, 'steps', 'Active', 'Phone Sensor', NOW())"
    );
    if ($vrStmt) {
        $valNum = (float)$stepCount;
        $valText = number_format($stepCount) . " steps";
        $vrStmt->bind_param("ids", $userId, $valNum, $valText);
        $vrStmt->execute();
        $vrStmt->close();
    }
}

$progressPercent = $targetSteps > 0 ? min(100, (int)round(($stepCount / $targetSteps) * 100)) : 0;

respondJson(200, [
    "status" => "success",
    "message" => "Daily steps synced successfully",
    "data" => [
        "id" => $recordId,
        "user_id" => $userId,
        "step_count" => $stepCount,
        "target_steps" => $targetSteps,
        "distance_km" => $distanceKm,
        "calories_kcal" => $caloriesKcal,
        "active_minutes" => $activeMinutes,
        "progress_percent" => $progressPercent,
        "logged_date" => $loggedDate,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);

