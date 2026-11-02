<?php

require_once __DIR__ . "/../../config/db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
if ($method !== "GET") {
    sendResponse("error", "Method not allowed. Use GET.", null, 405);
}

$input = $_GET;
$userId = resolveUserId($pdo, $input);

if ($userId <= 0) {
    sendResponse("error", "Valid user_id or registered email is required", null, 400);
}

$timeframe = strtolower(trim((string)($_GET["timeframe"] ?? $_GET["period"] ?? "today")));
$today = date("Y-m-d");

switch ($timeframe) {
    case "24h":
        $start = date("Y-m-d H:i:s", strtotime("-24 hours"));
        $end = date("Y-m-d H:i:s");
        $prevStart = date("Y-m-d H:i:s", strtotime("-48 hours"));
        $prevEnd = $start;
        break;
    case "7d":
    case "weekly":
        $start = date("Y-m-d 00:00:00", strtotime("-6 days"));
        $end = date("Y-m-d 23:59:59");
        $prevStart = date("Y-m-d 00:00:00", strtotime("-13 days"));
        $prevEnd = date("Y-m-d 23:59:59", strtotime("-7 days"));
        break;
    case "30d":
    case "monthly":
        $start = date("Y-m-d 00:00:00", strtotime("-29 days"));
        $end = date("Y-m-d 23:59:59");
        $prevStart = date("Y-m-d 00:00:00", strtotime("-59 days"));
        $prevEnd = date("Y-m-d 23:59:59", strtotime("-30 days"));
        break;
    case "today":
    default:
        $start = date("Y-m-d 00:00:00");
        $end = date("Y-m-d 23:59:59");
        $prevStart = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $prevEnd = date("Y-m-d 23:59:59", strtotime("-1 day"));
        break;
}

function calculateDelta($curr, $prev) {
    if ($curr === null && $prev === null) {
        return ["delta_percent" => "0.0%", "direction" => "STABLE"];
    }
    if ($prev === null || $prev <= 0) {
        return ["delta_percent" => ($curr > 0 ? "+100%" : "0.0%"), "direction" => ($curr > 0 ? "UP" : "STABLE")];
    }
    if ($curr === null || $curr <= 0) {
        return ["delta_percent" => "-100%", "direction" => "DOWN"];
    }
    $diff = $curr - $prev;
    $pct = round(($diff / $prev) * 100, 1);
    $sign = $pct > 0 ? "+" : "";
    $dir = abs($pct) < 1.0 ? "STABLE" : ($pct > 0 ? "UP" : "DOWN");
    return [
        "delta_percent" => "{$sign}{$pct}%",
        "direction" => $dir
    ];
}

try {
    // 1. HEART RATE AGGREGATES
    $hrQuery = "SELECT 
                    COUNT(*) as total_readings,
                    MIN(heart_rate) as min_bpm,
                    MAX(heart_rate) as max_bpm,
                    AVG(heart_rate) as avg_bpm
                FROM vitals_logs 
                WHERE user_id = :uid AND logged_at BETWEEN :start AND :end AND heart_rate > 0";
    $hrStmt = $pdo->prepare($hrQuery);
    $hrStmt->execute([":uid" => $userId, ":start" => $start, ":end" => $end]);
    $hrStats = $hrStmt->fetch();

    $hrPrevStmt = $pdo->prepare("SELECT AVG(heart_rate) as avg_bpm FROM vitals_logs WHERE user_id = :uid AND logged_at BETWEEN :pstart AND :pend AND heart_rate > 0");
    $hrPrevStmt->execute([":uid" => $userId, ":pstart" => $prevStart, ":pend" => $prevEnd]);
    $hrPrev = $hrPrevStmt->fetch();

    // Resting Heart Rate Estimation
    $restingHr = null;
    if ($hrStats && (int)$hrStats["total_readings"] > 0) {
        $restStmt = $pdo->prepare(
            "SELECT AVG(heart_rate) as rest_bpm FROM (
                SELECT heart_rate FROM vitals_logs 
                WHERE user_id = :uid AND logged_at BETWEEN :start AND :end AND heart_rate >= 40 
                ORDER BY heart_rate ASC LIMIT 5
            ) as t"
        );
        $restStmt->execute([":uid" => $userId, ":start" => $start, ":end" => $end]);
        $restRow = $restStmt->fetch();
        if ($restRow && $restRow["rest_bpm"] !== null) {
            $restingHr = (int)round((float)$restRow["rest_bpm"]);
        } else {
            $restingHr = (int)$hrStats["min_bpm"];
        }
    }

    // Latest HR
    $hrLatestStmt = $pdo->prepare("SELECT heart_rate, logged_at FROM vitals_logs WHERE user_id = :uid AND heart_rate > 0 ORDER BY logged_at DESC LIMIT 1");
    $hrLatestStmt->execute([":uid" => $userId]);
    $hrLatest = $hrLatestStmt->fetch();

    $currHrAvg = ($hrStats && $hrStats["avg_bpm"] !== null) ? round((float)$hrStats["avg_bpm"], 1) : null;
    $prevHrAvg = ($hrPrev && $hrPrev["avg_bpm"] !== null) ? round((float)$hrPrev["avg_bpm"], 1) : null;
    $hrDelta = calculateDelta($currHrAvg, $prevHrAvg);

    // 2. SPO2 AGGREGATES
    $spo2Query = "SELECT 
                    COUNT(*) as total_readings,
                    MIN(spo2) as min_spo2,
                    MAX(spo2) as max_spo2,
                    AVG(spo2) as avg_spo2
                  FROM vitals_logs 
                  WHERE user_id = :uid AND logged_at BETWEEN :start AND :end AND spo2 > 0";
    $spo2Stmt = $pdo->prepare($spo2Query);
    $spo2Stmt->execute([":uid" => $userId, ":start" => $start, ":end" => $end]);
    $spo2Stats = $spo2Stmt->fetch();

    $spo2PrevStmt = $pdo->prepare("SELECT AVG(spo2) as avg_spo2 FROM vitals_logs WHERE user_id = :uid AND logged_at BETWEEN :pstart AND :pend AND spo2 > 0");
    $spo2PrevStmt->execute([":uid" => $userId, ":pstart" => $prevStart, ":pend" => $prevEnd]);
    $spo2Prev = $spo2PrevStmt->fetch();

    $spo2LatestStmt = $pdo->prepare("SELECT spo2, logged_at FROM vitals_logs WHERE user_id = :uid AND spo2 > 0 ORDER BY logged_at DESC LIMIT 1");
    $spo2LatestStmt->execute([":uid" => $userId]);
    $spo2Latest = $spo2LatestStmt->fetch();

    $currSpo2Avg = ($spo2Stats && $spo2Stats["avg_spo2"] !== null) ? round((float)$spo2Stats["avg_spo2"], 1) : null;
    $prevSpo2Avg = ($spo2Prev && $spo2Prev["avg_spo2"] !== null) ? round((float)$spo2Prev["avg_spo2"], 1) : null;
    $spo2Delta = calculateDelta($currSpo2Avg, $prevSpo2Avg);

    // 3. BLOOD PRESSURE AGGREGATES
    $bpQuery = "SELECT 
                    COUNT(*) as total_readings,
                    MIN(systolic_bp) as min_sys,
                    MAX(systolic_bp) as max_sys,
                    AVG(systolic_bp) as avg_sys,
                    MIN(diastolic_bp) as min_dia,
                    MAX(diastolic_bp) as max_dia,
                    AVG(diastolic_bp) as avg_dia
                FROM vitals_logs 
                WHERE user_id = :uid AND logged_at BETWEEN :start AND :end AND systolic_bp > 0";
    $bpStmt = $pdo->prepare($bpQuery);
    $bpStmt->execute([":uid" => $userId, ":start" => $start, ":end" => $end]);
    $bpStats = $bpStmt->fetch();

    $bpPrevStmt = $pdo->prepare("SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia FROM vitals_logs WHERE user_id = :uid AND logged_at BETWEEN :pstart AND :pend AND systolic_bp > 0");
    $bpPrevStmt->execute([":uid" => $userId, ":pstart" => $prevStart, ":pend" => $prevEnd]);
    $bpPrev = $bpPrevStmt->fetch();

    $bpLatestStmt = $pdo->prepare("SELECT systolic_bp, diastolic_bp, logged_at FROM vitals_logs WHERE user_id = :uid AND systolic_bp > 0 ORDER BY logged_at DESC LIMIT 1");
    $bpLatestStmt->execute([":uid" => $userId]);
    $bpLatest = $bpLatestStmt->fetch();

    $currSysAvg = ($bpStats && $bpStats["avg_sys"] !== null) ? round((float)$bpStats["avg_sys"], 1) : null;
    $prevSysAvg = ($bpPrev && $bpPrev["avg_sys"] !== null) ? round((float)$bpPrev["avg_sys"], 1) : null;
    $currDiaAvg = ($bpStats && $bpStats["avg_dia"] !== null) ? round((float)$bpStats["avg_dia"], 1) : null;
    $prevDiaAvg = ($bpPrev && $bpPrev["avg_dia"] !== null) ? round((float)$bpPrev["avg_dia"], 1) : null;
    $sysDelta = calculateDelta($currSysAvg, $prevSysAvg);
    $diaDelta = calculateDelta($currDiaAvg, $prevDiaAvg);

    // 4. TIME-SERIES POINTS
    $chartStmt = $pdo->prepare(
        "SELECT heart_rate, spo2, systolic_bp, diastolic_bp, logged_at 
         FROM vitals_logs 
         WHERE user_id = :uid AND logged_at BETWEEN :start AND :end 
         ORDER BY logged_at ASC LIMIT 60"
    );
    $chartStmt->execute([":uid" => $userId, ":start" => $start, ":end" => $end]);
    $chartRows = $chartStmt->fetchAll();

    $points = [];
    foreach ($chartRows as $cr) {
        $points[] = [
            "time" => date("h:i A", strtotime($cr["logged_at"])),
            "logged_at" => $cr["logged_at"],
            "heart_rate" => $cr["heart_rate"] !== null ? (int)$cr["heart_rate"] : null,
            "spo2" => $cr["spo2"] !== null ? (int)$cr["spo2"] : null,
            "systolic_bp" => $cr["systolic_bp"] !== null ? (int)$cr["systolic_bp"] : null,
            "diastolic_bp" => $cr["diastolic_bp"] !== null ? (int)$cr["diastolic_bp"] : null
        ];
    }

    sendResponse("success", "Aggregated vitals statistics computed successfully", [
        "user_id" => $userId,
        "timeframe" => $timeframe,
        "period_range" => [
            "start" => $start,
            "end" => $end
        ],
        "heart_rate" => [
            "current_bpm" => $hrLatest ? (int)$hrLatest["heart_rate"] : null,
            "average_bpm" => $currHrAvg,
            "highest_bpm" => ($hrStats && $hrStats["max_bpm"] !== null) ? (int)$hrStats["max_bpm"] : null,
            "lowest_bpm" => ($hrStats && $hrStats["min_bpm"] !== null) ? (int)$hrStats["min_bpm"] : null,
            "resting_estimate_bpm" => $restingHr,
            "total_readings" => ($hrStats ? (int)$hrStats["total_readings"] : 0),
            "trend" => [
                "previous_avg" => $prevHrAvg,
                "delta_percent" => $hrDelta["delta_percent"],
                "direction" => $hrDelta["direction"],
                "status" => ($currHrAvg !== null && $currHrAvg >= 60 && $currHrAvg <= 100) ? "Optimal" : "Review Needed"
            ]
        ],
        "spo2" => [
            "current_percentage" => $spo2Latest ? (int)$spo2Latest["spo2"] : null,
            "average_percentage" => $currSpo2Avg,
            "highest_percentage" => ($spo2Stats && $spo2Stats["max_spo2"] !== null) ? (int)$spo2Stats["max_spo2"] : null,
            "lowest_percentage" => ($spo2Stats && $spo2Stats["min_spo2"] !== null) ? (int)$spo2Stats["min_spo2"] : null,
            "total_readings" => ($spo2Stats ? (int)$spo2Stats["total_readings"] : 0),
            "trend" => [
                "previous_avg" => $prevSpo2Avg,
                "delta_percent" => $spo2Delta["delta_percent"],
                "direction" => $spo2Delta["direction"],
                "status" => ($currSpo2Avg !== null && $currSpo2Avg >= 95) ? "Optimal" : "Attention Needed"
            ]
        ],
        "blood_pressure" => [
            "current" => $bpLatest ? [
                "systolic" => (int)$bpLatest["systolic_bp"],
                "diastolic" => (int)$bpLatest["diastolic_bp"],
                "formatted" => $bpLatest["systolic_bp"] . "/" . $bpLatest["diastolic_bp"] . " mmHg"
            ] : null,
            "average_systolic" => $currSysAvg,
            "average_diastolic" => $currDiaAvg,
            "highest_systolic" => ($bpStats && $bpStats["max_sys"] !== null) ? (int)$bpStats["max_sys"] : null,
            "lowest_systolic" => ($bpStats && $bpStats["min_sys"] !== null) ? (int)$bpStats["min_sys"] : null,
            "highest_diastolic" => ($bpStats && $bpStats["max_dia"] !== null) ? (int)$bpStats["max_dia"] : null,
            "lowest_diastolic" => ($bpStats && $bpStats["min_dia"] !== null) ? (int)$bpStats["min_dia"] : null,
            "total_readings" => ($bpStats ? (int)$bpStats["total_readings"] : 0),
            "trend" => [
                "systolic_delta" => $sysDelta["delta_percent"],
                "diastolic_delta" => $diaDelta["delta_percent"],
                "direction" => $sysDelta["direction"],
                "status" => ($currSysAvg !== null && $currSysAvg <= 120 && $currDiaAvg <= 80) ? "Optimal" : "Elevated"
            ]
        ],
        "chart_points" => $points
    ], 200);

} catch (\PDOException $e) {
    sendResponse("error", "Database error computing vitals statistics: " . $e->getMessage(), null, 500);
}

