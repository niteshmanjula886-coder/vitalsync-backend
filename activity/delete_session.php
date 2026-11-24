<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

$sessionUuid = trim($_REQUEST["session_uuid"] ?? $_REQUEST["uuid"] ?? "");
$sessionId = isset($_REQUEST["id"]) ? (int)$_REQUEST["id"] : 0;

if ($sessionUuid === "" && $sessionId <= 0) {
    // Try php://input JSON body
    $rawInput = file_get_contents("php://input");
    if (!empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $sessionUuid = trim($json["session_uuid"] ?? $json["uuid"] ?? "");
            $sessionId = isset($json["id"]) ? (int)$json["id"] : 0;
        }
    }
}

if ($sessionUuid === "" && $sessionId <= 0) {
    respondJson(400, [
        "success" => false,
        "message" => "Missing session_uuid or id parameter for deletion"
    ]);
}

$deletedWorkout = 0;
$deletedActivity = 0;

if ($sessionUuid !== "") {
    $stmt1 = $conn->prepare("DELETE FROM workout_sessions WHERE user_id = ? AND session_uuid = ?");
    if ($stmt1) {
        $stmt1->bind_param("is", $userId, $sessionUuid);
        $stmt1->execute();
        $deletedWorkout += $stmt1->affected_rows;
        $stmt1->close();
    }
}

if ($sessionId > 0) {
    $stmt1 = $conn->prepare("DELETE FROM workout_sessions WHERE user_id = ? AND id = ?");
    if ($stmt1) {
        $stmt1->bind_param("ii", $userId, $sessionId);
        $stmt1->execute();
        $deletedWorkout += $stmt1->affected_rows;
        $stmt1->close();
    }
    $stmt2 = $conn->prepare("DELETE FROM activity_sessions WHERE user_id = ? AND id = ?");
    if ($stmt2) {
        $stmt2->bind_param("ii", $userId, $sessionId);
        $stmt2->execute();
        $deletedActivity += $stmt2->affected_rows;
        $stmt2->close();
    }
}

$conn->close();

respondJson(200, [
    "success" => true,
    "message" => "Activity session deleted successfully",
    "deleted_count" => $deletedWorkout + $deletedActivity
]);
