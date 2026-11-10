<?php

require_once __DIR__ . "/../config/auth_middleware.php";

function evaluateSleepQualityMeta(int $durationMinutes): array {
    if ($durationMinutes <= 0) {
        return ["No record", 0, "none"];
    }
    $hrs = $durationMinutes / 60.0;
    if ($hrs < 6.0) {
        $score = max(40, min(55, (int)round(40 + ($hrs / 6.0) * 15)));
        return ["Poor", $score, "critical"];
    }
    if ($hrs < 7.0) {
        $score = max(60, min(74, (int)round(60 + ($hrs - 6.0) * 14)));
        return ["Fair", $score, "warning"];
    }
    if ($hrs <= 8.5) {
        $score = max(75, min(90, (int)round(75 + (($hrs - 7.0) / 1.5) * 15)));
        return ["Optimal", $score, "optimal"];
    }
    $score = max(90, min(100, (int)round(90 + min(1.0, ($hrs - 8.5) / 1.5) * 10)));
    return ["Excellent", $score, "optimal"];
}

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

    $durationMin = $rec ? (int)$rec["duration_minutes"] : 0;
    $score = $rec ? (int)$rec["sleep_score"] : 0;
    $recordDate = $rec ? $rec["date_string"] : $date;
    $bedtime = $rec ? $rec["bedtime"] : "";
    $wakeTime = $rec ? $rec["wake_time"] : "";
    $deep = $rec ? (int)$rec["deep_sleep_minutes"] : 0;
    $light = $rec ? (int)$rec["light_sleep_minutes"] : 0;
    $rem = $rec ? (int)$rec["rem_sleep_minutes"] : 0;
    $awake = $rec ? (int)$rec["awake_minutes"] : 0;

    // Fallback to sleep_logs if sleep_records empty
    if ($durationMin <= 0) {
        $slStmt = $conn->prepare("SELECT id, start_time, end_time, duration_minutes, quality_score, bedtime, wake_time, COALESCE(logged_at, logged_date, DATE(start_time)) AS log_date FROM sleep_logs WHERE user_id = ? AND (logged_at = ? OR logged_at <= ? OR logged_date = ? OR logged_date <= ?) ORDER BY COALESCE(logged_at, logged_date, DATE(start_time)) DESC LIMIT 1");
        if ($slStmt) {
            $slStmt->bind_param("issss", $userId, $date, $date, $date, $date);
            $slStmt->execute();
            $slRec = $slStmt->get_result()->fetch_assoc();
            $slStmt->close();
            if ($slRec && (int)$slRec["duration_minutes"] > 0) {
                $durationMin = (int)$slRec["duration_minutes"];
                $score = $slRec["quality_score"] !== null ? (int)$slRec["quality_score"] : 0;
                $recordDate = !empty($slRec["log_date"]) ? $slRec["log_date"] : $date;
                $bedtime = !empty($slRec["bedtime"]) ? $slRec["bedtime"] : date("h:i A", strtotime($slRec["start_time"]));
                $wakeTime = !empty($slRec["wake_time"]) ? $slRec["wake_time"] : date("h:i A", strtotime($slRec["end_time"]));
                $deep = (int)($durationMin * 0.22);
                $light = (int)($durationMin * 0.55);
                $rem = (int)($durationMin * 0.18);
                $awake = (int)($durationMin * 0.05);
            }
        }
    }

    [$quality, $derivedScore] = evaluateSleepQualityMeta($durationMin);
    if ($durationMin > 0 && $score <= 0) {
        $score = $derivedScore;
    }

    $hours = intdiv($durationMin, 60);
    $minutes = $durationMin % 60;

    // Fetch 7-day sleep history (sleep_records primary, fallback/merge from sleep_logs)
    $historyByDate = [];
    $hStmt = $conn->prepare(
        "SELECT date_string, duration_minutes, sleep_score 
         FROM sleep_records 
         WHERE user_id = ? 
         ORDER BY date_string DESC LIMIT 14"
    );
    if ($hStmt) {
        $hStmt->bind_param("i", $userId);
        $hStmt->execute();
        $hRes = $hStmt->get_result();
        while ($r = $hRes->fetch_assoc()) {
            $d = $r["date_string"];
            $dur = (int)$r["duration_minutes"];
            if ($dur <= 0 || isset($historyByDate[$d])) continue;
            [$rQuality, $rDerivedScore] = evaluateSleepQualityMeta($dur);
            $rScore = (int)$r["sleep_score"] > 0 ? (int)$r["sleep_score"] : $rDerivedScore;
            $historyByDate[$d] = [
                "date" => $d,
                "duration_minutes" => $dur,
                "sleep_score" => $rScore,
                "quality" => $rQuality
            ];
        }
        $hStmt->close();
    }

    $slHistStmt = $conn->prepare(
        "SELECT COALESCE(logged_at, logged_date, DATE(start_time)) AS log_date, duration_minutes, quality_score
         FROM sleep_logs
         WHERE user_id = ? AND duration_minutes > 0
         ORDER BY COALESCE(logged_at, logged_date, DATE(start_time)) DESC LIMIT 14"
    );
    if ($slHistStmt) {
        $slHistStmt->bind_param("i", $userId);
        $slHistStmt->execute();
        $slHistRes = $slHistStmt->get_result();
        while ($r = $slHistRes->fetch_assoc()) {
            $d = $r["log_date"];
            $dur = (int)$r["duration_minutes"];
            if (empty($d) || $dur <= 0 || isset($historyByDate[$d])) continue;
            [$rQuality, $rDerivedScore] = evaluateSleepQualityMeta($dur);
            $rScore = ($r["quality_score"] !== null && (int)$r["quality_score"] > 0) ? (int)$r["quality_score"] : $rDerivedScore;
            $historyByDate[$d] = [
                "date" => $d,
                "duration_minutes" => $dur,
                "sleep_score" => $rScore,
                "quality" => $rQuality
            ];
        }
        $slHistStmt->close();
    }

    krsort($historyByDate);
    $weekHistory = array_slice(array_values($historyByDate), 0, 7);
    $conn->close();

    respondJson(200, [
        "success" => true,
        "sleep" => [
            "date" => $recordDate,
            "duration_minutes" => $durationMin,
            "duration_formatted" => "{$hours}h {$minutes}m",
            "sleep_score" => $score,
            "quality" => $quality,
            "bedtime" => $bedtime,
            "wake_time" => $wakeTime,
            "stages" => [
                "deep_sleep_minutes" => $deep,
                "deep_sleep_percent" => $durationMin > 0 ? round(($deep / $durationMin) * 100, 1) : 0,
                "light_sleep_minutes" => $light,
                "light_sleep_percent" => $durationMin > 0 ? round(($light / $durationMin) * 100, 1) : 0,
                "rem_sleep_minutes" => $rem,
                "rem_sleep_percent" => $durationMin > 0 ? round(($rem / $durationMin) * 100, 1) : 0,
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
    $bedtime = trim((string)($data["bedtime"] ?? "10:30 PM"));
    $wakeTime = trim((string)($data["wake_time"] ?? "06:30 AM"));

    if ($duration <= 0 && !empty($bedtime) && !empty($wakeTime)) {
        $bt = strtotime($bedtime);
        $wt = strtotime($wakeTime);
        if ($bt !== false && $wt !== false) {
            if ($wt <= $bt) {
                $wt += 86400;
            }
            $duration = (int)round(($wt - $bt) / 60);
        }
    }

    if ($duration <= 0 || $duration > 1440) {
        respondJson(400, [
            "success" => false,
            "message" => "Please enter a realistic sleep duration between 1 and 1440 minutes"
        ]);
    }

    [$derivedQuality, $derivedScore, $statusColor] = evaluateSleepQualityMeta($duration);
    $score = (isset($data["sleep_score"]) && (int)$data["sleep_score"] > 0) ? (int)$data["sleep_score"] : $derivedScore;
    $quality = $derivedQuality;

    $deep = isset($data["deep_sleep_minutes"]) ? (int)$data["deep_sleep_minutes"] : (int)round($duration * 0.22);
    $light = isset($data["light_sleep_minutes"]) ? (int)$data["light_sleep_minutes"] : (int)round($duration * 0.55);
    $rem = isset($data["rem_sleep_minutes"]) ? (int)$data["rem_sleep_minutes"] : (int)round($duration * 0.18);
    $awake = isset($data["awake_minutes"]) ? (int)$data["awake_minutes"] : (int)round($duration * 0.05);

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

        // Also sync into sleep_logs for master schema persistence
        $startTime = date("Y-m-d H:i:s", strtotime("$dateString $bedtime"));
        $endTime = date("Y-m-d H:i:s", strtotime("$dateString $wakeTime"));
        if (strtotime($endTime) <= strtotime($startTime)) {
            $endTime = date("Y-m-d H:i:s", strtotime($startTime) + ($duration * 60));
        }

        $logCheck = $conn->prepare("SELECT id FROM sleep_logs WHERE user_id = ? AND (logged_at = ? OR logged_date = ?) LIMIT 1");
        if ($logCheck) {
            $logCheck->bind_param("iss", $userId, $dateString, $dateString);
            $logCheck->execute();
            $existLog = $logCheck->get_result()->fetch_assoc();
            $logCheck->close();

            if ($existLog) {
                $uLog = $conn->prepare("UPDATE sleep_logs SET start_time = ?, end_time = ?, duration_minutes = ?, quality_score = ?, logged_at = ?, bedtime = ?, wake_time = ?, status_color = ?, logged_date = ? WHERE id = ?");
                $uLog->bind_param("ssiisssssi", $startTime, $endTime, $duration, $score, $dateString, $bedtime, $wakeTime, $statusColor, $dateString, $existLog["id"]);
                $uLog->execute();
                $uLog->close();
            } else {
                $iLog = $conn->prepare("INSERT INTO sleep_logs (user_id, start_time, end_time, duration_minutes, quality_score, logged_at, bedtime, wake_time, status_color, logged_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $iLog->bind_param("isssiissss", $userId, $startTime, $endTime, $duration, $score, $dateString, $bedtime, $wakeTime, $statusColor, $dateString);
                $iLog->execute();
                $iLog->close();
            }
        }

        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Sleep record logged successfully",
            "date" => $dateString,
            "duration_minutes" => $duration,
            "sleep_score" => $score,
            "quality" => $quality,
            "quality_label" => $quality
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to save sleep record: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

