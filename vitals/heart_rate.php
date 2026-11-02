<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // 1. Latest reading from vitals_logs or vitals_readings
    $latestStmt = $conn->prepare(
        "SELECT heart_rate as value_numeric, logged_at as recorded_at 
         FROM vitals_logs 
         WHERE user_id = ? AND heart_rate > 0 
         ORDER BY logged_at DESC LIMIT 1"
    );
    $latestStmt->bind_param("i", $userId);
    $latestStmt->execute();
    $latest = $latestStmt->get_result()->fetch_assoc();
    $latestStmt->close();

    if (!$latest) {
        // Fallback to vitals_readings
        $latestStmt2 = $conn->prepare(
            "SELECT value_numeric, recorded_at, status_label 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = 'heart_rate' 
             ORDER BY recorded_at DESC LIMIT 1"
        );
        $latestStmt2->bind_param("i", $userId);
        $latestStmt2->execute();
        $latest = $latestStmt2->get_result()->fetch_assoc();
        $latestStmt2->close();
    }

    $currentBpm = $latest ? (int)$latest["value_numeric"] : 0;
    $status = $latest ? (($currentBpm < 60) ? "Low Rate" : (($currentBpm <= 100) ? "Normal" : "Elevated")) : "No readings yet";
    $recordedAt = $latest ? $latest["recorded_at"] : "";

    // 2. Min, Max, Avg today from vitals_logs
    $today = date("Y-m-d");
    $statsStmt = $conn->prepare(
        "SELECT MIN(heart_rate) as min_bpm, MAX(heart_rate) as max_bpm, AVG(heart_rate) as avg_bpm 
         FROM vitals_logs 
         WHERE user_id = ? AND heart_rate > 0 AND DATE(logged_at) = ?"
    );
    $statsStmt->bind_param("is", $userId, $today);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();

    if (!$stats || $stats["min_bpm"] === null) {
        // Fallback to vitals_readings
        $statsStmt2 = $conn->prepare(
            "SELECT MIN(value_numeric) as min_bpm, MAX(value_numeric) as max_bpm, AVG(value_numeric) as avg_bpm 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) = ?"
        );
        $statsStmt2->bind_param("is", $userId, $today);
        $statsStmt2->execute();
        $stats = $statsStmt2->get_result()->fetch_assoc();
        $statsStmt2->close();
    }

    $minBpm = ($stats && $stats["min_bpm"] !== null) ? (int)$stats["min_bpm"] : ($latest ? $currentBpm : 0);
    $maxBpm = ($stats && $stats["max_bpm"] !== null) ? (int)$stats["max_bpm"] : ($latest ? $currentBpm : 0);
    $avgBpm = ($stats && $stats["avg_bpm"] !== null) ? (int)round($stats["avg_bpm"]) : ($latest ? $currentBpm : 0);

    // 3. Resting heart rate estimation (bottom baseline cluster or min)
    $restingBpm = 0;
    if ($minBpm > 0) {
        $restStmt = $conn->prepare(
            "SELECT AVG(heart_rate) as rest_bpm FROM (
                SELECT heart_rate FROM vitals_logs 
                WHERE user_id = ? AND heart_rate >= 40 AND DATE(logged_at) = ? 
                ORDER BY heart_rate ASC LIMIT 5
            ) as t"
        );
        $restStmt->bind_param("is", $userId, $today);
        $restStmt->execute();
        $restRow = $restStmt->get_result()->fetch_assoc();
        $restStmt->close();

        if ($restRow && $restRow["rest_bpm"] !== null) {
            $restingBpm = (int)round($restRow["rest_bpm"]);
        } else {
            $restingBpm = max(45, (int)round($minBpm * 1.02));
        }
    }

    // 4. Time-series chart points
    $chartStmt = $conn->prepare(
        "SELECT heart_rate as value_numeric, logged_at as recorded_at 
         FROM vitals_logs 
         WHERE user_id = ? AND heart_rate > 0 
         ORDER BY logged_at ASC LIMIT 48"
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

    if (empty($points)) {
        // Fallback to vitals_readings
        $chartStmt2 = $conn->prepare(
            "SELECT value_numeric, recorded_at 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = 'heart_rate' 
             ORDER BY recorded_at ASC LIMIT 24"
        );
        $chartStmt2->bind_param("i", $userId);
        $chartStmt2->execute();
        $cRes2 = $chartStmt2->get_result();
        while ($r2 = $cRes2->fetch_assoc()) {
            $points[] = [
                "bpm" => (int)$r2["value_numeric"],
                "time" => date("h:i A", strtotime($r2["recorded_at"]))
            ];
        }
        $chartStmt2->close();
    }

    $conn->close();

    respondJson(200, [
        "success" => true,
        "heart_rate" => [
            "current_bpm" => $currentBpm,
            "status" => $status,
            "resting_bpm" => $restingBpm,
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

    $status = ($bpm < 60) ? "Low Rate" : (($bpm <= 100) ? "Normal" : "High Rate");
    $text = "$bpm BPM";
    $unit = "bpm";
    $source = trim((string)($data["source"] ?? "VitalSync Watch"));
    $loggedAt = !empty($data["logged_at"]) ? trim((string)$data["logged_at"]) : date("Y-m-d H:i:s");

    // 1. Insert into vitals_logs
    $stmt1 = $conn->prepare("INSERT INTO vitals_logs (user_id, heart_rate, source, logged_at) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("iiss", $userId, $bpm, $source, $loggedAt);
    $stmt1->execute();
    $logId = $stmt1->insert_id;
    $stmt1->close();

    // 2. Insert into vitals_readings
    $stmt2 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at)
         VALUES (?, 'heart_rate', ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("idsssss", $userId, $bpm, $text, $unit, $status, $source, $loggedAt);
    $stmt2->execute();
    $stmt2->close();

    $conn->close();

    respondJson(201, [
        "success" => true,
        "message" => "Heart rate reading recorded successfully",
        "id" => $logId,
        "bpm" => $bpm,
        "status" => $status
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
