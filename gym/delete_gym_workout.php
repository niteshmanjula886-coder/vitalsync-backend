<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$input = getInputData();

$workoutId = (int)($input["id"] ?? $input["workout_id"] ?? $_GET["id"] ?? $_GET["workout_id"] ?? 0);

if ($workoutId <= 0) {
    respondJson(400, [
        "success" => false,
        "message" => "Valid workout ID required to delete."
    ]);
}

// 1. Delete associated sets
$stmtSets = $conn->prepare("DELETE FROM gym_workout_sets WHERE workout_id = ?");
if ($stmtSets) {
    $stmtSets->bind_param("i", $workoutId);
    $stmtSets->execute();
    $stmtSets->close();
}

// 2. Delete main workout row
$stmt = $conn->prepare("DELETE FROM gym_workouts WHERE id = ? AND user_id = ?");
if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
}

$stmt->bind_param("ii", $workoutId, $userId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    respondJson(200, [
        "success" => true,
        "message" => "Workout session deleted successfully"
    ]);
} else {
    respondJson(404, [
        "success" => false,
        "message" => "Workout record not found or already deleted."
    ]);
}
