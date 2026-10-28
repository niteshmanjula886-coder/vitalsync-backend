<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // Latest reading
    $latestStmt = $conn->prepare(
        "SELECT value_numeric, recorded_at, status_label 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'heart_rate' 
         ORDER BY recorded_at DESC LIMIT 1"
    );
    $latestStmt->bind_param("i", $userId);
    $latestStmt->execute();
    $latest = $latestStmt->get_result()->fetch_assoc();
    $latestStmt->close();

    $currentBpm = $latest ? (int)$latest["value_numeric"] : 76;
    $status = $latest ? $latest["status_label"] : "Normal";
    $recordedAt = $latest ? $latest["recorded_at"] : date("Y-m-d 09:00:00");

    // Min, Max, Avg today
    $today = date("Y-m-d");
    $statsStmt = $conn->prepare(
        "SELECT MIN(value_numeric) as min_bpm, MAX(value_numeric) as max_bpm, AVG(value_numeric) as avg_bpm 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) = ?"
    );
    $statsStmt->bind_param("is", $userId, $today);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();

    $minBpm = ($stats && $stats["min_bpm"] !== null) ? (int)$stats["min_bpm"] : 58;
    $maxBpm = ($stats && $stats["max_bpm"] !== null) ? (int)$stats["max_bpm"] : 124;
    $avgBpm = ($stats && $stats["avg_bpm"] !== null) ? (int)round($stats["avg_bpm"]) : 72;

    // Time-series points for intraday chart
    $chartStmt = $conn->prepare(
        "SELECT value_numeric, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'heart_rate' 
         ORDER BY recorded_at ASC LIMIT 24"
    );
    $chartStmt->bind_param("i", $userId);
    $chartStmt->execute();
    $cRes = $chartStmt->get_result();
    $points = [];
    while ($r = $cRes->fetch_assoc()) {
        $points[] = [
            "bpm" => (int)$r["value_numeric"],
            "time" => date("h:i A", strtotime($r["recorded_at"]))
        ];
    }
    $chartStmt->close();

    // Default curve points if empty
    if (empty($points)) {
        $points = [
            ["bpm" => 68, "time" => "06:00 AM"],
            ["bpm" => 74, "time" => "09:00 AM"],
            ["bpm" => 88, "time" => "12:00 PM"],
            ["bpm" => 76, "time" => "03:00 PM"],
            ["bpm" => 92, "time" => "06:00 PM"],
            ["bpm" => 71, "time" => "09:00 PM"]
        ];
    }

    $conn->close();

    respondJson(200, [
        "success" => true,
        "heart_rate" => [
            "current_bpm" => $currentBpm,
            "status" => $status,
            "resting_bpm" => 64,
            "min_bpm" => $minBpm,
            "max_bpm" => $maxBpm,
            "avg_bpm" => $avgBpm,
            "recorded_at" => $recordedAt,
            "chart_points" => $points
        ]
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $bpm = isset($data["bpm"]) ? (int)$data["bpm"] : (isset($data["value_numeric"]) ? (int)$data["value_numeric"] : 0);

    if ($bpm <= 30 || $bpm >= 250) {
        respondJson(400, [
            "success" => false,
            "message" => "Please provide a realistic heart rate between 30 and 250 BPM"
        ]);
    }

    $status = ($bpm < 60) ? "Low Rate" : (($bpm <= 90) ? "Normal" : "High Rate");
    $text = "$bpm BPM";
    $unit = "bpm";
    $source = trim((string)($data["source"] ?? "VitalSync Watch"));

    $stmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source)
         VALUES (?, 'heart_rate', ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("idssss", $userId, $bpm, $text, $unit, $status, $source);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    respondJson(201, [
        "success" => true,
        "message" => "Heart rate reading recorded successfully",
        "id" => $newId,
        "bpm" => $bpm,
        "status" => $status
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

