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

// Rolling 7-day window relative to CURDATE() (today and the preceding 6 days)
$weekStart = date('Y-m-d', strtotime('-6 days'));
$weekEnd = date('Y-m-d');

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
$bpScore = 0;
if ($avgSys !== null && $avgDia !== null) {
    if ($avgSys < 90 || $avgDia < 60) {
        $bpCategory = "Low";
        $bpScore = 40;
    } elseif ($avgSys >= 140 || $avgDia >= 90) {
        $bpCategory = "Low";
        $bpScore = 45;
    } elseif ($avgSys >= 130 || $avgDia >= 80) {
        $bpCategory = "Mid";
        $bpScore = 70;
    } elseif ($avgSys >= 120 && $avgDia < 80) {
        $bpCategory = "Mid";
        $bpScore = 80;
    } else {
        $bpCategory = "Normal";
        $bpScore = 95;
    }
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
$spo2Score = 0;
if ($avgSpo2 !== null && $avgSpo2 > 0) {
    if ($avgSpo2 >= 95) {
        $spo2Category = "Normal";
        $spo2Score = 98;
    } elseif ($avgSpo2 >= 90) {
        $spo2Category = "Mid";
        $spo2Score = 65;
    } else {
        $spo2Category = "Low";
        $spo2Score = 30;
    }
}

/**
 * Derive sleep quality score and label from total sleep minutes when an explicit DB quality score is unavailable.
 */
function deriveWeeklySleepQuality(int $totalMinutes): array {
    if ($totalMinutes <= 0) {
        return [0, "No Log"];
    }
    if ($totalMinutes < 360) {
        return [50, "Poor"];
    }
    if ($totalMinutes < 420) {
        return [68, "Fair"];
    }
    if ($totalMinutes <= 510) {
        return [88, "Optimal"];
    }
    return [92, "Excellent"];
}

// 4. Sleep Stats (Rolling 7 days DATE_SUB(CURDATE(), INTERVAL 6 DAY) .. CURDATE() with complete 7-day array)
$sleepByDay = [];
$slStmt = $conn->prepare(
    "SELECT DATE(COALESCE(logged_at, logged_date, created_at, start_time)) AS log_day,
            SUM(COALESCE(NULLIF(duration_minutes, 0), TIMESTAMPDIFF(MINUTE, start_time, end_time), 0)) AS total_mins,
            AVG(NULLIF(quality_score, 0)) AS avg_quality
     FROM sleep_logs
     WHERE user_id = ?
       AND DATE(COALESCE(logged_at, logged_date, created_at, start_time)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND DATE(COALESCE(logged_at, logged_date, created_at, start_time)) <= CURDATE()
       AND COALESCE(NULLIF(duration_minutes, 0), TIMESTAMPDIFF(MINUTE, start_time, end_time), 0) > 0
     GROUP BY DATE(COALESCE(logged_at, logged_date, created_at, start_time))
     ORDER BY log_day ASC"
);
if ($slStmt) {
    $slStmt->bind_param("i", $userId);
    $slStmt->execute();
    $slRes = $slStmt->get_result();
    while ($row = $slRes->fetch_assoc()) {
        if (!empty($row["log_day"])) {
            $sleepByDay[$row["log_day"]] = [
                "duration_minutes" => (int)$row["total_mins"],
                "avg_quality" => $row["avg_quality"] !== null ? (float)$row["avg_quality"] : 0.0
            ];
        }
    }
    $slStmt->close();
}

// Fallback / supplement per missing day (or missing quality score) from sleep_records
try {
    $srStmt = $conn->prepare(
        "SELECT date_string AS log_day,
                SUM(duration_minutes) AS total_mins,
                AVG(NULLIF(sleep_score, 0)) AS avg_quality
         FROM sleep_records
         WHERE user_id = ?
           AND date_string >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
           AND date_string <= CURDATE()
           AND duration_minutes > 0
         GROUP BY date_string
         ORDER BY log_day ASC"
    );
    if ($srStmt) {
        $srStmt->bind_param("i", $userId);
        $srStmt->execute();
        $srRes = $srStmt->get_result();
        while ($row = $srRes->fetch_assoc()) {
            $d = $row["log_day"] ?? "";
            if ($d === "") {
                continue;
            }
            $srQuality = $row["avg_quality"] !== null ? (float)$row["avg_quality"] : 0.0;
            if (empty($sleepByDay[$d])) {
                $sleepByDay[$d] = [
                    "duration_minutes" => (int)$row["total_mins"],
                    "avg_quality" => $srQuality
                ];
            } elseif ($sleepByDay[$d]["avg_quality"] <= 0 && $srQuality > 0) {
                $sleepByDay[$d]["avg_quality"] = $srQuality;
            }
        }
        $srStmt->close();
    }
} catch (\Exception $e) {}

// Build complete 7-day sleep array (CURDATE() - 6 DAYS .. CURDATE(), empty days initialized with 0 / "No Log")
$sleepDays = [];
$totalSleepMins = 0;
$totalQualityScore = 0;
$loggedSleepDays = 0;
for ($offset = 6; $offset >= 0; $offset--) {
    $dayDate = date('Y-m-d', strtotime("-{$offset} days"));
    $dayLabel = date('D', strtotime($dayDate));
    $entry = $sleepByDay[$dayDate] ?? null;
    $mins = $entry ? (int)$entry["duration_minutes"] : 0;
    if ($mins > 0) {
        [$derivedScore, $dayQuality] = deriveWeeklySleepQuality($mins);
        $dbQuality = $entry ? (float)$entry["avg_quality"] : 0.0;
        $dayQualityScore = ($dbQuality > 0) ? max(1, min(100, (int)round($dbQuality))) : $derivedScore;
        $totalSleepMins += $mins;
        $totalQualityScore += $dayQualityScore;
        $loggedSleepDays++;
    } else {
        $dayQualityScore = 0;
        $dayQuality = "No Log";
    }
    $sleepDays[] = [
        "date" => $dayDate,
        "day_label" => $dayLabel,
        "duration" => $mins,
        "duration_minutes" => $mins,
        "quality_score" => $dayQualityScore,
        "quality" => $dayQuality,
        "quality_label" => $dayQuality
    ];
}

$avgSleepMin = $loggedSleepDays > 0 ? (int)round($totalSleepMins / $loggedSleepDays) : 0;
$averageSleepQuality = $loggedSleepDays > 0 ? (int)round($totalQualityScore / $loggedSleepDays) : 0;
$sleepScore = $averageSleepQuality;
if ($avgSleepMin > 0) {
    [, $sleepCategory] = deriveWeeklySleepQuality($avgSleepMin);
} else {
    $sleepCategory = "No Data";
}

// 5. Hydration Stats (Rolling 7 days relative to CURDATE(), 0 ml for empty days, sum of 7 days)
$hydrationByDay = [];
$hyStmt = $conn->prepare(
    "SELECT DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) AS h_day,
            COALESCE(SUM(COALESCE(volume_ml, intake_ml, amount_ml, 0)), 0) AS day_ml
     FROM hydration_logs
     WHERE user_id = ?
       AND DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at))
     ORDER BY h_day ASC"
);
if ($hyStmt) {
    $hyStmt->bind_param("i", $userId);
    $hyStmt->execute();
    $hyRes = $hyStmt->get_result();
    while ($hRow = $hyRes->fetch_assoc()) {
        if (!empty($hRow["h_day"])) {
            $hydrationByDay[$hRow["h_day"]] = (int)$hRow["day_ml"];
        }
    }
    $hyStmt->close();
}

$hydrationDays = [];
$totalHydrationMl = 0;
$hydDays = 0;
for ($offset = 6; $offset >= 0; $offset--) {
    $dDate = date('Y-m-d', strtotime("-{$offset} days"));
    $dMl = isset($hydrationByDay[$dDate]) ? max(0, (int)$hydrationByDay[$dDate]) : 0;
    if ($dMl > 0) {
        $hydDays++;
    }
    $totalHydrationMl += $dMl;
    $hydrationDays[] = [
        "date" => $dDate,
        "day_label" => date('D', strtotime($dDate)),
        "intake_ml" => $dMl
    ];
}
$avgHydrationMl = $hydDays > 0 ? (int)round($totalHydrationMl / $hydDays) : 0;

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

// 6. Activity / Steps & Workout Frequency Stats (Rolling 7 days)
$wsSteps = 0;
$workoutCount = 0;
$stStmt = $conn->prepare(
    "SELECT COALESCE(SUM(steps), 0) AS total_steps, COUNT(*) AS cnt
     FROM workout_sessions
     WHERE user_id = ?
       AND DATE(COALESCE(NULLIF(start_time, ''), date_string, created_at)) BETWEEN ? AND ?"
);
if ($stStmt) {
    $stStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $stStmt->execute();
    $stRow = $stStmt->get_result()->fetch_assoc();
    $stStmt->close();
    $wsSteps = (int)($stRow["total_steps"] ?? 0);
    $workoutCount = (int)($stRow["cnt"] ?? 0);
}

$asSteps = 0;
$asCount = 0;
$asStmt = $conn->prepare(
    "SELECT COALESCE(SUM(steps), 0) AS total_steps, COUNT(*) AS cnt
     FROM activity_sessions
     WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?"
);
if ($asStmt) {
    $asStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $asStmt->execute();
    $asRow = $asStmt->get_result()->fetch_assoc();
    $asStmt->close();
    $asSteps = (int)($asRow["total_steps"] ?? 0);
    $asCount = (int)($asRow["cnt"] ?? 0);
}

$slSteps = 0;
$chkTable = $conn->query("SHOW TABLES LIKE 'steps_logs'");
if ($chkTable && $chkTable->num_rows > 0) {
    $slq = $conn->prepare("SELECT COALESCE(SUM(step_count), 0) AS total_steps FROM steps_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ?");
    if ($slq) {
        $slq->bind_param("iss", $userId, $weekStart, $weekEnd);
        $slq->execute();
        $slRow = $slq->get_result()->fetch_assoc();
        $slq->close();
        $slSteps = (int)($slRow["total_steps"] ?? 0);
    }
}

$totalSteps = max($wsSteps, $asSteps, $slSteps);
if ($workoutCount === 0 && $asCount > 0) {
    $workoutCount = $asCount;
}
$avgSteps = (int)round($totalSteps / 7);

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

// 7. Resolve User Health Goals for Percentages & Baselines
$dailyStepGoal = 10000;
$dailyWaterGoal = 2000;
$nightlySleepTargetHours = 8.0;

$gStmt = $conn->prepare("SELECT target_steps, target_water_ml, target_sleep_hours FROM user_health_goals WHERE user_id = ? LIMIT 1");
if ($gStmt) {
    $gStmt->bind_param("i", $userId);
    $gStmt->execute();
    $gRow = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();
    if ($gRow) {
        if (!empty($gRow["target_steps"]) && (int)$gRow["target_steps"] > 0) $dailyStepGoal = (int)$gRow["target_steps"];
        if (!empty($gRow["target_water_ml"]) && (int)$gRow["target_water_ml"] > 0) $dailyWaterGoal = (int)$gRow["target_water_ml"];
        if (!empty($gRow["target_sleep_hours"]) && (float)$gRow["target_sleep_hours"] > 0) $nightlySleepTargetHours = (float)$gRow["target_sleep_hours"];
    }
}

if ($dailyWaterGoal === 2000) {
    $hgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
    if ($hgStmt) {
        $hgStmt->bind_param("i", $userId);
        $hgStmt->execute();
        $hgRow = $hgStmt->get_result()->fetch_assoc();
        $hgStmt->close();
        if ($hgRow && !empty($hgRow["daily_target_ml"]) && (int)$hgRow["daily_target_ml"] > 0) {
            $dailyWaterGoal = (int)$hgRow["daily_target_ml"];
        }
    }
}

$weeklyStepGoal = $dailyStepGoal * 7;
$weeklyWaterGoal = $dailyWaterGoal * 7;
$targetSleepMinutes = (int)round($nightlySleepTargetHours * 60);

$stepsPercent = $weeklyStepGoal > 0 ? min(100, (int)round(($totalSteps / (float)$weeklyStepGoal) * 100)) : 0;
$hydrationPercent = $weeklyWaterGoal > 0 ? min(100, (int)round(($totalHydrationMl / (float)$weeklyWaterGoal) * 100)) : 0;
$sleepPercent = $targetSleepMinutes > 0 ? min(100, (int)round(($avgSleepMin / (float)$targetSleepMinutes) * 100)) : 0;
$sleepHours = round($avgSleepMin / 60.0, 1);
$sleepDuration = intdiv($avgSleepMin, 60) . "h " . ($avgSleepMin % 60) . "m";

// 8. Composite Wellness Score & Performance Category
$activeScores = [];
if ($hrScore > 0) $activeScores[] = $hrScore;
if ($bpScore > 0) $activeScores[] = $bpScore;
if ($spo2Score > 0) $activeScores[] = $spo2Score;
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
$wellnessStatus = ucfirst($performanceCategory);

$hrValue = $avgHr !== null ? (string)$avgHr : "--";
$hrStatus = $hrCategory;
$spo2Value = $avgSpo2 !== null ? (string)$avgSpo2 : "--";
$spo2Status = $spo2Category;
$sys = $avgSys !== null ? (string)$avgSys : "--";
$dia = $avgDia !== null ? (string)$avgDia : "--";
$bpStatus = $bpCategory;
$steps = number_format($totalSteps);
$stepGoal = number_format($weeklyStepGoal);
$hydrationMl = number_format($totalHydrationMl);
$waterGoal = number_format($weeklyWaterGoal);

// 9. Ollama Executive Summary Generation & Deterministic Clinical Fallback
$breakdownPrompt = "Generate a concise 3-4 sentence clinical Executive Summary and 3 specific Action Recommendations based strictly on this user's calculated Vital Performance Breakdown:\n" .
    "- Overall Wellness Score: {$wellnessScore}/100 ({$wellnessStatus})\n" .
    "- Heart Rate: {$hrValue} BPM ({$hrStatus}) - Score: {$hrScore}%\n" .
    "- Blood Oxygen (SpO2): {$spo2Value}% ({$spo2Status}) - Score: {$spo2Score}%\n" .
    "- Blood Pressure: {$sys}/{$dia} mmHg ({$bpStatus}) - Score: {$bpScore}%\n" .
    "- Activity: {$steps} / {$stepGoal} steps ({$stepsPercent}%)\n" .
    "- Hydration: {$hydrationMl} / {$waterGoal} ml ({$hydrationPercent}%)\n" .
    "- Sleep: {$sleepHours} hrs ({$sleepPercent}%)\n\n" .
    "Rules:\n" .
    "1. Explicitly cite the user's exact numbers (e.g. {$wellnessScore}/100, {$sys}/{$dia} mmHg, {$hydrationMl} ml, {$steps} steps) in the summary.\n" .
    "2. Highlight which vitals are performing well (Optimal) and which specific metrics pulled the Wellness Score down (Needs Attention/Alert).\n" .
    "3. Output valid JSON: {\"executive_summary\": \"...\", \"key_findings\": [\"...\", \"...\"], \"recommendations\": [\"...\", \"...\"]}";

$fallbackSummary = "Executive Summary: Overall weekly health scored {$wellnessScore}/100 ({$wellnessStatus}). " .
    "Cardiovascular vitals show resting heart rate at {$hrValue} BPM ({$hrStatus}) and blood pressure at {$sys}/{$dia} mmHg ({$bpStatus}). " .
    "Respiratory oxygenation sits at {$spo2Value}% SpO2 ({$spo2Status}). " .
    "Lifestyle metrics recorded {$steps} steps ({$stepsPercent}% of goal), {$hydrationMl} ml hydration ({$hydrationPercent}% of goal), and {$sleepDuration} sleep ({$sleepPercent}% of target).";

$keyFindings = [];
if ($hrScore > 0) $keyFindings[] = "Heart Rate: {$hrValue} BPM ({$hrStatus})";
if ($bpScore > 0) $keyFindings[] = "Blood Pressure: {$sys}/{$dia} mmHg ({$bpStatus})";
if ($spo2Score > 0) $keyFindings[] = "SpO2: {$spo2Value}% ({$spo2Status})";
if ($avgSleepMin > 0) $keyFindings[] = "Sleep: {$sleepDuration} avg ({$sleepCategory})";
if ($totalHydrationMl > 0) $keyFindings[] = "Hydration: {$hydrationMl} ml ({$hydrationCategory})";
if ($totalSteps > 0) $keyFindings[] = "Activity: {$steps} steps ({$activityCategory})";

$recommendations = [];
if ($spo2Category === "Low") $recommendations[] = "Prioritize well-ventilated environments and check oxygenation levels regularly.";
if ($bpCategory === "Low") $recommendations[] = "Monitor blood pressure consistently and ensure adequate hydration and electrolytes.";
if ($activityCategory === "Low") $recommendations[] = "Gradually increase daily walking to close the step deficit.";
if ($hydrationCategory === "Low") $recommendations[] = "Drink water consistently throughout the day to meet the 2,000+ ml baseline.";
if (empty($recommendations)) {
    $recommendations[] = "Maintain your current optimal habits across cardiovascular and lifestyle vitals.";
}

$summaryText = $fallbackSummary;
if (function_exists('curl_init')) {
    $ollamaPayload = json_encode([
        "model" => "qwen2.5:1.5b",
        "prompt" => $breakdownPrompt,
        "stream" => false,
        "format" => "json",
        "options" => [
            "temperature" => 0.2,
            "top_p" => 0.85,
            "num_predict" => 250
        ]
    ]);
    $ch = curl_init("http://127.0.0.1:11434/api/generate");
    if ($ch) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ollamaPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $resp) {
            $parsedResp = json_decode($resp, true);
            $contentStr = $parsedResp["response"] ?? "";
            $jsonContent = json_decode($contentStr, true);
            if ($jsonContent && !empty($jsonContent["executive_summary"])) {
                $summaryText = trim($jsonContent["executive_summary"]);
                if (!empty($jsonContent["key_findings"]) && is_array($jsonContent["key_findings"])) {
                    $keyFindings = $jsonContent["key_findings"];
                }
                if (!empty($jsonContent["recommendations"]) && is_array($jsonContent["recommendations"])) {
                    $recommendations = $jsonContent["recommendations"];
                }
            }
        }
    }
}

// 10. Persist into weekly_health_reports table
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

$totalDistanceKm = round($totalSteps * 0.00078, 2);
$totalCaloriesBurned = (int)round($totalSteps * 0.045);

$vitalPerformanceBreakdown = [
    "overall_wellness_score" => $wellnessScore,
    "overall_status" => $wellnessStatus,
    "heart_rate" => [
        "value" => $avgHr !== null ? "{$avgHr} BPM" : "-- BPM",
        "status" => $hrCategory,
        "score" => $hrScore
    ],
    "blood_pressure" => [
        "value" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg" : "--/--",
        "status" => $bpCategory,
        "score" => $bpScore
    ],
    "spo2" => [
        "value" => $avgSpo2 !== null ? "{$avgSpo2}%" : "--%",
        "status" => $spo2Category,
        "score" => $spo2Score
    ],
    "sleep" => [
        "value" => $avgSleepMin > 0 ? "{$averageSleepQuality}% - {$sleepCategory}" : "--",
        "status" => $avgSleepMin > 0 ? "{$sleepDuration} avg" : "No Logs Recorded",
        "score" => $sleepScore,
        "duration_minutes" => $avgSleepMin,
        "completion_percent" => $sleepPercent
    ],
    "hydration" => [
        "value" => $totalHydrationMl > 0 ? "{$totalHydrationMl} ml" : "0 ml",
        "status" => $hydrationCategory,
        "score" => $hydrationScore,
        "total_ml" => $totalHydrationMl,
        "goal_ml" => $weeklyWaterGoal,
        "completion_percent" => $hydrationPercent
    ],
    "activity" => [
        "value" => $totalSteps > 0 ? number_format($totalSteps) . " steps" : "0 steps",
        "status" => $activityCategory,
        "score" => $activityScore,
        "total_steps" => $totalSteps,
        "goal_steps" => $weeklyStepGoal,
        "completion_percent" => $stepsPercent,
        "distance_km" => $totalDistanceKm,
        "calories_kcal" => $totalCaloriesBurned
    ]
];

$executiveSummary = [
    "summary" => $summaryText,
    "key_findings" => $keyFindings,
    "recommendations" => $recommendations
];

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
        "average_duration_minutes" => $avgSleepMin,
        "average_sleep_quality" => $averageSleepQuality,
        "logged_sleep_days" => $loggedSleepDays,
        "scores" => [
            "heart_rate" => $hrScore,
            "blood_pressure" => $bpScore,
            "spo2" => $spo2Score,
            "sleep" => $sleepScore,
            "hydration" => $hydrationScore,
            "activity" => $activityScore
        ],
        "sleep_days" => $sleepDays,
        "hydration_days" => $hydrationDays,
        "metrics" => [
            "heart_rate_avg" => $avgHr !== null ? "{$avgHr} BPM" : "-- BPM",
            "heart_rate_status" => $hrCategory,
            "avg_hr_bpm" => $avgHr !== null ? (int)$avgHr : null,
            "bp_avg" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg" : "--/--",
            "bp_status" => $bpCategory,
            "spo2_avg" => $avgSpo2 !== null ? "{$avgSpo2}%" : "--%",
            "spo2_status" => $spo2Category,
            "sleep_quality" => $avgSleepMin > 0 ? "{$averageSleepQuality}% - {$sleepCategory}" : "--",
            "sleep_status" => $avgSleepMin > 0 ? "{$sleepDuration} avg" : "No Logs Recorded",
            "avg_sleep_minutes" => $avgSleepMin,
            "average_duration_minutes" => $avgSleepMin,
            "average_sleep_quality" => $averageSleepQuality,
            "sleep_score" => $averageSleepQuality,
            "hydration_total" => $totalHydrationMl > 0 ? "{$totalHydrationMl} ml" : "0 ml",
            "hydration_status" => $hydrationCategory,
            "total_hydration_ml" => $totalHydrationMl,
            "activity_steps" => $totalSteps > 0 ? number_format($totalSteps) . " steps" : "0 steps",
            "activity_status" => $activityCategory,
            "total_steps" => $totalSteps,
            "workout_count" => $workoutCount
        ],
        "vital_performance_breakdown" => $vitalPerformanceBreakdown,
        "executive_summary" => $executiveSummary,
        "summary_text" => $summaryText
    ]
]);

