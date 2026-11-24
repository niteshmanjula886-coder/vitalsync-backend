<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

// 1. Delete from workout_sessions
$stmt1 = $conn->prepare("DELETE FROM workout_sessions WHERE user_id = ?");
$deletedWorkout = 0;
if ($stmt1) {
    $stmt1->bind_param("i", $userId);
    $stmt1->execute();
    $deletedWorkout = $stmt1->affected_rows;
    $stmt1->close();
}

// 2. Delete from activity_sessions
$stmt2 = $conn->prepare("DELETE FROM activity_sessions WHERE user_id = ?");
$deletedActivity = 0;
if ($stmt2) {
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $deletedActivity = $stmt2->affected_rows;
    $stmt2->close();
}

$conn->close();

respondJson(200, [
    "success" => true,
    "message" => "Activity session history removed successfully",
    "deleted_count" => $deletedWorkout + $deletedActivity
]);
