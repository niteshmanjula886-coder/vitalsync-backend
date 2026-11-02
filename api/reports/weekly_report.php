<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET" && $method !== "POST") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use GET or POST.",
        "data" => null
    ]);
}

// Determine Monday to Sunday week range
$ts = time();
$dayOfWeek = (int)date('N', $ts); // 1 (Mon) to 7 (Sun)
$startTs = strtotime('-' . ($dayOfWeek - 1) . ' days', $ts);
$endTs = strtotime('+' . (7 - $dayOfWeek) . ' days', $ts);

$weekStart = date('Y-m-d', $startTs);
$weekEnd = date('Y-m-d', $endTs);

// Allow overriding via GET params
if (!empty($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date'])) {
    $weekStart = $_GET['start_date'];
}
if (!empty($_GET['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])) {
    $weekEnd = $_GET['end_date'];
}

// 1. Heart Rate Stats
$hrStmt = $conn->prepare("SELECT AVG(heart_rate) as avg_hr, MIN(heart_rate) as min_hr, MAX(heart_rate) as max_hr, COUNT(*) as cnt FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND heart_rate > 0");
$hrStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$hrStmt->execute();
$hrRow = $hrStmt->get_result()->fetch_assoc();
$hrStmt->close();

$avgHr = ($hrRow && $hrRow["avg_hr"] !== null) ? round((float)$hrRow["avg_hr"]) : null;
$minHr = ($hrRow && $hrRow["min_hr"] !== null) ? (int)$hrRow["min_hr"] : null;
$maxHr = ($hrRow && $hrRow["max_hr"] !== null) ? (int)$hrRow["max_hr"] : null;

if ($avgHr === null) {
    $hrFallback = $conn->prepare("SELECT AVG(value_numeric) as avg_hr, MIN(value_numeric) as min_hr, MAX(value_numeric) as max_hr FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $hrFallback->bind_param("iss", $userId, $weekStart, $weekEnd);
    $hrFallback->execute();
    $hrFb = $hrFallback->get_result()->fetch_assoc();
    $hrFallback->close();
    if ($hrFb && $hrFb["avg_hr"] !== null) {
        $avgHr = round((float)$hrFb["avg_hr"]);
        $minHr = (int)$hrFb["min_hr"];
        $maxHr = (int)$hrFb["max_hr"];
    }
}

// HR Score
$hrScore = 0;
$hrCategory = "No Data";
if ($avgHr !== null && $avgHr > 0) {
    if ($avgHr >= 60 && $avgHr <= 100) {
        $hrScore = 95;
        $hrCategory = "Normal";
    } elseif (($avgHr >= 50 && $avgHr < 60) || ($avgHr > 100 && $avgHr <= 115)) {
        $hrScore = 75;
        $hrCategory = "Mid";
    } else {
        $hrScore = 50;
        $hrCategory = "Low";
    }
}

// 2. Blood Pressure Stats
$bpStmt = $conn->prepare("SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND systolic_bp > 0");
$bpStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$bpStmt->execute();
$bpRow = $bpStmt->get_result()->fetch_assoc();
$bpStmt->close();

$avgSys = ($bpRow && $bpRow["avg_sys"] !== null) ? round((float)$bpRow["avg_sys"]) : null;
$avgDia = ($bpRow && $bpRow["avg_dia"] !== null) ? round((float)$bpRow["avg_dia"]) : null;

if ($avgSys === null) {
    $bpFb = $conn->prepare("SELECT AVG(value_numeric) as avg_sys, AVG(value_secondary) as avg_dia FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $bpFb->bind_param("iss", $userId, $weekStart, $weekEnd);
    $bpFb->execute();
    $bFb = $bpFb->get_result()->fetch_assoc();
    $bpFb->close();
    if ($bFb && $bFb["avg_sys"] !== null && $bFb["avg_dia"] !== null) {
        $avgSys = round((float)$bFb["avg_sys"]);
        $avgDia = round((float)$bFb["avg_dia"]);
    }
}

$bpCategory = "No Data";
if ($avgSys !== null && $avgDia !== null) {
    if ($avgSys <= 120 && $avgDia <= 80) $bpCategory = "Normal";
    elseif ($avgSys <= 129 && $avgDia <= 80) $bpCategory = "Mid";
    else $bpCategory = "Low";
}

// 3. SpO2 Stats
$spo2Stmt = $conn->prepare("SELECT AVG(spo2) as avg_spo2, MIN(spo2) as min_spo2 FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND spo2 > 0");
$spo2Stmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$spo2Stmt->execute();
$spo2Row = $spo2Stmt->get_result()->fetch_assoc();
$spo2Stmt->close();

$avgSpo2 = ($spo2Row && $spo2Row["avg_spo2"] !== null) ? round((float)$spo2Row["avg_spo2"], 1) : null;
if ($avgSpo2 === null) {
    $spo2Fb = $conn->prepare("SELECT AVG(value_numeric) as avg_spo2 FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $spo2Fb->bind_param("iss", $userId, $weekStart, $weekEnd);
    $spo2Fb->execute();
    $sFb = $spo2Fb->get_result()->fetch_assoc();
    $spo2Fb->close();
    if ($sFb && $sFb["avg_spo2"] !== null) {
        $avgSpo2 = round((float)$sFb["avg_spo2"], 1);
    }
}

$spo2Category = "No Data";
if ($avgSpo2 !== null && $avgSpo2 > 0) {
    if ($avgSpo2 >= 95) $spo2Category = "Normal";
    elseif ($avgSpo2 >= 90) $spo2Category = "Mid";
    else $spo2Category = "Low";
}

// 4. Sleep Stats
$slStmt = $conn->prepare("SELECT AVG(duration_minutes) as avg_sleep, SUM(duration_minutes) as total_sleep, COUNT(*) as cnt FROM sleep_logs WHERE user_id = ? AND logged_at BETWEEN ? AND ? AND duration_minutes > 0");
$slStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$slStmt->execute();
$slRow = $slStmt->get_result()->fetch_assoc();
$slStmt->close();

$avgSleepMin = ($slRow && $slRow["avg_sleep"] !== null) ? (int)round((float)$slRow["avg_sleep"]) : 0;
$sleepScore = 0;
$sleepCategory = "No Data";
if ($avgSleepMin > 0) {
    if ($avgSleepMin >= 420) {
        $sleepScore = 95;
        $sleepCategory = "Normal";
    } elseif ($avgSleepMin >= 360) {
        $sleepScore = 75;
        $sleepCategory = "Mid";
    } else {
        $sleepScore = 50;
        $sleepCategory = "Low";
    }
}

// 5. Hydration Stats
$hyStmt = $conn->prepare("SELECT SUM(COALESCE(volume_ml, amount_ml, 0)) as total_intake, AVG(COALESCE(volume_ml, amount_ml, 0)) as avg_intake FROM hydration_logs WHERE user_id = ? AND (log_date BETWEEN ? AND ? OR logged_at BETWEEN ? AND ?)");
$hyStmt->bind_param("issss", $userId, $weekStart, $weekEnd, $weekStart, $weekEnd);
$hyStmt->execute();
$hyRow = $hyStmt->get_result()->fetch_assoc();
$hyStmt->close();

$totalHydrationMl = ($hyRow && $hyRow["total_intake"] !== null) ? (int)$hyRow["total_intake"] : 0;
$avgHydrationMl = ($hyRow && $hyRow["avg_intake"] !== null) ? (int)round((float)$hyRow["avg_intake"]) : 0;
$hydrationScore = 0;
$hydrationCategory = "No Data";
if ($avgHydrationMl > 0) {
    if ($avgHydrationMl >= 2000) {
        $hydrationScore = 100;
        $hydrationCategory = "Normal";
    } elseif ($avgHydrationMl >= 1500) {
        $hydrationScore = 80;
        $hydrationCategory = "Mid";
    } else {
        $hydrationScore = 55;
        $hydrationCategory = "Low";
    }
}

// 6. Activity / Steps Stats
$stStmt = $conn->prepare("SELECT SUM(steps) as total_steps, AVG(steps) as avg_steps FROM workout_sessions WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?");
$stStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$stStmt->execute();
$stRow = $stStmt->get_result()->fetch_assoc();
$stStmt->close();

$totalSteps = ($stRow && $stRow["total_steps"] !== null) ? (int)$stRow["total_steps"] : 0;
$avgSteps = ($stRow && $stRow["avg_steps"] !== null) ? (int)round((float)$stRow["avg_steps"]) : 0;

if ($avgSteps === 0) {
    // Check steps_logs table if exists
    $chkTable = $conn->query("SHOW TABLES LIKE 'steps_logs'");
    if ($chkTable && $chkTable->num_rows > 0) {
        $slq = $conn->prepare("SELECT SUM(step_count) as total_steps, AVG(step_count) as avg_steps FROM steps_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ?");
        $slq->bind_param("iss", $userId, $weekStart, $weekEnd);
        $slq->execute();
        $slRow = $slq->get_result()->fetch_assoc();
        $slq->close();
        if ($slRow && $slRow["avg_steps"] !== null) {
            $totalSteps = (int)$slRow["total_steps"];
            $avgSteps = (int)round((float)$slRow["avg_steps"]);
        }
    }
}

$activityScore = 0;
$activityCategory = "No Data";
if ($avgSteps > 0) {
    if ($avgSteps >= 8000) {
        $activityScore = 100;
        $activityCategory = "Normal";
    } elseif ($avgSteps >= 5000) {
        $activityScore = 75;
        $activityCategory = "Mid";
    } else {
        $activityScore = 50;
        $activityCategory = "Low";
    }
}

// 7. Composite Wellness Score & Performance Category
$activeScores = [];
if ($hrScore > 0) $activeScores[] = $hrScore;
if ($sleepScore > 0) $activeScores[] = $sleepScore;
if ($hydrationScore > 0) $activeScores[] = $hydrationScore;
if ($activityScore > 0) $activeScores[] = $activityScore;

$wellnessScore = 0;
$performanceCategory = "low";
if (count($activeScores) > 0) {
    $wellnessScore = (int)round(array_sum($activeScores) / count($activeScores));
    if ($wellnessScore >= 80) {
        $performanceCategory = "normal";
    } elseif ($wellnessScore >= 50) {
        $performanceCategory = "mid";
    } else {
        $performanceCategory = "low";
    }
}

// Summary observation text
$summaryParts = [];
if ($avgHr !== null) $summaryParts[] = "Resting heart rate averaged {$avgHr} BPM ($hrCategory)";
if ($avgSleepMin > 0) $summaryParts[] = "Sleep averaged " . intdiv($avgSleepMin, 60) . "h " . ($avgSleepMin % 60) . "m nightly ($sleepCategory)";
if ($avgHydrationMl > 0) $summaryParts[] = "Hydration averaged {$avgHydrationMl} ml daily ($hydrationCategory)";
if ($avgSteps > 0) $summaryParts[] = "Physical activity averaged " . number_format($avgSteps) . " steps/day ($activityCategory)";

if (!empty($summaryParts)) {
    $summaryText = "Weekly clinical performance: " . implode(". ", $summaryParts) . ". Wellness grade: " . strtoupper($performanceCategory) . " (" . $wellnessScore . "/100).";
} else {
    $summaryText = "No weekly vital logs recorded yet. Start tracking or connect a smartwatch to view clinical observations.";
}

// 8. Persist into weekly_health_reports table
$insStmt = $conn->prepare(
    "INSERT INTO weekly_health_reports (user_id, week_start_date, week_end_date, wellness_score, performance_category, heart_rate_score, sleep_score, hydration_score, activity_score, summary_text)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
         week_end_date = VALUES(week_end_date),
         wellness_score = VALUES(wellness_score),
         performance_category = VALUES(performance_category),
         heart_rate_score = VALUES(heart_rate_score),
         sleep_score = VALUES(sleep_score),
         hydration_score = VALUES(hydration_score),
         activity_score = VALUES(activity_score),
         summary_text = VALUES(summary_text)"
);

if ($insStmt) {
    $insStmt->bind_param(
        "issisiiiis",
        $userId,
        $weekStart,
        $weekEnd,
        $wellnessScore,
        $performanceCategory,
        $hrScore,
        $sleepScore,
        $hydrationScore,
        $activityScore,
        $summaryText
    );
    $insStmt->execute();
    $insStmt->close();
}

$conn->close();

respondJson(200, [
    "status" => "success",
    "success" => true,
    "message" => "Weekly health report computed successfully",
    "data" => [
        "week_start_date" => $weekStart,
        "week_end_date" => $weekEnd,
        "wellness_score" => $wellnessScore,
        "performance_category" => $performanceCategory,
        "category_label" => ucfirst($performanceCategory),
        "scores" => [
            "heart_rate" => $hrScore,
            "sleep" => $sleepScore,
            "hydration" => $hydrationScore,
            "activity" => $activityScore
        ],
        "metrics" => [
            "heart_rate_avg" => $avgHr !== null ? "{$avgHr} BPM" : "-- BPM",
            "heart_rate_status" => $hrCategory,
            "bp_avg" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg" : "--/--",
            "bp_status" => $bpCategory,
            "spo2_avg" => $avgSpo2 !== null ? "{$avgSpo2}%" : "--%",
            "spo2_status" => $spo2Category,
            "sleep_quality" => $avgSleepMin > 0 ? (intdiv($avgSleepMin, 60) . "h " . ($avgSleepMin % 60) . "m") : "--",
            "sleep_status" => $sleepCategory,
            "hydration_total" => $totalHydrationMl > 0 ? "{$totalHydrationMl} ml" : "-- ml",
            "hydration_status" => $hydrationCategory,
            "activity_steps" => $totalSteps > 0 ? number_format($totalSteps) . " steps" : "-- steps",
            "activity_status" => $activityCategory
        ],
        "summary_text" => $summaryText
    ]
]);

