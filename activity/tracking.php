<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $type = trim($_GET["activity_type"] ?? $_GET["type"] ?? "");
    $limit = isset($_GET["limit"]) ? max(1, min(100, (int)$_GET["limit"])) : 30;

    $sessions = [];

    // Query workout_sessions first
    if ($type !== "") {
        $normType = ucfirst(strtolower($type));
        $ws = $conn->prepare(
            "SELECT id, session_uuid, activity_type, duration_seconds, target_type, target_value, distance_km, distance_meters, calories, steps, avg_pace, avg_heart_rate, tracking_source, start_time, end_time, created_at
             FROM workout_sessions
             WHERE user_id = ? AND activity_type = ?
             ORDER BY start_time DESC LIMIT ?"
        );
        if ($ws) {
            $ws->bind_param("isi", $userId, $normType, $limit);
            $ws->execute();
            $res = $ws->get_result();
            while ($r = $res->fetch_assoc()) {
                $durSec = (int)$r["duration_seconds"];
                $distKm = (float)$r["distance_km"] > 0 ? (float)$r["distance_km"] : round((float)$r["distance_meters"] / 1000.0, 2);
                $calculatedPace = $distKm > 0 ? gmdate("i:s", (int)($durSec / $distKm)) . " /km" : "--";
                $sessions[] = [
                    "id" => (int)$r["id"],
                    "session_uuid" => $r["session_uuid"],
                    "activity_type" => strtoupper($r["activity_type"]),
                    "duration_seconds" => $durSec,
                    "duration_formatted" => gmdate("H:i:s", $durSec),
                    "target_type" => $r["target_type"] ?? "time",
                    "target_value" => (int)($r["target_value"] ?? 45),
                    "distance_km" => $distKm,
                    "distance_meters" => (float)$r["distance_meters"] > 0 ? (float)$r["distance_meters"] : ($distKm * 1000.0),
                    "calories_burned" => (int)$r["calories"],
                    "avg_pace" => !empty($r["avg_pace"]) && $r["avg_pace"] !== "00:00 /km" ? $r["avg_pace"] : $calculatedPace,
                    "avg_heart_rate" => $r["avg_heart_rate"] !== null ? (int)$r["avg_heart_rate"] : null,
                    "steps" => (int)$r["steps"],
                    "tracking_source" => $r["tracking_source"] ?? "phone_sensor",
                    "start_time" => $r["start_time"],
                    "end_time" => $r["end_time"]
                ];
            }
            $ws->close();
        }
    } else {
        $ws = $conn->prepare(
            "SELECT id, session_uuid, activity_type, duration_seconds, target_type, target_value, distance_km, distance_meters, calories, steps, avg_pace, avg_heart_rate, tracking_source, start_time, end_time, created_at
             FROM workout_sessions
             WHERE user_id = ? AND activity_type IS NOT NULL
             ORDER BY start_time DESC LIMIT ?"
        );
        if ($ws) {
            $ws->bind_param("ii", $userId, $limit);
            $ws->execute();
            $res = $ws->get_result();
            while ($r = $res->fetch_assoc()) {
                $durSec = (int)$r["duration_seconds"];
                $distKm = (float)$r["distance_km"] > 0 ? (float)$r["distance_km"] : round((float)$r["distance_meters"] / 1000.0, 2);
                $calculatedPace = $distKm > 0 ? gmdate("i:s", (int)($durSec / $distKm)) . " /km" : "--";
                $sessions[] = [
                    "id" => (int)$r["id"],
                    "session_uuid" => $r["session_uuid"],
                    "activity_type" => strtoupper($r["activity_type"]),
                    "duration_seconds" => $durSec,
                    "duration_formatted" => gmdate("H:i:s", $durSec),
                    "target_type" => $r["target_type"] ?? "time",
                    "target_value" => (int)($r["target_value"] ?? 45),
                    "distance_km" => $distKm,
                    "distance_meters" => (float)$r["distance_meters"] > 0 ? (float)$r["distance_meters"] : ($distKm * 1000.0),
                    "calories_burned" => (int)$r["calories"],
                    "avg_pace" => !empty($r["avg_pace"]) && $r["avg_pace"] !== "00:00 /km" ? $r["avg_pace"] : $calculatedPace,
                    "avg_heart_rate" => $r["avg_heart_rate"] !== null ? (int)$r["avg_heart_rate"] : null,
                    "steps" => (int)$r["steps"],
                    "tracking_source" => $r["tracking_source"] ?? "phone_sensor",
                    "start_time" => $r["start_time"],
                    "end_time" => $r["end_time"]
                ];
            }
            $ws->close();
        }
    }

    // Fallback to activity_sessions if workout_sessions is empty
    if (empty($sessions)) {
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
    }
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

    $sessionUuid = !empty($data["session_uuid"]) ? trim((string)$data["session_uuid"]) : bin2hex(random_bytes(16));
    $normType = ucfirst(strtolower($type));
    if (!in_array($normType, ['Walking', 'Running', 'Cycling'])) {
        $normType = 'Walking';
    }
    $distanceMeters = isset($data["distance_meters"]) ? (float)$data["distance_meters"] : ($distance * 1000.0);
    $targetType = strtolower(trim((string)($data["target_type"] ?? "time")));
    if (!in_array($targetType, ["time", "distance"])) {
        $targetType = "time";
    }
    $targetValue = isset($data["target_value"]) ? (int)$data["target_value"] : 45;
    $trackingSource = trim((string)($data["tracking_source"] ?? "phone_sensor"));
    if ($trackingSource === "") $trackingSource = "phone_sensor";

    if ($normType === 'Cycling') {
        $steps = 0;
        if ($trackingSource === "phone_sensor") {
            $trackingSource = "gps";
        }
    }

    // Save to workout_sessions
    $wsStmt = $conn->prepare(
        "INSERT INTO workout_sessions (
            user_id, session_uuid, activity_type, start_time, end_time,
            duration_seconds, target_type, target_value, steps, distance_km,
            avg_pace, calories, tracking_source
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            duration_seconds = VALUES(duration_seconds),
            target_type = VALUES(target_type),
            target_value = VALUES(target_value),
            steps = VALUES(steps),
            distance_km = VALUES(distance_km),
            avg_pace = VALUES(avg_pace),
            calories = VALUES(calories),
            tracking_source = VALUES(tracking_source),
            end_time = VALUES(end_time)"
    );
    if ($wsStmt) {
        $wsStmt->bind_param("issssisidssis", 
            $userId, $sessionUuid, $normType, $startTime, $endTime,
            $duration, $targetType, $targetValue, $steps, $distance,
            $pace, $calories, $trackingSource
        );
        $wsStmt->execute();
        $wsStmt->close();
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
                "session_uuid" => $sessionUuid,
                "activity_type" => $type,
                "duration_seconds" => $duration,
                "duration_formatted" => gmdate("H:i:s", $duration),
                "distance_km" => $distance,
                "distance_meters" => $distanceMeters,
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

