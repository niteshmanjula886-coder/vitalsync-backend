<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET" && $method !== "POST") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use GET or POST.",
        "data" => null
    ]);
}

// 7-day calendar window: CURDATE() - 6 days to CURDATE()
$weekStart = date('Y-m-d', strtotime('-6 days'));
$weekEnd = date('Y-m-d');

if (!empty($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date'])) {
    $weekStart = $_GET['start_date'];
}
if (!empty($_GET['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])) {
    $weekEnd = $_GET['end_date'];
}

// -------------------------------------------------------------------------
// 1. Resolve User Health Goals for Percentages & Baselines
// -------------------------------------------------------------------------
$dailyStepGoal = 10000;
$dailyWaterGoal = 2500;
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

$weeklyStepGoal = $dailyStepGoal * 7;
$weeklyWaterGoal = $dailyWaterGoal * 7;
$targetSleepMinutes = (int)round($nightlySleepTargetHours * 60);

// -------------------------------------------------------------------------
// 2. Heart Rate Stats (7-Day Weekly Basis)
// -------------------------------------------------------------------------
$hrStmt = $conn->prepare("SELECT AVG(heart_rate) as avg_hr, MIN(heart_rate) as min_hr, MAX(heart_rate) as max_hr, COUNT(*) as cnt FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND heart_rate > 0");
$hrStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$hrStmt->execute();
$hrRow = $hrStmt->get_result()->fetch_assoc();
$hrStmt->close();

$avgHr = ($hrRow && $hrRow["avg_hr"] !== null) ? round((float)$hrRow["avg_hr"]) : null;
$minHr = ($hrRow && $hrRow["min_hr"] !== null) ? (int)$hrRow["min_hr"] : null;
$maxHr = ($hrRow && $hrRow["max_hr"] !== null) ? (int)$hrRow["max_hr"] : null;
$hrCount = ($hrRow && $hrRow["cnt"] !== null) ? (int)$hrRow["cnt"] : 0;

if ($avgHr === null) {
    $hrFallback = $conn->prepare("SELECT AVG(value_numeric) as avg_hr, MIN(value_numeric) as min_hr, MAX(value_numeric) as max_hr, COUNT(*) as cnt FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    if ($hrFallback) {
        $hrFallback->bind_param("iss", $userId, $weekStart, $weekEnd);
        $hrFallback->execute();
        $hrFb = $hrFallback->get_result()->fetch_assoc();
        $hrFallback->close();
        if ($hrFb && $hrFb["avg_hr"] !== null) {
            $avgHr = round((float)$hrFb["avg_hr"]);
            $minHr = (int)$hrFb["min_hr"];
            $maxHr = (int)$hrFb["max_hr"];
            $hrCount = (int)$hrFb["cnt"];
        }
    }
}

// Fallback if 7-day has no readings: check latest reading
if ($avgHr === null) {
    $hrLatestVr = $conn->prepare("SELECT value_numeric as latest_hr FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND value_numeric > 0 ORDER BY recorded_at DESC, id DESC LIMIT 1");
    if ($hrLatestVr) {
        $hrLatestVr->bind_param("i", $userId);
        $hrLatestVr->execute();
        $hrLRow = $hrLatestVr->get_result()->fetch_assoc();
        $hrLatestVr->close();
        if ($hrLRow && $hrLRow["latest_hr"] !== null) {
            $avgHr = round((float)$hrLRow["latest_hr"]);
            $minHr = $avgHr;
            $maxHr = $avgHr;
            $hrCount = 1;
        }
    }
}
if ($avgHr === null) {
    $hrLatestLog = $conn->prepare("SELECT heart_rate as latest_hr FROM vitals_logs WHERE user_id = ? AND heart_rate > 0 ORDER BY logged_at DESC, id DESC LIMIT 1");
    if ($hrLatestLog) {
        $hrLatestLog->bind_param("i", $userId);
        $hrLatestLog->execute();
        $hrLLRow = $hrLatestLog->get_result()->fetch_assoc();
        $hrLatestLog->close();
        if ($hrLLRow && $hrLLRow["latest_hr"] !== null) {
            $avgHr = round((float)$hrLLRow["latest_hr"]);
            $minHr = $avgHr;
            $maxHr = $avgHr;
            $hrCount = 1;
        }
    }
}
if ($avgHr === null) {
    $hrRecent = $conn->prepare("SELECT bpm FROM heart_rate_logs WHERE user_id = ? AND bpm > 0 ORDER BY id DESC LIMIT 1");
    if ($hrRecent) {
        $hrRecent->bind_param("i", $userId);
        $hrRecent->execute();
        $hrRecRow = $hrRecent->get_result()->fetch_assoc();
        $hrRecent->close();
        if ($hrRecRow && !empty($hrRecRow["bpm"])) {
            $avgHr = round((float)$hrRecRow["bpm"]);
            $minHr = $avgHr;
            $maxHr = $avgHr;
            $hrCount = 1;
        }
    }
}

$hrScore = 0;
$hrCategory = "No Data";
if ($avgHr !== null && $avgHr > 0) {
    if ($avgHr >= 60 && $avgHr <= 100) {
        $hrScore = 95;
        $hrCategory = "Normal Resting";
    } elseif (($avgHr >= 50 && $avgHr < 60) || ($avgHr > 100 && $avgHr <= 115)) {
        $hrScore = 75;
        $hrCategory = "Borderline / Elevated";
    } else {
        $hrScore = 50;
        $hrCategory = "Irregular / Attention";
    }
}

// -------------------------------------------------------------------------
// 3. Blood Pressure Stats (7-Day Weekly Basis)
// -------------------------------------------------------------------------
$bpStmt = $conn->prepare("SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND systolic_bp > 0");
$bpStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$bpStmt->execute();
$bpRow = $bpStmt->get_result()->fetch_assoc();
$bpStmt->close();

$avgSys = ($bpRow && $bpRow["avg_sys"] !== null) ? round((float)$bpRow["avg_sys"]) : null;
$avgDia = ($bpRow && $bpRow["avg_dia"] !== null) ? round((float)$bpRow["avg_dia"]) : null;

if ($avgSys === null) {
    $bpFb = $conn->prepare("SELECT AVG(value_numeric) as avg_sys, AVG(value_secondary) as avg_dia FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    if ($bpFb) {
        $bpFb->bind_param("iss", $userId, $weekStart, $weekEnd);
        $bpFb->execute();
        $bFb = $bpFb->get_result()->fetch_assoc();
        $bpFb->close();
        if ($bFb && $bFb["avg_sys"] !== null && $bFb["avg_dia"] !== null) {
            $avgSys = round((float)$bFb["avg_sys"]);
            $avgDia = round((float)$bFb["avg_dia"]);
        }
    }
}

// Fallback to latest reading if 7-day has none
if ($avgSys === null) {
    $bpLatestVr = $conn->prepare("SELECT value_numeric as latest_sys, value_secondary as latest_dia FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND value_numeric > 0 ORDER BY recorded_at DESC, id DESC LIMIT 1");
    if ($bpLatestVr) {
        $bpLatestVr->bind_param("i", $userId);
        $bpLatestVr->execute();
        $bpLRow = $bpLatestVr->get_result()->fetch_assoc();
        $bpLatestVr->close();
        if ($bpLRow && $bpLRow["latest_sys"] !== null && $bpLRow["latest_dia"] !== null) {
            $avgSys = round((float)$bpLRow["latest_sys"]);
            $avgDia = round((float)$bpLRow["latest_dia"]);
        }
    }
}
if ($avgSys === null) {
    $bpLatestLog = $conn->prepare("SELECT systolic_bp as latest_sys, diastolic_bp as latest_dia FROM vitals_logs WHERE user_id = ? AND systolic_bp > 0 ORDER BY logged_at DESC, id DESC LIMIT 1");
    if ($bpLatestLog) {
        $bpLatestLog->bind_param("i", $userId);
        $bpLatestLog->execute();
        $bpLLRow = $bpLatestLog->get_result()->fetch_assoc();
        $bpLatestLog->close();
        if ($bpLLRow && $bpLLRow["latest_sys"] !== null && $bpLLRow["latest_dia"] !== null) {
            $avgSys = round((float)$bpLLRow["latest_sys"]);
            $avgDia = round((float)$bpLLRow["latest_dia"]);
        }
    }
}

$bpCategory = "No Data";
$bpScore = 0;
if ($avgSys !== null && $avgDia !== null) {
    if ($avgSys < 90 || $avgDia < 60) {
        $bpCategory = "Hypotension";
        $bpScore = 40;
    } elseif ($avgSys >= 140 || $avgDia >= 90) {
        $bpCategory = "Stage 2 Hypertension";
        $bpScore = 45;
    } elseif ($avgSys >= 130 || $avgDia >= 80) {
        $bpCategory = "Stage 1 Hypertension";
        $bpScore = 70;
    } elseif ($avgSys >= 120 && $avgDia < 80) {
        $bpCategory = "Elevated BP";
        $bpScore = 80;
    } else {
        $bpCategory = "Optimal BP";
        $bpScore = 95;
    }
}

// -------------------------------------------------------------------------
// 4. SpO2 Stats (7-Day Weekly Basis)
// -------------------------------------------------------------------------
$spo2Stmt = $conn->prepare("SELECT AVG(spo2) as avg_spo2, MIN(spo2) as min_spo2, MAX(spo2) as max_spo2 FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND spo2 > 0");
$spo2Stmt->bind_param("iss", $userId, $weekStart, $weekEnd);
$spo2Stmt->execute();
$spo2Row = $spo2Stmt->get_result()->fetch_assoc();
$spo2Stmt->close();

$avgSpo2 = ($spo2Row && $spo2Row["avg_spo2"] !== null) ? round((float)$spo2Row["avg_spo2"], 1) : null;
$minSpo2 = ($spo2Row && $spo2Row["min_spo2"] !== null) ? round((float)$spo2Row["min_spo2"], 1) : null;
$maxSpo2 = ($spo2Row && $spo2Row["max_spo2"] !== null) ? round((float)$spo2Row["max_spo2"], 1) : null;

if ($avgSpo2 === null) {
    $spo2Fb = $conn->prepare("SELECT AVG(value_numeric) as avg_spo2, MIN(value_numeric) as min_spo2, MAX(value_numeric) as max_spo2 FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    if ($spo2Fb) {
        $spo2Fb->bind_param("iss", $userId, $weekStart, $weekEnd);
        $spo2Fb->execute();
        $sFb = $spo2Fb->get_result()->fetch_assoc();
        $spo2Fb->close();
        if ($sFb && $sFb["avg_spo2"] !== null) {
            $avgSpo2 = round((float)$sFb["avg_spo2"], 1);
            $minSpo2 = round((float)$sFb["min_spo2"], 1);
            $maxSpo2 = round((float)$sFb["max_spo2"], 1);
        }
    }
}

// Fallback to latest reading if 7-day has none
if ($avgSpo2 === null) {
    $spo2LatestVr = $conn->prepare("SELECT value_numeric as latest_spo2 FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND value_numeric > 0 ORDER BY recorded_at DESC, id DESC LIMIT 1");
    if ($spo2LatestVr) {
        $spo2LatestVr->bind_param("i", $userId);
        $spo2LatestVr->execute();
        $spLRow = $spo2LatestVr->get_result()->fetch_assoc();
        $spo2LatestVr->close();
        if ($spLRow && $spLRow["latest_spo2"] !== null) {
            $avgSpo2 = round((float)$spLRow["latest_spo2"], 1);
            $minSpo2 = $avgSpo2;
            $maxSpo2 = $avgSpo2;
        }
    }
}
if ($avgSpo2 === null) {
    $spo2LatestLog = $conn->prepare("SELECT spo2 as latest_spo2 FROM vitals_logs WHERE user_id = ? AND spo2 > 0 ORDER BY logged_at DESC, id DESC LIMIT 1");
    if ($spo2LatestLog) {
        $spo2LatestLog->bind_param("i", $userId);
        $spo2LatestLog->execute();
        $spLLRow = $spo2LatestLog->get_result()->fetch_assoc();
        $spo2LatestLog->close();
        if ($spLLRow && $spLLRow["latest_spo2"] !== null) {
            $avgSpo2 = round((float)$spLLRow["latest_spo2"], 1);
            $minSpo2 = $avgSpo2;
            $maxSpo2 = $avgSpo2;
        }
    }
}

$spo2Category = "No Data";
$spo2Score = 0;
if ($avgSpo2 !== null && $avgSpo2 > 0) {
    if ($avgSpo2 >= 95) {
        $spo2Category = "Optimal Oxygenation";
        $spo2Score = 98;
    } elseif ($avgSpo2 >= 90) {
        $spo2Category = "Mild Desaturation";
        $spo2Score = 65;
    } else {
        $spo2Category = "Low Oxygenation";
        $spo2Score = 30;
    }
}

// -------------------------------------------------------------------------
// 5. Sleep Stats (7-Day Weekly Basis: Group raw logs by calendar date first)
// -------------------------------------------------------------------------
$sleepByDay = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $dayKey = date('Y-m-d', strtotime("-{$offset} days"));
    $sleepByDay[$dayKey] = [
        "duration_minutes" => 0,
        "quality_score" => 0
    ];
}

$slStmt = $conn->prepare(
    "SELECT DATE(COALESCE(logged_at, logged_date, created_at, start_time)) AS log_day,
            SUM(COALESCE(NULLIF(duration_minutes, 0), TIMESTAMPDIFF(MINUTE, start_time, end_time), 0)) AS total_mins,
            AVG(NULLIF(quality_score, 0)) AS avg_quality
     FROM sleep_logs
     WHERE user_id = ?
       AND DATE(COALESCE(logged_at, logged_date, created_at, start_time)) BETWEEN ? AND ?
       AND COALESCE(NULLIF(duration_minutes, 0), TIMESTAMPDIFF(MINUTE, start_time, end_time), 0) > 0
     GROUP BY DATE(COALESCE(logged_at, logged_date, created_at, start_time))"
);
if ($slStmt) {
    $slStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $slStmt->execute();
    $slRes = $slStmt->get_result();
    while ($row = $slRes->fetch_assoc()) {
        $d = $row["log_day"] ?? "";
        if (!empty($d) && isset($sleepByDay[$d])) {
            $sleepByDay[$d]["duration_minutes"] += (int)$row["total_mins"];
            if ($row["avg_quality"] !== null) {
                $sleepByDay[$d]["quality_score"] = (float)$row["avg_quality"];
            }
        }
    }
    $slStmt->close();
}

// Supplement from sleep_records
try {
    $srStmt = $conn->prepare(
        "SELECT date_string AS log_day,
                SUM(duration_minutes) AS total_mins,
                AVG(NULLIF(sleep_score, 0)) AS avg_quality
         FROM sleep_records
         WHERE user_id = ?
           AND date_string BETWEEN ? AND ?
           AND duration_minutes > 0
         GROUP BY date_string"
    );
    if ($srStmt) {
        $srStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
        $srStmt->execute();
        $srRes = $srStmt->get_result();
        while ($row = $srRes->fetch_assoc()) {
            $d = $row["log_day"] ?? "";
            if (!empty($d) && isset($sleepByDay[$d])) {
                if ($sleepByDay[$d]["duration_minutes"] <= 0) {
                    $sleepByDay[$d]["duration_minutes"] = (int)$row["total_mins"];
                }
                if ($sleepByDay[$d]["quality_score"] <= 0 && $row["avg_quality"] !== null) {
                    $sleepByDay[$d]["quality_score"] = (float)$row["avg_quality"];
                }
            }
        }
        $srStmt->close();
    }
} catch (\Exception $e) {}

// Supplement from vitals_readings (reading_type = 'sleep')
try {
    $vrSleep = $conn->prepare(
        "SELECT DATE(recorded_at) AS log_day,
                AVG(value_numeric) AS total_mins
         FROM vitals_readings
         WHERE user_id = ?
           AND reading_type = 'sleep'
           AND value_numeric > 0
           AND DATE(recorded_at) BETWEEN ? AND ?
         GROUP BY DATE(recorded_at)"
    );
    if ($vrSleep) {
        $vrSleep->bind_param("iss", $userId, $weekStart, $weekEnd);
        $vrSleep->execute();
        $vrRes = $vrSleep->get_result();
        while ($row = $vrRes->fetch_assoc()) {
            $d = $row["log_day"] ?? "";
            if (!empty($d) && isset($sleepByDay[$d])) {
                if ($sleepByDay[$d]["duration_minutes"] <= 0) {
                    $sleepByDay[$d]["duration_minutes"] = (int)round((float)$row["total_mins"]);
                }
            }
        }
        $vrSleep->close();
    }
} catch (\Exception $e) {}

$weeklyTotalSleepMins = 0;
$loggedSleepDays = 0;
$sleepDays = [];

foreach ($sleepByDay as $dateStr => $sData) {
    $mins = (int)$sData["duration_minutes"];
    $dayLabel = date('D', strtotime($dateStr));
    $hrs = round($mins / 60.0, 1);
    if ($mins > 0) {
        $weeklyTotalSleepMins += $mins;
        $loggedSleepDays++;
    }
    $sleepDays[] = [
        "date" => $dateStr,
        "day_label" => $dayLabel,
        "duration" => $mins,
        "duration_minutes" => $mins,
        "duration_hours" => $hrs,
        "quality_score" => (int)$sData["quality_score"]
    ];
}

// Fallback to latest sleep entry if 7-day window was completely empty
if ($loggedSleepDays === 0) {
    $latestSleepMin = 0;
    $slLatest = $conn->prepare(
        "SELECT duration_minutes, quality_score
         FROM sleep_logs
         WHERE user_id = ? AND duration_minutes > 0
         ORDER BY COALESCE(logged_at, created_at, start_time) DESC, id DESC LIMIT 1"
    );
    if ($slLatest) {
        $slLatest->bind_param("i", $userId);
        $slLatest->execute();
        $slLatRow = $slLatest->get_result()->fetch_assoc();
        $slLatest->close();
        if ($slLatRow && (int)$slLatRow["duration_minutes"] > 0) {
            $latestSleepMin = (int)$slLatRow["duration_minutes"];
        }
    }
    if ($latestSleepMin <= 0) {
        $srLatest = $conn->prepare(
            "SELECT duration_minutes, sleep_score
             FROM sleep_records
             WHERE user_id = ? AND duration_minutes > 0
             ORDER BY date_string DESC, id DESC LIMIT 1"
        );
        if ($srLatest) {
            $srLatest->bind_param("i", $userId);
            $srLatest->execute();
            $srLatRow = $srLatest->get_result()->fetch_assoc();
            $srLatest->close();
            if ($srLatRow && (int)$srLatRow["duration_minutes"] > 0) {
                $latestSleepMin = (int)$srLatRow["duration_minutes"];
            }
        }
    }
    if ($latestSleepMin <= 0) {
        $vrLatest = $conn->prepare(
            "SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'sleep' AND value_numeric > 0 ORDER BY recorded_at DESC, id DESC LIMIT 1"
        );
        if ($vrLatest) {
            $vrLatest->bind_param("i", $userId);
            $vrLatest->execute();
            $vrLatRow = $vrLatest->get_result()->fetch_assoc();
            $vrLatest->close();
            if ($vrLatRow && (int)$vrLatRow["value_numeric"] > 0) {
                $latestSleepMin = (int)round((float)$vrLatRow["value_numeric"]);
            }
        }
    }
    if ($latestSleepMin > 0) {
        $weeklyTotalSleepMins = $latestSleepMin;
        $loggedSleepDays = 1;
        $lastIdx = count($sleepDays) - 1;
        $sleepDays[$lastIdx]["duration"] = $latestSleepMin;
        $sleepDays[$lastIdx]["duration_minutes"] = $latestSleepMin;
        $sleepDays[$lastIdx]["duration_hours"] = round($latestSleepMin / 60.0, 1);
    }
}

$weeklyTotalSleepHrs = round($weeklyTotalSleepMins / 60.0, 1);
$weeklyAvgSleepMins = $loggedSleepDays > 0 ? (int)round($weeklyTotalSleepMins / $loggedSleepDays) : 0;
$weeklyAvgSleepHrs = round($weeklyAvgSleepMins / 60.0, 1);

// Strictly calculate weekly sleep quality & score from $weeklyAvgSleepHrs
if ($weeklyAvgSleepHrs >= 7.0 && $weeklyAvgSleepHrs <= 9.0) {
    $sleepQuality = "Optimal Weekly Sleep";
    $sleepScore = 95;
} elseif ($weeklyAvgSleepHrs >= 6.0) {
    $sleepQuality = "Good / Fair Weekly Avg";
    $sleepScore = 75;
} elseif ($weeklyAvgSleepHrs >= 5.0) {
    $sleepQuality = "Moderate Weekly Deficit";
    $sleepScore = 55;
} elseif ($weeklyAvgSleepHrs > 0) {
    $sleepQuality = "Poor Weekly Sleep";
    $sleepScore = 35;
} else {
    $sleepQuality = "No Sleep Logged This Week";
    $sleepScore = 0;
}

$avgSleepH = intdiv($weeklyAvgSleepMins, 60);
$avgSleepM = $weeklyAvgSleepMins % 60;
$weeklySleepFormatted = ($weeklyAvgSleepMins > 0)
    ? "{$avgSleepH}h {$avgSleepM}m avg/night (Total: {$weeklyTotalSleepHrs} hrs)"
    : "No Sleep Logged This Week";
$weeklySleepShort = ($weeklyAvgSleepMins > 0) ? "{$avgSleepH}h {$avgSleepM}m avg/night" : "--";
$sleepPercent = $targetSleepMinutes > 0 ? min(100, (int)round(($weeklyAvgSleepMins / (float)$targetSleepMinutes) * 100)) : 0;

// -------------------------------------------------------------------------
// 6. Hydration Stats (7-Day Weekly Basis: Group raw logs by calendar date first)
// -------------------------------------------------------------------------
$hydrationByDay = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $dayKey = date('Y-m-d', strtotime("-{$offset} days"));
    $hydrationByDay[$dayKey] = 0;
}

$hyStmt = $conn->prepare(
    "SELECT DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) AS h_day,
            COALESCE(SUM(COALESCE(amount_ml, volume_ml, intake_ml, 0)), 0) AS daily_ml
     FROM hydration_logs
     WHERE user_id = ?
       AND DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) BETWEEN ? AND ?
     GROUP BY DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at))"
);
if ($hyStmt) {
    $hyStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $hyStmt->execute();
    $hyRes = $hyStmt->get_result();
    while ($hRow = $hyRes->fetch_assoc()) {
        $d = $hRow["h_day"] ?? "";
        if (!empty($d) && isset($hydrationByDay[$d])) {
            $hydrationByDay[$d] += (int)$hRow["daily_ml"];
        }
    }
    $hyStmt->close();
}

// Supplement from vitals_readings (reading_type = 'hydration')
try {
    $hyVr = $conn->prepare(
        "SELECT DATE(recorded_at) AS h_day, COALESCE(SUM(value_numeric), 0) AS daily_ml
         FROM vitals_readings
         WHERE user_id = ? AND reading_type = 'hydration' AND DATE(recorded_at) BETWEEN ? AND ?
         GROUP BY DATE(recorded_at)"
    );
    if ($hyVr) {
        $hyVr->bind_param("iss", $userId, $weekStart, $weekEnd);
        $hyVr->execute();
        $hyVrRes = $hyVr->get_result();
        while ($hRow = $hyVrRes->fetch_assoc()) {
            $d = $hRow["h_day"] ?? "";
            if (!empty($d) && isset($hydrationByDay[$d])) {
                if ($hydrationByDay[$d] <= 0) {
                    $hydrationByDay[$d] = (int)$hRow["daily_ml"];
                }
            }
        }
        $hyVr->close();
    }
} catch (\Exception $e) {}

$weeklyTotalHydrationMl = 0;
$loggedHydrationDays = 0;
$hydrationDays = [];

foreach ($hydrationByDay as $dateStr => $dayMl) {
    $dayLabel = date('D', strtotime($dateStr));
    if ($dayMl > 0) {
        $weeklyTotalHydrationMl += $dayMl;
        $loggedHydrationDays++;
    }
    $hydrationDays[] = [
        "date" => $dateStr,
        "day_label" => $dayLabel,
        "intake_ml" => $dayMl
    ];
}

// All-time fallback if rolling 7 days was empty
if ($weeklyTotalHydrationMl === 0) {
    $hyFb = $conn->prepare("SELECT COALESCE(SUM(COALESCE(amount_ml, volume_ml, intake_ml, 0)), 0) AS total_ml FROM hydration_logs WHERE user_id = ?");
    if ($hyFb) {
        $hyFb->bind_param("i", $userId);
        $hyFb->execute();
        $hyFbRes = $hyFb->get_result()->fetch_assoc();
        $hyFb->close();
        $fbTotal = (int)($hyFbRes["total_ml"] ?? 0);
        if ($fbTotal > 0) {
            $weeklyTotalHydrationMl = $fbTotal;
            $loggedHydrationDays = 1;
            $lastIdx = count($hydrationDays) - 1;
            $hydrationDays[$lastIdx]["intake_ml"] = $fbTotal;
        }
    }
}

$weeklyAvgHydrationMl = $loggedHydrationDays > 0
    ? (int)round($weeklyTotalHydrationMl / $loggedHydrationDays)
    : ($weeklyTotalHydrationMl > 0 ? (int)round($weeklyTotalHydrationMl / 7) : 0);
$calendarAvgHydrationMl = (int)round($weeklyTotalHydrationMl / 7);

$hydrationPercent = $weeklyWaterGoal > 0 ? min(100, (int)round(($weeklyTotalHydrationMl / (float)$weeklyWaterGoal) * 100)) : 0;

$hydrationScore = 0;
$hydrationStatus = "No Hydration Logged";
if ($weeklyAvgHydrationMl > 0 || $weeklyTotalHydrationMl > 0) {
    if ($weeklyAvgHydrationMl >= $dailyWaterGoal || $weeklyTotalHydrationMl >= $weeklyWaterGoal) {
        $hydrationScore = 100;
        $hydrationStatus = "Optimal Hydration";
    } elseif ($weeklyAvgHydrationMl >= round($dailyWaterGoal * 0.75) || $hydrationPercent >= 75) {
        $hydrationScore = 80;
        $hydrationStatus = "Good / Near Target";
    } elseif ($weeklyAvgHydrationMl >= round($dailyWaterGoal * 0.50) || $hydrationPercent >= 50) {
        $hydrationScore = 65;
        $hydrationStatus = "Moderate Deficit";
    } else {
        $hydrationScore = 40;
        $hydrationStatus = "Critically Dehydrated";
    }
}

// -------------------------------------------------------------------------
// 7. Steps & Physical Activity (7-Day Weekly Basis: Group raw logs by calendar date first)
// -------------------------------------------------------------------------
$stepsByDay = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $dayKey = date('Y-m-d', strtotime("-{$offset} days"));
    $stepsByDay[$dayKey] = 0;
}

$workoutCount = 0;

// A. workout_sessions
$wsStmt = $conn->prepare(
    "SELECT DATE(COALESCE(NULLIF(start_time, ''), date_string, created_at)) as log_date,
            SUM(steps) as day_steps, COUNT(*) as cnt
     FROM workout_sessions
     WHERE user_id = ?
       AND DATE(COALESCE(NULLIF(start_time, ''), date_string, created_at)) BETWEEN ? AND ?
     GROUP BY DATE(COALESCE(NULLIF(start_time, ''), date_string, created_at))"
);
if ($wsStmt) {
    $wsStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $wsStmt->execute();
    $wsRes = $wsStmt->get_result();
    while ($r = $wsRes->fetch_assoc()) {
        $d = $r["log_date"] ?? "";
        if (!empty($d) && isset($stepsByDay[$d])) {
            $stepsByDay[$d] = max($stepsByDay[$d], (int)$r["day_steps"]);
            $workoutCount += (int)$r["cnt"];
        }
    }
    $wsStmt->close();
}

// B. activity_sessions
$asStmt = $conn->prepare(
    "SELECT DATE(start_time) as log_date, SUM(steps) as day_steps, COUNT(*) as cnt
     FROM activity_sessions
     WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?
     GROUP BY DATE(start_time)"
);
if ($asStmt) {
    $asStmt->bind_param("iss", $userId, $weekStart, $weekEnd);
    $asStmt->execute();
    $asRes = $asStmt->get_result();
    while ($r = $asRes->fetch_assoc()) {
        $d = $r["log_date"] ?? "";
        if (!empty($d) && isset($stepsByDay[$d])) {
            $stepsByDay[$d] = max($stepsByDay[$d], (int)$r["day_steps"]);
            if ($workoutCount === 0) $workoutCount += (int)$r["cnt"];
        }
    }
    $asStmt->close();
}

// C. steps_logs
$chkSteps = $conn->query("SHOW TABLES LIKE 'steps_logs'");
if ($chkSteps && $chkSteps->num_rows > 0) {
    $slq = $conn->prepare(
        "SELECT logged_date as log_date, SUM(step_count) as day_steps
         FROM steps_logs
         WHERE user_id = ? AND logged_date BETWEEN ? AND ?
         GROUP BY logged_date"
    );
    if ($slq) {
        $slq->bind_param("iss", $userId, $weekStart, $weekEnd);
        $slq->execute();
        $slRes = $slq->get_result();
        while ($r = $slRes->fetch_assoc()) {
            $d = $r["log_date"] ?? "";
            if (!empty($d) && isset($stepsByDay[$d])) {
                $stepsByDay[$d] = max($stepsByDay[$d], (int)$r["day_steps"]);
            }
        }
        $slq->close();
    }
}

// D. vitals_readings (reading_type = 'steps')
$vrStepsQ = $conn->prepare(
    "SELECT DATE(recorded_at) as log_date, SUM(value_numeric) as day_steps
     FROM vitals_readings
     WHERE user_id = ? AND reading_type = 'steps' AND DATE(recorded_at) BETWEEN ? AND ?
     GROUP BY DATE(recorded_at)"
);
if ($vrStepsQ) {
    $vrStepsQ->bind_param("iss", $userId, $weekStart, $weekEnd);
    $vrStepsQ->execute();
    $vrRes = $vrStepsQ->get_result();
    while ($r = $vrRes->fetch_assoc()) {
        $d = $r["log_date"] ?? "";
        if (!empty($d) && isset($stepsByDay[$d])) {
            $stepsByDay[$d] = max($stepsByDay[$d], (int)round((float)$r["day_steps"]));
        }
    }
    $vrStepsQ->close();
}

$weeklyTotalSteps = 0;
$loggedStepDays = 0;
foreach ($stepsByDay as $dateStr => $daySteps) {
    if ($daySteps > 0) {
        $weeklyTotalSteps += $daySteps;
        $loggedStepDays++;
    }
}

// All-time fallback if rolling 7-day was empty
if ($weeklyTotalSteps === 0) {
    $wsFb = $conn->prepare("SELECT COALESCE(SUM(steps), 0) AS total_steps, COUNT(*) AS cnt FROM workout_sessions WHERE user_id = ? AND steps > 0");
    if ($wsFb) {
        $wsFb->bind_param("i", $userId);
        $wsFb->execute();
        $wsFbRes = $wsFb->get_result()->fetch_assoc();
        $wsFb->close();
        $s1 = (int)($wsFbRes["total_steps"] ?? 0);
        if ($workoutCount === 0) $workoutCount = (int)($wsFbRes["cnt"] ?? 0);
    }
    $asFb = $conn->prepare("SELECT COALESCE(SUM(steps), 0) AS total_steps, COUNT(*) AS cnt FROM activity_sessions WHERE user_id = ? AND steps > 0");
    if ($asFb) {
        $asFb->bind_param("i", $userId);
        $asFb->execute();
        $asFbRes = $asFb->get_result()->fetch_assoc();
        $asFb->close();
        $s2 = (int)($asFbRes["total_steps"] ?? 0);
        if ($workoutCount === 0) $workoutCount = (int)($asFbRes["cnt"] ?? 0);
    }
    $weeklyTotalSteps = max($s1 ?? 0, $s2 ?? 0);
    if ($weeklyTotalSteps > 0) {
        $loggedStepDays = 1;
        $lastIdx = date('Y-m-d');
        if (isset($stepsByDay[$lastIdx])) {
            $stepsByDay[$lastIdx] = $weeklyTotalSteps;
        }
    }
}

$weeklyAvgSteps = $loggedStepDays > 0
    ? (int)round($weeklyTotalSteps / $loggedStepDays)
    : ($weeklyTotalSteps > 0 ? (int)round($weeklyTotalSteps / 7) : 0);
$calendarAvgSteps = (int)round($weeklyTotalSteps / 7);

$stepsPercent = $weeklyStepGoal > 0 ? min(100, (int)round(($weeklyTotalSteps / (float)$weeklyStepGoal) * 100)) : 0;

$activityScore = 0;
$activityStatus = "No Activity Logged";
if ($weeklyAvgSteps > 0 || $weeklyTotalSteps > 0) {
    if ($weeklyAvgSteps >= 8000 || $stepsPercent >= 80) {
        $activityScore = 100;
        $activityStatus = "Active / Optimal";
    } elseif ($weeklyAvgSteps >= 5000 || $stepsPercent >= 50) {
        $activityScore = 75;
        $activityStatus = "Moderate Activity";
    } else {
        $activityScore = 50;
        $activityStatus = "Sedentary / Low";
    }
}

$totalDistanceKm = round($weeklyTotalSteps * 0.00078, 2);
$totalCaloriesBurned = (int)round($weeklyTotalSteps * 0.045);

// -------------------------------------------------------------------------
// 8. 7-Day Daily Breakdown Series Array
// -------------------------------------------------------------------------
$dailyBreakdown = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $curDay = date('Y-m-d', strtotime("-{$offset} days"));
    $dayLabel = date('D', strtotime($curDay));

    $daySteps = $stepsByDay[$curDay] ?? 0;
    $dayHydration = $hydrationByDay[$curDay] ?? 0;
    $daySleepMins = isset($sleepByDay[$curDay]["duration_minutes"]) ? (int)$sleepByDay[$curDay]["duration_minutes"] : 0;
    $daySleepHours = round($daySleepMins / 60.0, 1);

    // Calculate a daily wellness mini-score
    $dScores = [];
    if ($dailyStepGoal > 0) $dScores[] = min(100, (int)round(($daySteps / (float)$dailyStepGoal) * 100));
    if ($dailyWaterGoal > 0) $dScores[] = min(100, (int)round(($dayHydration / (float)$dailyWaterGoal) * 100));
    if ($targetSleepMinutes > 0 && $daySleepMins > 0) $dScores[] = min(100, (int)round(($daySleepMins / (float)$targetSleepMinutes) * 100));
    $dayScore = count($dScores) > 0 ? (int)round(array_sum($dScores) / count($dScores)) : 0;

    $dailyBreakdown[] = [
        "date" => $curDay,
        "day_label" => $dayLabel,
        "steps_total" => $daySteps,
        "hydration_ml" => $dayHydration,
        "sleep_minutes" => $daySleepMins,
        "sleep_hours" => $daySleepHours,
        "daily_score" => $dayScore
    ];
}

// -------------------------------------------------------------------------
// 9. Composite Weekly Wellness Score (0–100)
// -------------------------------------------------------------------------
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
$wellnessStatus = ($wellnessScore >= 80) ? "Optimal" : (($wellnessScore >= 50) ? "Good Progress" : ($wellnessScore > 0 ? "Needs Attention" : "No Data"));

// -------------------------------------------------------------------------
// 10. Vital Performance Breakdown (Strictly 7-Day Weekly Aggregated Formats)
// -------------------------------------------------------------------------
$vitalPerformanceBreakdown = [
    "overall_wellness_score" => $wellnessScore,
    "overall_status" => $wellnessStatus,
    "heart_rate" => [
        "value" => $avgHr !== null ? "{$avgHr} BPM (7-Day Avg)" : "-- BPM",
        "status" => $hrCategory,
        "score" => $hrScore,
        "avg_bpm" => $avgHr,
        "min_bpm" => $minHr,
        "max_bpm" => $maxHr
    ],
    "blood_pressure" => [
        "value" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg (7-Day Avg)" : "--/--",
        "status" => $bpCategory,
        "score" => $bpScore,
        "avg_sys" => $avgSys,
        "avg_dia" => $avgDia
    ],
    "spo2" => [
        "value" => $avgSpo2 !== null ? "{$avgSpo2}% (7-Day Avg)" : "--%",
        "status" => $spo2Category,
        "score" => $spo2Score,
        "avg_spo2" => $avgSpo2
    ],
    "sleep" => [
        "value" => $weeklyAvgSleepHrs > 0 ? "{$weeklyAvgSleepHrs} hrs/night • {$sleepQuality}" : "--",
        "status" => $weeklySleepFormatted,
        "score" => $sleepScore,
        "duration_minutes" => $weeklyAvgSleepMins,
        "total_hours" => $weeklyTotalSleepHrs,
        "avg_hours" => $weeklyAvgSleepHrs,
        "completion_percent" => $sleepPercent
    ],
    "hydration" => [
        "value" => number_format($weeklyTotalHydrationMl) . " ml total (" . number_format($weeklyAvgHydrationMl) . " ml/day)",
        "status" => "{$hydrationPercent}% of goal",
        "score" => $hydrationScore,
        "total_ml" => $weeklyTotalHydrationMl,
        "daily_avg_ml" => $weeklyAvgHydrationMl,
        "goal_ml" => $weeklyWaterGoal,
        "completion_percent" => $hydrationPercent
    ],
    "activity" => [
        "value" => number_format($weeklyTotalSteps) . " steps total (" . number_format($weeklyAvgSteps) . " steps/day)",
        "status" => "{$stepsPercent}% of goal",
        "score" => $activityScore,
        "total_steps" => $weeklyTotalSteps,
        "daily_avg_steps" => $weeklyAvgSteps,
        "goal_steps" => $weeklyStepGoal,
        "completion_percent" => $stepsPercent,
        "distance_km" => $totalDistanceKm,
        "calories_kcal" => $totalCaloriesBurned
    ]
];

// -------------------------------------------------------------------------
// 11. "Your Weekly Trends" Structured Payload (For AI Screen)
// -------------------------------------------------------------------------
$weeklyTrends = [
    "heart_rate" => [
        "avg_bpm" => ($avgHr !== null) ? (int)$avgHr : 0,
        "min_bpm" => $minHr,
        "max_bpm" => $maxHr,
        "formatted_value" => ($avgHr !== null && $avgHr > 0)
            ? "{$avgHr} bpm wk avg ({$minHr}–{$maxHr} bpm) • {$hrCategory}"
            : "-- bpm wk avg",
        "title_value" => ($avgHr !== null && $avgHr > 0) ? "{$avgHr} bpm" : "-- bpm",
        "subtitle" => ($avgHr !== null && $avgHr > 0)
            ? "wk avg ({$minHr}–{$maxHr} bpm) • {$hrCategory}"
            : "No readings yet",
        "status" => $hrCategory,
        "score" => $hrScore
    ],
    "activity" => [
        "total_steps" => $weeklyTotalSteps,
        "avg_steps" => $weeklyAvgSteps,
        "goal_steps" => $weeklyStepGoal,
        "completion_percent" => $stepsPercent,
        "workout_count" => $workoutCount,
        "formatted_value" => number_format($weeklyTotalSteps) . " total steps (" . number_format($weeklyAvgSteps) . "/day avg) • {$stepsPercent}% of wk goal",
        "title_value" => number_format($weeklyTotalSteps) . " steps",
        "subtitle" => "(" . number_format($weeklyAvgSteps) . "/day avg) • {$stepsPercent}% of wk goal",
        "status" => $activityStatus,
        "score" => $activityScore
    ],
    "sleep" => [
        "total_hours" => $weeklyTotalSleepHrs,
        "avg_hours" => $weeklyAvgSleepHrs,
        "avg_minutes" => $weeklyAvgSleepMins,
        "formatted_value" => ($weeklyAvgSleepHrs > 0)
            ? "{$weeklySleepShort} ({$weeklyTotalSleepHrs}h wk total) • {$sleepQuality}"
            : "No sleep logged this week",
        "title_value" => ($weeklyAvgSleepHrs > 0) ? "{$weeklyAvgSleepHrs} hrs/night" : "--",
        "subtitle" => ($weeklyAvgSleepHrs > 0) ? "({$weeklyTotalSleepHrs}h wk total) • {$sleepQuality}" : "No logs yet",
        "status" => $sleepQuality,
        "score" => $sleepScore
    ],
    "hydration" => [
        "total_ml" => $weeklyTotalHydrationMl,
        "avg_ml" => $weeklyAvgHydrationMl,
        "goal_ml" => $weeklyWaterGoal,
        "completion_percent" => $hydrationPercent,
        "formatted_value" => number_format($weeklyTotalHydrationMl) . " ml wk total (" . number_format($weeklyAvgHydrationMl) . " ml/day) • {$hydrationPercent}% of goal",
        "title_value" => ($weeklyTotalHydrationMl >= 1000) ? round($weeklyTotalHydrationMl / 1000.0, 1) . " L" : "{$weeklyTotalHydrationMl} ml",
        "subtitle" => "(" . number_format($weeklyAvgHydrationMl) . " ml/day) • {$hydrationPercent}% of goal",
        "status" => $hydrationStatus,
        "score" => $hydrationScore
    ],
    "spo2" => [
        "avg_spo2" => $avgSpo2,
        "formatted_value" => ($avgSpo2 !== null) ? "{$avgSpo2}% wk avg • {$spo2Category}" : "--% wk avg",
        "status" => $spo2Category,
        "score" => $spo2Score
    ],
    "blood_pressure" => [
        "avg_sys" => $avgSys,
        "avg_dia" => $avgDia,
        "formatted_value" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg wk avg • {$bpCategory}" : "--/-- wk avg",
        "status" => $bpCategory,
        "score" => $bpScore
    ]
];

// -------------------------------------------------------------------------
// 12. Ollama Executive Summary Generation & Fallback
// -------------------------------------------------------------------------
$hrValStr = $avgHr !== null ? "{$avgHr} BPM" : "-- BPM";
$bpValStr = ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg" : "--/--";
$spo2ValStr = $avgSpo2 !== null ? "{$avgSpo2}%" : "--%";
$stepsFormatted = number_format($weeklyTotalSteps);
$stepsAvgFormatted = number_format($weeklyAvgSteps);
$hydFormatted = number_format($weeklyTotalHydrationMl);
$hydAvgFormatted = number_format($weeklyAvgHydrationMl);

$breakdownPrompt = "Generate a concise 3-4 sentence clinical Executive Summary and 3 specific Action Recommendations based strictly on this patient's 7-DAY WEEKLY AGGREGATED vitals:\n" .
    "- Overall Weekly Wellness Score: {$wellnessScore}/100 ({$wellnessStatus})\n" .
    "- Heart Rate: {$hrValStr} (7-Day Avg, {$minHr}-{$maxHr} BPM) - Status: {$hrCategory}\n" .
    "- Blood Oxygen (SpO2): {$spo2ValStr} (7-Day Avg) - Status: {$spo2Category}\n" .
    "- Blood Pressure: {$bpValStr} (7-Day Avg) - Status: {$bpCategory}\n" .
    "- Physical Activity: {$stepsFormatted} total steps ({$stepsAvgFormatted} steps/day, {$stepsPercent}% of weekly goal)\n" .
    "- Hydration Intake: {$hydFormatted} ml weekly total ({$hydAvgFormatted} ml/day, {$hydrationPercent}% of weekly goal)\n" .
    "- Sleep Tracking: {$weeklySleepFormatted} - {$sleepQuality}\n\n" .
    "Rules:\n" .
    "1. Cite the exact 7-day weekly aggregated numbers ({$wellnessScore}/100, {$hrValStr}, {$bpValStr}, {$stepsFormatted} steps, {$hydFormatted} ml, {$weeklyTotalSleepHrs} hrs).\n" .
    "2. State which metrics met targets and which pulled the weekly score down.\n" .
    "3. Output valid JSON: {\"executive_summary\": \"...\", \"key_findings\": [\"...\", \"...\"], \"recommendations\": [\"...\", \"...\"]}";

$fallbackSummary = "Executive Summary: Overall 7-day weekly health scored {$wellnessScore}/100 ({$wellnessStatus}). " .
    "Resting heart rate averaged {$hrValStr} ({$hrCategory}) and blood pressure averaged {$bpValStr} ({$bpCategory}). " .
    "Respiratory oxygenation averaged {$spo2ValStr} SpO2 ({$spo2Category}). " .
    "Weekly lifestyle metrics recorded {$stepsFormatted} total steps ({$stepsAvgFormatted} steps/day, {$stepsPercent}% of goal), " .
    "{$hydFormatted} ml weekly hydration ({$hydAvgFormatted} ml/day, {$hydrationPercent}% of goal), and " .
    "{$weeklySleepFormatted} ({$sleepQuality}).";

$keyFindings = [];
if ($hrScore > 0) $keyFindings[] = "Heart Rate: {$avgHr} BPM (7-Day Avg) • {$hrCategory}";
if ($bpScore > 0) $keyFindings[] = "Blood Pressure: {$avgSys}/{$avgDia} mmHg (7-Day Avg) • {$bpCategory}";
if ($spo2Score > 0) $keyFindings[] = "SpO2: {$avgSpo2}% (7-Day Avg) • {$spo2Category}";
if ($weeklyAvgSleepHrs > 0) $keyFindings[] = "Sleep: {$weeklySleepShort} • {$sleepQuality}";
if ($weeklyTotalHydrationMl > 0) $keyFindings[] = "Hydration: {$hydFormatted} ml total ({$hydAvgFormatted} ml/day) • {$hydrationStatus}";
if ($weeklyTotalSteps > 0) $keyFindings[] = "Activity: {$stepsFormatted} total steps ({$stepsAvgFormatted} steps/day) • {$activityStatus}";

$recommendations = [];
if ($spo2Score < 70 && $spo2Score > 0) $recommendations[] = "Prioritize well-ventilated environments and check oxygenation levels regularly.";
if ($bpScore < 70 && $bpScore > 0) $recommendations[] = "Monitor blood pressure consistently and maintain balanced hydration and electrolyte levels.";
if ($activityScore < 70) $recommendations[] = "Increase daily walking to close the weekly step deficit.";
if ($hydrationScore < 70) $recommendations[] = "Drink water consistently throughout the day to meet the 2,500 ml/day baseline.";
if (empty($recommendations)) {
    $recommendations[] = "Maintain your current optimal weekly routine across cardiovascular and lifestyle vitals.";
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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

// -------------------------------------------------------------------------
// 13. Persist into weekly_health_reports table
// -------------------------------------------------------------------------
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

$executiveSummary = [
    "summary" => $summaryText,
    "key_findings" => $keyFindings,
    "recommendations" => $recommendations
];

respondJson(200, [
    "status" => "success",
    "success" => true,
    "message" => "7-day weekly health report computed successfully",
    "data" => [
        "week_start_date" => $weekStart,
        "week_end_date" => $weekEnd,
        "wellness_score" => $wellnessScore,
        "performance_category" => $performanceCategory,
        "category_label" => $wellnessStatus,
        "average_duration_minutes" => $weeklyAvgSleepMins,
        "average_sleep_quality" => $sleepScore,
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
        "daily_breakdown" => $dailyBreakdown,
        "weekly_series" => $dailyBreakdown,
        "metrics" => [
            "heart_rate_avg" => $avgHr !== null ? "{$avgHr} BPM (7-Day Avg)" : "-- BPM",
            "heart_rate_status" => $hrCategory,
            "avg_hr_bpm" => $avgHr !== null ? (int)$avgHr : null,
            "min_hr_bpm" => $minHr,
            "max_hr_bpm" => $maxHr,
            "bp_avg" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg (7-Day Avg)" : "--/--",
            "bp_status" => $bpCategory,
            "avg_sys" => $avgSys,
            "avg_dia" => $avgDia,
            "spo2_avg" => $avgSpo2 !== null ? "{$avgSpo2}% (7-Day Avg)" : "--%",
            "spo2_status" => $spo2Category,
            "sleep_quality" => $weeklyAvgSleepHrs > 0 ? "{$weeklyAvgSleepHrs} hrs/night • {$sleepQuality}" : "--",
            "sleep_status" => $weeklySleepFormatted,
            "avg_sleep_minutes" => $weeklyAvgSleepMins,
            "average_duration_minutes" => $weeklyAvgSleepMins,
            "weekly_total_sleep_hours" => $weeklyTotalSleepHrs,
            "weekly_avg_sleep_hours" => $weeklyAvgSleepHrs,
            "average_sleep_quality" => $sleepScore,
            "sleep_score" => $sleepScore,
            "hydration_total" => number_format($weeklyTotalHydrationMl) . " ml total (" . number_format($weeklyAvgHydrationMl) . " ml/day)",
            "hydration_status" => "{$hydrationPercent}% of goal",
            "total_hydration_ml" => $weeklyTotalHydrationMl,
            "weekly_total_hydration_ml" => $weeklyTotalHydrationMl,
            "weekly_avg_hydration_ml" => $weeklyAvgHydrationMl,
            "activity_steps" => number_format($weeklyTotalSteps) . " steps total (" . number_format($weeklyAvgSteps) . " steps/day)",
            "activity_status" => "{$stepsPercent}% of goal",
            "total_steps" => $weeklyTotalSteps,
            "weekly_total_steps" => $weeklyTotalSteps,
            "weekly_avg_steps" => $weeklyAvgSteps,
            "workout_count" => $workoutCount
        ],
        "weekly_trends" => $weeklyTrends,
        "heart_rate_avg" => $avgHr !== null ? "{$avgHr} BPM (7-Day Avg)" : "-- BPM",
        "hydration_total_ml" => $weeklyTotalHydrationMl,
        "steps_total" => $weeklyTotalSteps,
        "sleep_hours" => $weeklyAvgSleepHrs,
        "sleep_formatted" => $weeklySleepFormatted,
        "sleep_quality" => $sleepQuality,
        "sleep_score" => $sleepScore,
        "vital_performance_breakdown" => $vitalPerformanceBreakdown,
        "executive_summary" => $executiveSummary,
        "summary_text" => $summaryText
    ]
]);
