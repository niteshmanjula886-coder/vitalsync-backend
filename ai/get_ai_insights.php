<?php

date_default_timezone_set("Asia/Kolkata");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tunnel-Skip-Anti-Abuse");

if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

set_time_limit(35);

require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/ai_service.php";

if (!defined("OLLAMA_BASE_URL")) define("OLLAMA_BASE_URL", "http://127.0.0.1:11434");
if (!defined("OLLAMA_MODEL")) define("OLLAMA_MODEL", "qwen2.5:1.5b");

$userId = 0;
if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) && (int)$_GET["user_id"] > 0) {
    $userId = (int)$_GET["user_id"];
} elseif (isset($_POST["user_id"]) && is_numeric($_POST["user_id"]) && (int)$_POST["user_id"] > 0) {
    $userId = (int)$_POST["user_id"];
} else {
    $userId = resolveUserIdOrDie($conn, false);
}

// 1. Resolve is_bt_connected parameter
$isBtConnected = false;
$btParam = $_GET["is_bt_connected"] ?? $_POST["is_bt_connected"] ?? null;
if ($btParam !== null) {
    if (is_bool($btParam)) {
        $isBtConnected = $btParam;
    } else {
        $lower = strtolower(trim((string)$btParam));
        $isBtConnected = in_array($lower, ["1", "true", "yes", "on", "connected"], true);
    }
}

// 2. Fetch User Profile Context
$userName = "there";
$uStmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
if ($uStmt) {
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $uRes = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();
    if (!empty($uRes["name"])) {
        $userName = explode(" ", trim($uRes["name"]))[0];
    }
}

// 3. Resolve Today's Hydration
// Prioritize actual live telemetry passed directly from the client ViewModel
$hydrationParam = isset($_GET["hydration_ml"]) ? (int)$_GET["hydration_ml"] : (isset($_POST["hydration_ml"]) ? (int)$_POST["hydration_ml"] : null);
$targetWaterParam = isset($_GET["target_water_ml"]) ? (int)$_GET["target_water_ml"] : (isset($_POST["target_water_ml"]) ? (int)$_POST["target_water_ml"] : null);

$todayMl = 0;
$targetWaterMl = ($targetWaterParam !== null && $targetWaterParam > 0) ? $targetWaterParam : 2500;

if ($hydrationParam !== null && $hydrationParam >= 0) {
    $todayMl = $hydrationParam;
} else {
    // Sum today's entries directly from hydration_logs using DATE(created_at) = CURDATE()
    $hStmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount_ml), 0) AS today_ml,
                COALESCE(MAX(target_ml), 2500) AS target_ml
         FROM hydration_logs 
         WHERE user_id = ? AND DATE(created_at) = CURDATE()"
    );
    if ($hStmt) {
        $hStmt->bind_param("i", $userId);
        $hStmt->execute();
        $hRes = $hStmt->get_result()->fetch_assoc();
        $hStmt->close();
        if ($hRes && (int)$hRes["today_ml"] > 0) {
            $todayMl = (int)$hRes["today_ml"];
            if ($targetWaterParam === null && !empty($hRes["target_ml"]) && (int)$hRes["target_ml"] > 0) {
                $targetWaterMl = (int)$hRes["target_ml"];
            }
        }
    }

    // Fallback: Check other date columns or recent log if 0
    if ($todayMl === 0) {
        $hStmt2 = $conn->prepare(
            "SELECT COALESCE(SUM(COALESCE(amount_ml, volume_ml, intake_ml, 0)), 0) AS today_ml,
                    COALESCE(MAX(target_ml), 2500) AS target_ml
             FROM hydration_logs 
             WHERE user_id = ? AND (log_date = CURDATE() OR logged_date = CURDATE() OR DATE(logged_at) = CURDATE())"
        );
        if ($hStmt2) {
            $hStmt2->bind_param("i", $userId);
            $hStmt2->execute();
            $hRes2 = $hStmt2->get_result()->fetch_assoc();
            $hStmt2->close();
            if ($hRes2 && (int)$hRes2["today_ml"] > 0) {
                $todayMl = (int)$hRes2["today_ml"];
                if ($targetWaterParam === null && !empty($hRes2["target_ml"]) && (int)$hRes2["target_ml"] > 0) {
                    $targetWaterMl = (int)$hRes2["target_ml"];
                }
            }
        }
    }
}

// 4. Resolve Recent Sleep
// Prioritize actual live sleep text and score passed directly from the client ViewModel
$sleepTextParam = isset($_GET["sleep_text"]) ? trim($_GET["sleep_text"]) : (isset($_POST["sleep_text"]) ? trim($_POST["sleep_text"]) : null);
$sleepMinutesParam = isset($_GET["sleep_minutes"]) ? (int)$_GET["sleep_minutes"] : (isset($_POST["sleep_minutes"]) ? (int)$_POST["sleep_minutes"] : null);
$sleepScoreParam = isset($_GET["sleep_score"]) ? (int)$_GET["sleep_score"] : (isset($_POST["sleep_score"]) ? (int)$_POST["sleep_score"] : null);

$sleepDurationMinutes = 0;
$sleepQualityScore = null;
$sleepFormatted = "No sleep logged";

if ($sleepMinutesParam !== null && $sleepMinutesParam > 0) {
    $sleepDurationMinutes = $sleepMinutesParam;
}

if (!empty($sleepTextParam) && $sleepTextParam !== "--" && strtolower($sleepTextParam) !== "no data") {
    $sleepFormatted = $sleepTextParam;
    if ($sleepDurationMinutes <= 0) {
        if (preg_match('/(\d+)\s*h(?:ours?)?\s*(?:(\d+)\s*m)?/i', $sleepTextParam, $m)) {
            $sleepDurationMinutes = ((int)$m[1] * 60) + (isset($m[2]) ? (int)$m[2] : 0);
        } elseif (preg_match('/^(\d+(?:\.\d+)?)\s*h/i', $sleepTextParam, $m)) {
            $sleepDurationMinutes = (int)round(((float)$m[1]) * 60);
        }
    }
    if ($sleepScoreParam !== null && $sleepScoreParam > 0) {
        $sleepQualityScore = $sleepScoreParam;
    }
} else {
    // Query today's / last-night's entry from sleep_logs
    $sStmt = $conn->prepare(
        "SELECT COALESCE(NULLIF(duration_minutes, 0), TIMESTAMPDIFF(MINUTE, start_time, end_time), 0) AS dur_mins,
                quality_score, logged_at
         FROM sleep_logs
         WHERE user_id = ?
           AND DATE(COALESCE(logged_at, logged_date, created_at, start_time)) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
         ORDER BY COALESCE(logged_at, created_at, start_time) DESC, id DESC LIMIT 1"
    );
    if ($sStmt) {
        $sStmt->bind_param("i", $userId);
        $sStmt->execute();
        $sRes = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();
        if ($sRes && (int)$sRes["dur_mins"] > 0) {
            $sleepDurationMinutes = (int)$sRes["dur_mins"];
            $sleepQualityScore = $sRes["quality_score"] !== null ? (int)$sRes["quality_score"] : null;
            $h = floor($sleepDurationMinutes / 60);
            $m = $sleepDurationMinutes % 60;
            $sleepFormatted = "{$h}h {$m}m";
        }
    }

    // Fallback: Check sleep_records if sleep_logs was empty
    if ($sleepFormatted === "No sleep logged") {
        $srStmt = $conn->prepare(
            "SELECT duration_minutes, sleep_score
             FROM sleep_records
             WHERE user_id = ?
               AND date_string >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
             ORDER BY date_string DESC, id DESC LIMIT 1"
        );
        if ($srStmt) {
            $srStmt->bind_param("i", $userId);
            $srStmt->execute();
            $srRes = $srStmt->get_result()->fetch_assoc();
            $srStmt->close();
            if ($srRes && !empty($srRes["duration_minutes"])) {
                $dur = (int)$srRes["duration_minutes"];
                $sleepDurationMinutes = $dur;
                $h = floor($dur / 60);
                $m = $dur % 60;
                $sleepFormatted = "{$h}h {$m}m";
                $sleepQualityScore = isset($srRes["sleep_score"]) ? (int)$srRes["sleep_score"] : null;
            }
        }
    }
}

// 5. Resolve Today's Steps and Step Goal
$stepsParam = isset($_GET["steps"]) ? (int)$_GET["steps"] : (isset($_POST["steps"]) ? (int)$_POST["steps"] : null);
$stepGoalParam = isset($_GET["step_goal"]) ? (int)$_GET["step_goal"] : (isset($_POST["step_goal"]) ? (int)$_POST["step_goal"] : null);
$todaySteps = 0;
$stepGoal = 8000;
if ($stepGoalParam !== null && $stepGoalParam > 0) {
    $stepGoal = $stepGoalParam;
} else {
    try {
        $agGoal = 0; $agUpdated = null;
        $agStmt = $conn->prepare("SELECT daily_step_goal, updated_at FROM activity_goals WHERE user_id = ? LIMIT 1");
        if ($agStmt) {
            $agStmt->bind_param("i", $userId);
            $agStmt->execute();
            $agRes = $agStmt->get_result()->fetch_assoc();
            $agStmt->close();
            if (!empty($agRes["daily_step_goal"]) && (int)$agRes["daily_step_goal"] > 0) {
                $agGoal = (int)$agRes["daily_step_goal"];
                $agUpdated = $agRes["updated_at"] ?? null;
            }
        }
        $hgGoal = 0; $hgUpdated = null;
        $sgStmt = $conn->prepare("SELECT target_steps, target_water_ml, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
        if ($sgStmt) {
            $sgStmt->bind_param("i", $userId);
            $sgStmt->execute();
            $sgRes = $sgStmt->get_result()->fetch_assoc();
            $sgStmt->close();
            if (!empty($sgRes["target_steps"]) && (int)$sgRes["target_steps"] > 0) {
                $hgGoal = (int)$sgRes["target_steps"];
                $hgUpdated = $sgRes["updated_at"] ?? null;
            }
            if ($targetWaterParam === null && $todayMl === 0 && !empty($sgRes["target_water_ml"]) && (int)$sgRes["target_water_ml"] > 0) {
                $targetWaterMl = (int)$sgRes["target_water_ml"];
            }
        }
        if ($agGoal > 0 && $hgGoal > 0) {
            $stepGoal = ($hgUpdated !== null && $agUpdated !== null && strtotime($hgUpdated) > strtotime($agUpdated)) ? $hgGoal : $agGoal;
        } elseif ($agGoal > 0) {
            $stepGoal = $agGoal;
        } elseif ($hgGoal > 0) {
            $stepGoal = $hgGoal;
        }
    } catch (\Exception $e) {}
}

if ($stepsParam !== null && $stepsParam >= 0) {
    $todaySteps = $stepsParam;
} else {
    $actStmt = $conn->prepare(
        "SELECT COALESCE(SUM(steps), 0) AS total_steps
         FROM activity_sessions
         WHERE user_id = ? AND DATE(start_time) = CURDATE()"
    );
    if ($actStmt) {
        $actStmt->bind_param("i", $userId);
        $actStmt->execute();
        $actRes = $actStmt->get_result()->fetch_assoc();
        $actStmt->close();
        $todaySteps = (int)($actRes["total_steps"] ?? 0);
    }
}

// 6. Fetch Real-Time / Today's Biometrics Only (NEVER inject fake defaults like 72 / 98 / 120/80)
$hrParam = isset($_GET["heart_rate"]) ? (int)$_GET["heart_rate"] : (isset($_POST["heart_rate"]) ? (int)$_POST["heart_rate"] : null);
$spo2Param = isset($_GET["spo2"]) ? (int)$_GET["spo2"] : (isset($_POST["spo2"]) ? (int)$_POST["spo2"] : null);
$bpParam = isset($_GET["blood_pressure"]) ? trim((string)$_GET["blood_pressure"]) : (isset($_POST["blood_pressure"]) ? trim((string)$_POST["blood_pressure"]) : null);

$heartRate = ($hrParam !== null && $hrParam > 0) ? $hrParam : null;
$spo2 = ($spo2Param !== null && $spo2Param > 0) ? $spo2Param : null;
$bloodPressure = (!empty($bpParam) && strpos($bpParam, "/") !== false && strpos($bpParam, "--") === false) ? $bpParam : null;

// Query vitals logged TODAY (from active Bluetooth device or Health Connect / manual log today)
if ($heartRate === null || $spo2 === null || $bloodPressure === null) {
    $vStmt = $conn->prepare(
        "SELECT heart_rate, spo2, systolic_bp, diastolic_bp, logged_at
         FROM vitals_logs
         WHERE user_id = ? AND DATE(logged_at) = CURDATE()
         ORDER BY logged_at DESC LIMIT 1"
    );
    if ($vStmt) {
        $vStmt->bind_param("i", $userId);
        $vStmt->execute();
        $vRes = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();
        if ($vRes) {
            if ($heartRate === null && !empty($vRes["heart_rate"]) && (int)$vRes["heart_rate"] > 0) {
                $heartRate = (int)$vRes["heart_rate"];
            }
            if ($spo2 === null && !empty($vRes["spo2"]) && (int)$vRes["spo2"] > 0) {
                $spo2 = (int)$vRes["spo2"];
            }
            if ($bloodPressure === null && !empty($vRes["systolic_bp"]) && !empty($vRes["diastolic_bp"]) && (int)$vRes["systolic_bp"] > 0 && (int)$vRes["diastolic_bp"] > 0) {
                $bloodPressure = (int)$vRes["systolic_bp"] . "/" . (int)$vRes["diastolic_bp"];
            }
        }
    }

    if ($heartRate === null) {
        $hrStmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1");
        if ($hrStmt) {
            $hrStmt->bind_param("i", $userId);
            $hrStmt->execute();
            $hrRes = $hrStmt->get_result()->fetch_assoc();
            $hrStmt->close();
            if ($hrRes && (float)$hrRes["value_numeric"] > 0) $heartRate = (int)$hrRes["value_numeric"];
        }
    }

    if ($spo2 === null) {
        $spStmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1");
        if ($spStmt) {
            $spStmt->bind_param("i", $userId);
            $spStmt->execute();
            $spRes = $spStmt->get_result()->fetch_assoc();
            $spStmt->close();
            if ($spRes && (float)$spRes["value_numeric"] > 0) $spo2 = (int)$spRes["value_numeric"];
        }
    }

    if ($bloodPressure === null) {
        $bpStmt = $conn->prepare("SELECT value_numeric, value_secondary FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1");
        if ($bpStmt) {
            $bpStmt->bind_param("i", $userId);
            $bpStmt->execute();
            $bpRes = $bpStmt->get_result()->fetch_assoc();
            $bpStmt->close();
            if ($bpRes && (float)$bpRes["value_numeric"] > 0 && (float)$bpRes["value_secondary"] > 0) {
                $bloodPressure = (int)$bpRes["value_numeric"] . "/" . (int)$bpRes["value_secondary"];
            }
        }
    }
}

$hasAnyBiometricsToday = ($heartRate !== null || $spo2 !== null || $bloodPressure !== null);

// 7. Compute Dynamic Home Wellness Score (0-100):
// round(min(35, (todaySteps/stepGoal)*35) + min(35, (todayWaterMl/waterGoalMl)*35) + min(30, (todaySleepMinutes/480)*30))
$stepsScore = ($stepGoal > 0) ? min(35.0, ($todaySteps / (float)$stepGoal) * 35.0) : 0.0;
$waterScore = ($targetWaterMl > 0) ? min(35.0, ($todayMl / (float)$targetWaterMl) * 35.0) : 0.0;
$sleepScoreVal = min(30.0, ($sleepDurationMinutes / 480.0) * 30.0);
$computedScore = max(0, min(100, (int)round($stepsScore + $waterScore + $sleepScoreVal)));

// Classify Sleep objectively using clinical evaluation tiers
$sleepCategory = "Not Logged";
$sleepStatus = "Not Logged";
$sleepTier = "Not Logged";
$sleepSeverity = 0; // 0 = Optimal/Neutral, 1 = Needs Improvement/Attention, 2 = Low/Alert
$sleepScoreNorm = 85.0;
$sleepPhrase = "No sleep has been logged yet today.";

if ($sleepDurationMinutes > 0) {
    $sleepHours = $sleepDurationMinutes / 60.0;
    if ($sleepHours >= 7.0) {
        $sleepCategory = ($sleepHours <= 8.5) ? "Optimal (7.0-8.5h)" : "Restorative (>8.5h)";
        $sleepStatus = "Fully Rested";
        $sleepTier = "Optimal";
        $sleepSeverity = 0;
        $sleepScoreNorm = min(100.0, ($sleepHours / 8.0) * 100.0);
        $sleepPhrase = "Sleep is Optimal ({$sleepStatus}) at {$sleepFormatted}.";
    } elseif ($sleepHours >= 5.5) {
        $sleepCategory = "Mild Deficit (5.5-6.9h)";
        $sleepStatus = "Mild Sleep Deficit";
        $sleepTier = "Needs Improvement";
        $sleepSeverity = 1;
        $sleepScoreNorm = ($sleepHours / 8.0) * 100.0;
        $sleepPhrase = "Sleep needs improvement ({$sleepStatus}) at {$sleepFormatted}, just shy of the 7.0 hr target.";
    } else {
        $sleepCategory = "Severe Deficit (<5.5h)";
        $sleepStatus = "Severe Sleep Deprivation";
        $sleepTier = "Low / Alert";
        $sleepSeverity = 2;
        $sleepScoreNorm = max(15.0, ($sleepHours / 8.0) * 100.0);
        $sleepPhrase = "Sleep is Critically Low ({$sleepStatus}) at {$sleepFormatted}, well below the 7.0 hr recovery threshold.";
    }
}

$sleepDescription = ($sleepDurationMinutes > 0)
    ? "{$sleepFormatted} [{$sleepStatus} - {$sleepTier}]"
    : "Not logged today";

// Classify Hydration against personalized target
$hydrationPctRaw = ($targetWaterMl > 0) ? (($todayMl / (float)$targetWaterMl) * 100.0) : 0.0;
$hydrationPct = (int)round($hydrationPctRaw);
$hydrationRemainingMl = max(0, $targetWaterMl - $todayMl);
$loggedWaterFmt = number_format($todayMl);
$targetWaterFmt = number_format($targetWaterMl);
$remWaterFmt = number_format($hydrationRemainingMl);

if ($hydrationPctRaw >= 80.0) {
    $hydrationStatus = "Well Hydrated";
    $hydrationTier = "Optimal";
    $hydrationSeverity = 0;
    $hydrationPhrase = ($hydrationPctRaw >= 100.0)
        ? "Hydration is Optimal ({$loggedWaterFmt} / {$targetWaterFmt} ml, {$hydrationPct}%)."
        : "Hydration is Optimal at {$loggedWaterFmt} / {$targetWaterFmt} ml ({$hydrationPct}%), with {$remWaterFmt} ml left to hit 100%.";
} elseif ($hydrationPctRaw >= 40.0) {
    $hydrationStatus = "Below Target";
    $hydrationTier = "Needs Improvement";
    $hydrationSeverity = 1;
    $hydrationPhrase = "Hydration needs improvement at {$loggedWaterFmt} / {$targetWaterFmt} ml ({$hydrationPct}%, {$remWaterFmt} ml remaining).";
} else {
    $hydrationStatus = "Critically Dehydrated";
    $hydrationTier = "Low / Alert";
    $hydrationSeverity = 2;
    $hydrationPhrase = "Hydration is Critically Low at {$loggedWaterFmt} / {$targetWaterFmt} ml ({$hydrationPct}%, {$remWaterFmt} ml deficit).";
}
$hydrationScoreNorm = min(100.0, $hydrationPctRaw);
$hydrationDescription = "{$loggedWaterFmt} / {$targetWaterFmt} ml ({$hydrationPct}%) [{$hydrationStatus} - {$hydrationTier}]";

// Classify Activity (Steps) against user's saved step_goal
$stepsPctRaw = ($stepGoal > 0) ? (($todaySteps / (float)$stepGoal) * 100.0) : 0.0;
$stepsPct = (int)round($stepsPctRaw);
$stepsFmt = number_format($todaySteps);
$stepGoalFmt = number_format($stepGoal);
$remSteps = max(0, $stepGoal - $todaySteps);
$remStepsFmt = number_format($remSteps);

if ($stepsPctRaw >= 70.0) {
    $stepsStatus = "Active Day";
    $stepsTier = "Optimal";
    $stepsSeverity = 0;
    $stepsPhrase = ($todaySteps >= $stepGoal)
        ? "Activity is Optimal with {$stepsFmt} / {$stepGoalFmt} steps completed ({$stepsPct}%)."
        : "Activity is Optimal at {$stepsFmt} / {$stepGoalFmt} steps ({$stepsPct}%), leaving {$remStepsFmt} steps to hit your full target.";
} elseif ($stepsPctRaw >= 30.0) {
    $stepsStatus = "Moderate Activity";
    $stepsTier = "Needs Improvement";
    $stepsSeverity = 1;
    $stepsPhrase = "Activity needs improvement at {$stepsFmt} / {$stepGoalFmt} steps ({$stepsPct}%), {$remStepsFmt} steps shy of goal.";
} else {
    $stepsStatus = "Sedentary / Lagging";
    $stepsTier = "Low";
    $stepsSeverity = 2;
    $stepsPhrase = "Activity is Low at {$stepsFmt} / {$stepGoalFmt} steps ({$stepsPct}%), with {$remStepsFmt} steps remaining today.";
}
$stepsScoreNorm = min(100.0, $stepsPctRaw);

// Build Metric Evaluation Matrix (Strictly omit unlogged biometrics from diagnostic claims)
$metricEvaluations = [];
$evaluatedForRanking = [];
$optimalClauses = [];
$concernClauses = [];
$hasClinicalAlert = false;

$addEvaluatedMetric = function (
    string $metricName,
    string $valueLabel,
    string $statusLabel,
    string $tierLabel,
    int $severity,
    float $normScore,
    string $summarySentence,
    string $actionInstruction,
    int $priorityWeight
) use (&$metricEvaluations, &$evaluatedForRanking, &$optimalClauses, &$concernClauses, &$hasClinicalAlert) {
    $metricEvaluations[] = [
        "metric" => $metricName,
        "value" => $valueLabel,
        "status" => $statusLabel,
        "tier" => $tierLabel,
        "severity" => $severity
    ];
    $evaluatedForRanking[] = [
        "metric" => $metricName,
        "tier" => $tierLabel,
        "severity" => $severity,
        "norm_score" => $normScore,
        "action" => $actionInstruction,
        "priority" => $priorityWeight
    ];
    if ($severity === 0) {
        $optimalClauses[] = $summarySentence;
    } else {
        $concernClauses[] = $summarySentence;
    }
    if ($tierLabel === "Alert" || $tierLabel === "Low / Alert") {
        $hasClinicalAlert = true;
    }
};

$telemetryLines = [];

if ($heartRate !== null) {
    if ($heartRate < 60) {
        $hrStatus = "Resting/Athletic";
        $hrTier = "Optimal";
        $hrSeverity = 0;
        $hrNorm = 95.0;
        $hrSentence = "Heart rate is Optimal ({$hrStatus}) at {$heartRate} bpm.";
        $hrAction = "Maintain your steady recovery pacing while keeping heart rate at {$heartRate} bpm.";
    } elseif ($heartRate <= 100) {
        $hrStatus = "Normal Resting";
        $hrTier = "Optimal";
        $hrSeverity = 0;
        $hrNorm = 92.0;
        $hrSentence = "Heart rate is Optimal ({$hrStatus}) at {$heartRate} bpm.";
        $hrAction = "Keep resting heart rate steady around {$heartRate} bpm with light movement and hydration.";
    } elseif ($heartRate <= 120) {
        $hrStatus = "Elevated";
        $hrTier = "Needs Attention";
        $hrSeverity = 1;
        $hrNorm = 55.0;
        $hrSentence = "Heart rate is Elevated at {$heartRate} bpm, signaling mild physiological strain or stress.";
        $hrAction = "Take 5 minutes of slow nasal breathing and rehydrate to bring your heart rate down from {$heartRate} bpm.";
    } else {
        $hrStatus = "High";
        $hrTier = "Alert";
        $hrSeverity = 2;
        $hrNorm = 20.0;
        $hrSentence = "Heart rate is High (Alert) at {$heartRate} bpm and warrants seated rest.";
        $hrAction = "Stop strenuous activity, rest quietly, hydrate slowly, and recheck your {$heartRate} bpm reading in 15 minutes.";
    }
    $addEvaluatedMetric("Heart Rate", "{$heartRate} bpm", $hrStatus, $hrTier, $hrSeverity, $hrNorm, $hrSentence, $hrAction, 3);
    $telemetryLines[] = "- Heart Rate: {$heartRate} bpm [Status: {$hrStatus} | Tier: {$hrTier}]";
}

if ($spo2 !== null) {
    if ($spo2 >= 95) {
        $spStatus = "Optimal Oxygenation";
        $spTier = "Optimal";
        $spSeverity = 0;
        $spNorm = 98.0;
        $spSentence = "Blood oxygen is Optimal at {$spo2}% SpO2.";
        $spAction = "Blood oxygen is optimal at {$spo2}%; continue steady nasal breathing during activity.";
    } elseif ($spo2 >= 90) {
        $spStatus = "Mild Hypoxia";
        $spTier = "Needs Attention";
        $spSeverity = 1;
        $spNorm = 50.0;
        $spSentence = "SpO2 shows Mild Hypoxia at {$spo2}%, slightly below the 95% optimal threshold.";
        $spAction = "Move to a well-ventilated space, practice deep diaphragmatic breathing, and recheck SpO2 from {$spo2}% to reach 95%+. ";
    } else {
        $spStatus = "Low Oxygen";
        $spTier = "Alert";
        $spSeverity = 2;
        $spNorm = 15.0;
        $spSentence = "SpO2 is critically low at {$spo2}% (Alert) and requires prompt rest and clinical monitoring.";
        $spAction = "Rest upright in fresh air immediately, perform slow deep breathing, and seek medical review if SpO2 remains below 92% (currently {$spo2}%).";
    }
    $addEvaluatedMetric("SpO2", "{$spo2}%", $spStatus, $spTier, $spSeverity, $spNorm, $spSentence, trim($spAction), 1);
    $telemetryLines[] = "- SpO2: {$spo2}% [Status: {$spStatus} | Tier: {$spTier}]";
}

if ($bloodPressure !== null) {
    $bpStatus = "Optimal Blood Pressure";
    $bpTier = "Optimal";
    $bpSeverity = 0;
    $bpNorm = 95.0;
    $bpSentence = "Blood pressure is Optimal at {$bloodPressure} mmHg.";
    $bpAction = "Keep blood pressure balanced near {$bloodPressure} mmHg with low-sodium meals.";
    if (preg_match('/^(\d+)\s*\/\s*(\d+)/', $bloodPressure, $mBp)) {
        $sysVal = (int)$mBp[1];
        $diaVal = (int)$mBp[2];
        $bpEval = evaluateBloodPressure($sysVal, $diaVal);
        $bpStatus = $bpEval["status_title"];
        $bpTier = $bpEval["tier"];
        if ($bpStatus === "Low Blood Pressure (Hypotension)") {
            $bpSeverity = 2;
            $bpNorm = ($sysVal < 80 || $diaVal < 50) ? 10.0 : 18.0;
            $bpSentence = "Blood pressure is Critically Low (Hypotension Alert) at {$bloodPressure} mmHg (< 90 systolic or < 60 diastolic).";
            $bpAction = "Sit or lie down slowly, hydrate with water and electrolytes, and consult a healthcare provider if dizzy near {$bloodPressure} mmHg.";
        } elseif ($bpStatus === "Hypertensive Crisis") {
            $bpSeverity = 2;
            $bpNorm = 10.0;
            $bpSentence = "Blood pressure sits in Hypertensive Crisis (Alert) at {$bloodPressure} mmHg (>= 180 systolic or >= 120 diastolic) and requires prompt rest and medical evaluation.";
            $bpAction = "Sit quietly for 5 minutes and recheck {$bloodPressure} mmHg; seek immediate medical attention if elevated pressure persists.";
        } elseif ($bpStatus === "Stage 2 Hypertension") {
            $bpSeverity = 2;
            $bpNorm = 25.0;
            $bpSentence = "Blood pressure falls in Stage 2 Hypertension at {$bloodPressure} mmHg (>= 140 systolic or >= 90 diastolic).";
            $bpAction = "Limit sodium below 1,500 mg today, avoid heavy straining, and consult a healthcare provider regarding {$bloodPressure} mmHg.";
        } elseif ($bpStatus === "Stage 1 Hypertension") {
            $bpSeverity = 1;
            $bpNorm = 45.0;
            $bpSentence = "Blood pressure sits in Stage 1 Hypertension at {$bloodPressure} mmHg (130-139 systolic or 80-89 diastolic).";
            $bpAction = "Rest quietly for 10 minutes, keep sodium low today, and add a calm 20-minute walk to support BP near {$bloodPressure} mmHg.";
        } elseif ($bpStatus === "Elevated Blood Pressure") {
            $bpSeverity = 1;
            $bpNorm = 65.0;
            $bpSentence = "Blood pressure is Elevated at {$bloodPressure} mmHg (120-129 systolic with diastolic below 80).";
            $bpAction = "Swap salty snacks for potassium-rich foods and take a calm 15-minute walk to ease BP from {$bloodPressure} mmHg.";
        }
    }
    $addEvaluatedMetric("Blood Pressure", "{$bloodPressure} mmHg", $bpStatus, $bpTier, $bpSeverity, $bpNorm, $bpSentence, $bpAction, 2);
    $telemetryLines[] = "- Blood Pressure: {$bloodPressure} mmHg [Status: {$bpStatus} | Tier: {$bpTier}]";
}

// Always evaluate Hydration & Activity, plus Sleep when logged
$hydAction = ($hydrationPctRaw >= 80.0)
    ? "Drink remaining {$remWaterFmt} ml of water gradually today to finish at {$targetWaterFmt} ml."
    : (($hydrationPctRaw >= 40.0)
        ? "Drink 400-500 ml of water over the next 2 hours to close the {$remWaterFmt} ml gap toward {$targetWaterFmt} ml."
        : "Drink 500 ml water right now and aim for at least " . number_format(max(500, (int)round($hydrationRemainingMl * 0.6))) . " ml more before evening to prevent dehydration fatigue.");
$addEvaluatedMetric("Hydration", "{$loggedWaterFmt}/{$targetWaterFmt} ml", $hydrationStatus, $hydrationTier, $hydrationSeverity, $hydrationScoreNorm, $hydrationPhrase, $hydAction, 4);
$telemetryLines[] = "- Hydration: {$hydrationDescription}";

$stepAddBlock = max(1000, min($remSteps, 2500));
$stepAction = ($stepsPctRaw >= 70.0)
    ? ($remSteps > 0 ? "Add a quick " . number_format($remSteps) . "-step stroll to complete your {$stepGoalFmt}-step target." : "Maintain gentle evening stretching after exceeding your {$stepGoalFmt}-step goal.")
    : "Take a brisk 20-minute walk to add ~" . number_format($stepAddBlock) . " steps toward your {$stepGoalFmt}-step goal ({$remStepsFmt} remaining).";
$addEvaluatedMetric("Activity", "{$stepsFmt}/{$stepGoalFmt} steps", $stepsStatus, $stepsTier, $stepsSeverity, $stepsScoreNorm, $stepsPhrase, $stepAction, 6);
$telemetryLines[] = "- Activity (Steps): {$stepsFmt} / {$stepGoalFmt} ({$stepsPct}%) [Status: {$stepsStatus} | Tier: {$stepsTier}]";

if ($sleepDurationMinutes > 0) {
    $sleepAction = ($sleepSeverity === 0)
        ? "Keep your consistent bedtime schedule to maintain 7.0+ hrs of Optimal sleep."
        : "Start winding down 45 minutes earlier tonight without screens to reach at least 7.5 hrs of restorative sleep.";
    $addEvaluatedMetric("Sleep", $sleepFormatted, $sleepStatus, $sleepTier, $sleepSeverity, $sleepScoreNorm, $sleepPhrase, $sleepAction, 5);
    $telemetryLines[] = "- Sleep: {$sleepDescription}";
} else {
    $metricEvaluations[] = [
        "metric" => "Sleep",
        "value" => "Not logged",
        "status" => "Not Logged",
        "tier" => "Not Logged",
        "severity" => 0
    ];
    $telemetryLines[] = "- Sleep: No sleep logged yet today (do not diagnose sleep deficit unless logged).";
}

if (!$hasAnyBiometricsToday) {
    $telemetryLines[] = "- Biometrics (HR / SpO2 / BP): Not logged today (STRICTLY OMIT HR, SpO2, BP values).";
}

$telemetryText = implode("\n", $telemetryLines);

// Determine overarching clinical tier & lowest-scoring metric
usort($evaluatedForRanking, function ($a, $b) {
    if ($a["severity"] !== $b["severity"]) {
        return $b["severity"] <=> $a["severity"];
    }
    if (abs($a["norm_score"] - $b["norm_score"]) > 0.01) {
        return $a["norm_score"] <=> $b["norm_score"];
    }
    return $a["priority"] <=> $b["priority"];
});
$lowestMetric = !empty($evaluatedForRanking) ? $evaluatedForRanking[0] : null;

$needsAttentionCount = count(array_filter($evaluatedForRanking, fn($m) => $m["severity"] >= 1));
if ($hasClinicalAlert) {
    $computedOverallTier = "ALERT";
} elseif ($needsAttentionCount > 0) {
    $computedOverallTier = "NEEDS_ATTENTION";
} else {
    $computedOverallTier = "OPTIMAL";
}

// 8. Synthesize Holistic Clinical Fallback Summary (Optimal metrics FIRST, then Gaps/Alerts)
$orderedClauses = [];
if (!empty($optimalClauses)) {
    $orderedClauses[] = implode(" ", array_slice($optimalClauses, 0, 2));
}
if (!empty($concernClauses)) {
    $orderedClauses[] = implode(" ", array_slice($concernClauses, 0, 2));
}
if ($sleepDurationMinutes <= 0) {
    $orderedClauses[] = "No sleep has been logged yet today.";
}
if (!$hasAnyBiometricsToday) {
    $orderedClauses[] = "Tap '+ Log Vitals' to record Heart Rate, SpO2, or Blood Pressure for full cardiovascular scoring.";
}
$fallbackSummary = implode(" ", array_slice($orderedClauses, 0, 3));

if ($computedOverallTier === "OPTIMAL") {
    $fallbackHeadline = $hasAnyBiometricsToday ? "Optimal Vitals & Habits" : "Strong Daily Balance";
} elseif ($computedOverallTier === "ALERT") {
    if ($lowestMetric !== null && in_array($lowestMetric["metric"], ["Blood Pressure", "SpO2", "Heart Rate"], true)) {
        $fallbackHeadline = "{$lowestMetric['metric']} Alert";
    } elseif ($hydrationSeverity === 2) {
        $fallbackHeadline = "Hydration & Recovery Alert";
    } else {
        $fallbackHeadline = "Priority Recovery Alert";
    }
} else {
    if ($lowestMetric !== null && $lowestMetric["severity"] >= 1) {
        $fallbackHeadline = "{$lowestMetric['metric']} Needs Focus";
    } else {
        $fallbackHeadline = "Steady Daily Progress";
    }
}

$fallbackTip = ($lowestMetric !== null)
    ? $lowestMetric["action"]
    : "Tap '+ Log Vitals' to log Heart Rate, SpO2, and Blood Pressure for complete wellness insights.";

if ($computedOverallTier === "OPTIMAL" && !$hasAnyBiometricsToday) {
    $fallbackTip = "Great daily momentum! Tap '+ Log Vitals' on Home to record Heart Rate, SpO2, or Blood Pressure.";
}

// 9. Call Ollama Local AI (/api/generate) with Precision Clinical Prompt & format: "json"
$score = $computedScore;
$overallTier = $computedOverallTier;
$headline = $fallbackHeadline;
$summary = $fallbackSummary;
$actionTip = $fallbackTip;
$modelUsed = "vitalsync:fallback-engine";

$lowestMetricHint = ($lowestMetric !== null)
    ? "{$lowestMetric['metric']} (Tier: {$lowestMetric['tier']})"
    : "Hydration / Activity";

$biometricRule = $hasAnyBiometricsToday
    ? "Evaluate ONLY logged biometrics shown below (HR, SpO2, BP). Omit any biometric not listed."
    : "NO biometrics (HR, SpO2, BP) were logged today. Strictly OMIT heart rate, SpO2, and blood pressure values completely.";

$precisionPrompt = <<<PROMPT
Analyze this user's health telemetry and their evaluated diagnostic categories:
{$telemetryText}

Programmatic Baseline:
- Overall Tier Baseline: {$computedOverallTier}
- Lowest-Scoring Metric to Target: {$lowestMetricHint}

Generate a clinical wellness report in JSON format strictly adhering to this schema:
{
  "status_headline": "2-4 word overarching diagnosis",
  "overall_tier": "OPTIMAL" | "NEEDS_ATTENTION" | "ALERT",
  "summary": "2-3 sentences: Start with positive/optimal metrics, then clearly address metrics needing improvement or alert.",
  "action_tip": "1 concrete actionable step with exact target numbers for the lowest-scoring metric"
}

Rules:
1. Speak directly to the user in second person ("Your heart rate...", "You...") — NEVER say "The patient" or "The individual".
2. Highlight Optimal metrics first as positive reinforcement with their exact numbers.
3. Call out Low / Needs Improvement / Alert metrics clearly with exact values from telemetry.
4. {$biometricRule}
5. Action tip must directly target {$lowestMetricHint} with exact numbers.
6. NEVER use the word "adequate", "bluetooth", "smartwatch", "wearable", or "pair".
7. Output ONLY valid JSON.
PROMPT;

$payload = json_encode([
    "model" => OLLAMA_MODEL,
    "prompt" => $precisionPrompt,
    "format" => "json",
    "stream" => false,
    "keep_alive" => "15m",
    "options" => [
        "temperature" => 0.2,
        "num_predict" => 260
    ]
]);

$rawResponse = false;
$httpCode = 0;
$curlErr = "";

if (function_exists("curl_init")) {
    $ch = curl_init(OLLAMA_BASE_URL . "/api/generate");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 22
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
} else {
    $errno = 0;
    $errstr = "";
    $sock = @fsockopen("127.0.0.1", 11434, $errno, $errstr, 3);
    if ($sock) {
        fclose($sock);
        $opts = [
            "http" => [
                "method" => "POST",
                "header" => "Content-Type: application/json\r\nConnection: close\r\n",
                "content" => $payload,
                "timeout" => 22,
                "ignore_errors" => true
            ]
        ];
        $ctx = stream_context_create($opts);
        $rawResponse = @file_get_contents(OLLAMA_BASE_URL . "/api/generate", false, $ctx);
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $mCode)) {
                $httpCode = (int)$mCode[1];
            }
        } elseif ($rawResponse !== false) {
            $httpCode = 200;
        }
    }
}

if (!$curlErr && $httpCode >= 200 && $httpCode < 300 && !empty($rawResponse)) {
    $decodedOllama = json_decode($rawResponse, true);
    $content = trim((string)($decodedOllama["response"] ?? $decodedOllama["message"]["content"] ?? ""));
    if (!empty($content)) {
        $cleanJson = preg_replace('/^```(?:json)?/i', '', $content);
        $cleanJson = preg_replace('/```$/', '', $cleanJson);
        $cleanJson = trim($cleanJson);

        $parsed = json_decode($cleanJson, true);
        if (is_array($parsed)) {
            $rawHeadline = trim((string)($parsed["status_headline"] ?? $parsed["status_title"] ?? ""));
            if (!empty($rawHeadline)) {
                $headline = $rawHeadline;
            }
            // Enforce programmatic clinical overall_tier so threshold grading is always deterministic
            $overallTier = $computedOverallTier;
            if (!empty($parsed["summary"])) {
                $summary = trim((string)$parsed["summary"]);
            }
            if (!empty($parsed["action_tip"])) {
                $actionTip = trim((string)$parsed["action_tip"]);
            }
            $modelUsed = OLLAMA_MODEL;
        }
    }
}

// 10. Post-processing Sanity Checks: prevent hallucinated vitals, Bluetooth mentions, third-person labels, or contradicting clinical tiers
if (stripos($summary, "adequate") !== false ||
    stripos($summary, "bluetooth") !== false ||
    stripos($summary, "smartwatch") !== false ||
    stripos($summary, "wearable") !== false ||
    stripos($summary, "the patient") !== false ||
    stripos($summary, "the individual") !== false) {
    $summary = $fallbackSummary;
}
if ($overallTier !== "OPTIMAL" && preg_match('/\b(all|both)\b[^.]{0,45}\b(optimal|normal ranges?|within range)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($bloodPressure !== null && isset($bpSeverity) && $bpSeverity > 0 && preg_match('/blood pressure[^.]{0,60}\b(optimal|normal|healthy|within range)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($heartRate !== null && isset($hrSeverity) && $hrSeverity > 0 && preg_match('/heart rate[^.]{0,60}\b(optimal|normal|healthy)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($spo2 !== null && isset($spSeverity) && $spSeverity > 0 && preg_match('/spo2|oxygen[^.]{0,60}\b(optimal|normal|healthy)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($hydrationSeverity > 0 && preg_match('/hydration[^.]{0,60}\b(optimal|well hydrated)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($sleepDurationMinutes >= 420 && (preg_match('/sleep[^.]{0,60}\b(could be improved|needs improvement|deprivation|deficit|poor|insufficient)\b/i', $summary) || stripos($summary, "insufficient sleep") !== false)) {
    $summary = $fallbackSummary;
}
if ($sleepDurationMinutes > 0 && $sleepDurationMinutes < 420 && preg_match('/sleep[^.]{0,60}\b(optimal|fully rested|within optimal)\b/i', $summary)) {
    $summary = $fallbackSummary;
}
if ($stepGoal !== 10000 && strpos($summary, "10,000") !== false) {
    $summary = $fallbackSummary;
}
if ($todayMl != 500 && (strpos($summary, "500 of 2,000") !== false || strpos($summary, "500 mL") !== false || strpos($summary, "500 ml") !== false)) {
    $summary = $fallbackSummary;
}
if ($sleepDurationMinutes > 0 && (stripos($summary, "no sleep") !== false || stripos($summary, "had no sleep") !== false || stripos($summary, "0 sleep") !== false)) {
    $summary = $fallbackSummary;
}
if ($heartRate === null && (stripos($summary, "bpm") !== false || strpos($summary, "72") !== false)) {
    $summary = $fallbackSummary;
}
if ($spo2 === null && (stripos($summary, "spo2") !== false || strpos($summary, "98%") !== false)) {
    $summary = $fallbackSummary;
}
if ($bloodPressure === null && (strpos($summary, "120/80") !== false || stripos($summary, "blood pressure") !== false)) {
    $summary = $fallbackSummary;
}

if (stripos($headline, "bluetooth") !== false ||
    stripos($headline, "smartwatch") !== false ||
    stripos($headline, "wearable") !== false ||
    preg_match('/\b(clinical wellness report|wellness report|health insight)\b/i', $headline) ||
    ($overallTier !== "OPTIMAL" && preg_match('/\b(well-managed|optimal|all good|great health|healthy day|well-being)\b/i', $headline))) {
    $headline = $fallbackHeadline;
}

if (stripos($actionTip, "bluetooth") !== false ||
    stripos($actionTip, "smartwatch") !== false ||
    stripos($actionTip, "wearable") !== false ||
    stripos($actionTip, "pair ") !== false ||
    preg_match('/lower your blood pressure to stage\s*1\/2\s*htn/i', $actionTip) ||
    ($sleepDurationMinutes >= 420 && preg_match('/increase your sleep duration/i', $actionTip))) {
    $actionTip = $fallbackTip;
} elseif ($lowestMetric !== null && $lowestMetric["severity"] >= 2) {
    $lowName = $lowestMetric["metric"];
    if ($lowName === "Blood Pressure" && !preg_match('/\b(bp|blood pressure|sodium|mmhg|pressure)\b/i', $actionTip)) {
        $actionTip = $fallbackTip;
    } elseif ($lowName === "SpO2" && !preg_match('/\b(spo2|oxygen|breath|air)\b/i', $actionTip)) {
        $actionTip = $fallbackTip;
    } elseif ($lowName === "Heart Rate" && !preg_match('/\b(heart rate|bpm|pulse|rest|breath)\b/i', $actionTip)) {
        $actionTip = $fallbackTip;
    } elseif ($lowName === "Hydration" && !preg_match('/\b(water|hydrat|fluid|ml|drink)\b/i', $actionTip)) {
        $actionTip = $fallbackTip;
    }
}

$conn->close();

respondJson(200, [
    "success" => true,
    "status" => "success",
    "score" => $score,
    "status_headline" => $headline,
    "status_title" => $headline,
    "overall_tier" => $overallTier,
    "summary" => $summary,
    "action_tip" => $actionTip,
    "metric_evaluations" => $metricEvaluations,
    "is_bt_connected" => $isBtConnected,
    "telemetry" => [
        "today_ml" => $todayMl,
        "target_water_ml" => $targetWaterMl,
        "sleep_formatted" => $sleepFormatted,
        "sleep_score" => $sleepQualityScore,
        "steps" => $todaySteps,
        "step_goal" => $stepGoal,
        "heart_rate" => $heartRate,
        "spo2" => $spo2,
        "blood_pressure" => $bloodPressure
    ],
    "insight" => [
        "score" => $score,
        "status_headline" => $headline,
        "status_title" => $headline,
        "overall_tier" => $overallTier,
        "summary" => $summary,
        "action_tip" => $actionTip,
        "metric_evaluations" => $metricEvaluations,
        "is_bt_connected" => $isBtConnected
    ],
    "model_used" => $modelUsed
]);
