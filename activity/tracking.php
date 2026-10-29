<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $type = trim($_GET["activity_type"] ?? $_GET["type"] ?? "");
    $limit = isset($_GET["limit"]) ? max(1, min(100, (int)$_GET["limit"])) : 30;

    if ($type !== "") {
        $stmt = $conn->prepare(
            "SELECT id, activity_type, duration_seconds, distance_km, calories_burned, avg_pace, avg_heart_rate, steps, start_time, end_time, created_at 
             FROM activity_sessions 
             WHERE user_id = ? AND activity_type = ? 
             ORDER BY start_time DESC LIMIT ?"
        );
        $stmt->bind_param("isi", $userId, $type, $limit);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, activity_type, duration_seconds, distance_km, calories_burned, avg_pace, avg_heart_rate, steps, start_time, end_time, created_at 
             FROM activity_sessions 
             WHERE user_id = ? 
             ORDER BY start_time DESC LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $sessions = [];
    while ($r = $result->fetch_assoc()) {
        $durSec = (int)$r["duration_seconds"];
        $sessions[] = [
            "id" => (int)$r["id"],
            "activity_type" => $r["activity_type"],
            "duration_seconds" => $durSec,
            "duration_formatted" => gmdate("H:i:s", $durSec),
            "distance_km" => (float)$r["distance_km"],
            "calories_burned" => (int)$r["calories_burned"],
            "avg_pace" => $r["avg_pace"],
            "avg_heart_rate" => $r["avg_heart_rate"] !== null ? (int)$r["avg_heart_rate"] : null,
            "steps" => (int)$r["steps"],
            "start_time" => $r["start_time"],
            "end_time" => $r["end_time"]
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($sessions),
        "sessions" => $sessions
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $type = strtoupper(trim((string)($data["activity_type"] ?? $data["type"] ?? "WALKING")));
    $validTypes = ["WALKING", "RUNNING", "CYCLING", "WORKOUT"];
    if (!in_array($type, $validTypes)) {
        respondJson(400, [
            "success" => false,
            "message" => "Invalid activity_type. Allowed: WALKING, RUNNING, CYCLING, WORKOUT"
        ]);
    }

    $duration = isset($data["duration_seconds"]) ? (int)$data["duration_seconds"] : (isset($data["duration_minutes"]) ? (int)$data["duration_minutes"] * 60 : 0);
    $distance = isset($data["distance_km"]) ? (float)$data["distance_km"] : 0.00;
    $calories = isset($data["calories_burned"]) ? (int)$data["calories_burned"] : (int)($duration * 0.12);
    $pace = trim((string)($data["avg_pace"] ?? ""));
    $avgHr = isset($data["avg_heart_rate"]) && is_numeric($data["avg_heart_rate"]) ? (int)$data["avg_heart_rate"] : null;
    $steps = isset($data["steps"]) ? (int)$data["steps"] : 0;
    $startTime = !empty($data["start_time"]) ? trim((string)$data["start_time"]) : date("Y-m-d H:i:s");
    $endTime = !empty($data["end_time"]) ? trim((string)$data["end_time"]) : date("Y-m-d H:i:s", strtotime($startTime) + $duration);

    if ($duration <= 0) {
        respondJson(400, [
            "success" => false,
            "message" => "duration_seconds must be greater than 0"
        ]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO activity_sessions (user_id, activity_type, duration_seconds, distance_km, calories_burned, avg_pace, avg_heart_rate, steps, start_time, end_time)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isidisiiss", $userId, $type, $duration, $distance, $calories, $pace, $avgHr, $steps, $startTime, $endTime);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Activity session recorded successfully",
            "session" => [
                "id" => $newId,
                "activity_type" => $type,
                "duration_seconds" => $duration,
                "duration_formatted" => gmdate("H:i:s", $duration),
                "distance_km" => $distance,
                "calories_burned" => $calories,
                "avg_pace" => $pace,
                "avg_heart_rate" => $avgHr,
                "steps" => $steps,
                "start_time" => $startTime
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to record activity session: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

