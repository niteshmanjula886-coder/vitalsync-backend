<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $date = trim($_GET["date"] ?? date("Y-m-d"));

    // Fetch record for requested date or latest
    $stmt = $conn->prepare(
        "SELECT id, date_string, duration_minutes, sleep_score, quality_label, bedtime, wake_time, deep_sleep_minutes, light_sleep_minutes, rem_sleep_minutes, awake_minutes, created_at 
         FROM sleep_records 
         WHERE user_id = ? AND (date_string = ? OR date_string <= ?) 
         ORDER BY date_string DESC LIMIT 1"
    );
    $stmt->bind_param("iss", $userId, $date, $date);
    $stmt->execute();
    $rec = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $durationMin = $rec ? (int)$rec["duration_minutes"] : 452; // 7h 32m
    $score = $rec ? (int)$rec["sleep_score"] : 80;
    $quality = $rec ? $rec["quality_label"] : "Restful";
    $bedtime = $rec ? $rec["bedtime"] : "10:30 PM";
    $wakeTime = $rec ? $rec["wake_time"] : "06:15 AM";
    $deep = $rec ? (int)$rec["deep_sleep_minutes"] : 95;
    $light = $rec ? (int)$rec["light_sleep_minutes"] : 250;
    $rem = $rec ? (int)$rec["rem_sleep_minutes"] : 85;
    $awake = $rec ? (int)$rec["awake_minutes"] : 22;

    $hours = intdiv($durationMin, 60);
    $minutes = $durationMin % 60;

    // Fetch 7-day sleep history
    $hStmt = $conn->prepare(
        "SELECT date_string, duration_minutes, sleep_score, quality_label 
         FROM sleep_records 
         WHERE user_id = ? 
         ORDER BY date_string DESC LIMIT 7"
    );
    $hStmt->bind_param("i", $userId);
    $hStmt->execute();
    $hRes = $hStmt->get_result();
    $weekHistory = [];
    while ($r = $hRes->fetch_assoc()) {
        $weekHistory[] = [
            "date" => $r["date_string"],
            "duration_minutes" => (int)$r["duration_minutes"],
            "sleep_score" => (int)$r["sleep_score"],
            "quality" => $r["quality_label"]
        ];
    }
    $hStmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "sleep" => [
            "date" => $rec ? $rec["date_string"] : $date,
            "duration_minutes" => $durationMin,
            "duration_formatted" => "{$hours}h {$minutes}m",
            "sleep_score" => $score,
            "quality" => $quality,
            "bedtime" => $bedtime,
            "wake_time" => $wakeTime,
            "stages" => [
                "deep_sleep_minutes" => $deep,
                "deep_sleep_percent" => round(($deep / $durationMin) * 100, 1),
                "light_sleep_minutes" => $light,
                "light_sleep_percent" => round(($light / $durationMin) * 100, 1),
                "rem_sleep_minutes" => $rem,
                "rem_sleep_percent" => round(($rem / $durationMin) * 100, 1),
                "awake_minutes" => $awake
            ],
            "recent_history" => $weekHistory
        ]
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $dateString = !empty($data["date_string"]) ? trim((string)$data["date_string"]) : date("Y-m-d");
    $duration = isset($data["duration_minutes"]) ? (int)$data["duration_minutes"] : 0;
    $score = isset($data["sleep_score"]) ? (int)$data["sleep_score"] : 80;
    $quality = trim((string)($data["quality_label"] ?? "Restful"));
    $bedtime = trim((string)($data["bedtime"] ?? "10:30 PM"));
    $wakeTime = trim((string)($data["wake_time"] ?? "06:15 AM"));
    $deep = isset($data["deep_sleep_minutes"]) ? (int)$data["deep_sleep_minutes"] : (int)($duration * 0.22);
    $light = isset($data["light_sleep_minutes"]) ? (int)$data["light_sleep_minutes"] : (int)($duration * 0.55);
    $rem = isset($data["rem_sleep_minutes"]) ? (int)$data["rem_sleep_minutes"] : (int)($duration * 0.18);
    $awake = isset($data["awake_minutes"]) ? (int)$data["awake_minutes"] : (int)($duration * 0.05);

    if ($duration <= 60 || $duration > 1440) {
        respondJson(400, [
            "success" => false,
            "message" => "Please enter a realistic sleep duration between 60 and 1440 minutes"
        ]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO sleep_records (user_id, date_string, duration_minutes, sleep_score, quality_label, bedtime, wake_time, deep_sleep_minutes, light_sleep_minutes, rem_sleep_minutes, awake_minutes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            duration_minutes = VALUES(duration_minutes),
            sleep_score = VALUES(sleep_score),
            quality_label = VALUES(quality_label),
            bedtime = VALUES(bedtime),
            wake_time = VALUES(wake_time),
            deep_sleep_minutes = VALUES(deep_sleep_minutes),
            light_sleep_minutes = VALUES(light_sleep_minutes),
            rem_sleep_minutes = VALUES(rem_sleep_minutes),
            awake_minutes = VALUES(awake_minutes)"
    );

    $stmt->bind_param("isiisssiiii", $userId, $dateString, $duration, $score, $quality, $bedtime, $wakeTime, $deep, $light, $rem, $awake);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Sleep record logged successfully",
            "date" => $dateString,
            "duration_minutes" => $duration,
            "sleep_score" => $score
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to save sleep record: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

