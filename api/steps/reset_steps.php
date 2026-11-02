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
$loggedDate = !empty($input["logged_date"]) ? trim((string)$input["logged_date"]) : (!empty($input["date"]) ? trim((string)$input["date"]) : date("Y-m-d"));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loggedDate)) {
    $loggedDate = date("Y-m-d");
}

// Reset steps_logs row
$uStmt = $conn->prepare("UPDATE steps_logs SET step_count = 0, distance_km = 0.00, calories_kcal = 0, active_minutes = 0 WHERE user_id = ? AND logged_date = ?");
$uStmt->bind_param("is", $userId, $loggedDate);
$uStmt->execute();
$affected = $uStmt->affected_rows;
$uStmt->close();

if ($affected === 0) {
    // If no row exists, create one with 0
    $iStmt = $conn->prepare("INSERT INTO steps_logs (user_id, step_count, target_steps, distance_km, calories_kcal, active_minutes, logged_date) VALUES (?, 0, 8000, 0.00, 0, 0, ?)");
    $iStmt->bind_param("is", $userId, $loggedDate);
    $iStmt->execute();
    $iStmt->close();
}

respondJson(200, [
    "status" => "success",
    "message" => "Today's steps reset to 0 successfully",
    "data" => [
        "user_id" => $userId,
        "logged_date" => $loggedDate,
        "step_count" => 0,
        "distance_km" => 0.0,
        "calories_kcal" => 0,
        "active_minutes" => 0,
        "progress_percent" => 0
    ]
]);

