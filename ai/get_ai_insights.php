<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tunnel-Skip-Anti-Abuse");

if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

set_time_limit(20);

require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/ai_service.php";

if (!defined("OLLAMA_BASE_URL")) define("OLLAMA_BASE_URL", "http://127.0.0.1:11434");
if (!defined("OLLAMA_MODEL")) define("OLLAMA_MODEL", "qwen2.5:1.5b");

$userId = 0;
if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"])) {
    $userId = (int)$_GET["user_id"];
} elseif (isset($_POST["user_id"]) && is_numeric($_POST["user_id"])) {
    $userId = (int)$_POST["user_id"];
} else {
    $userId = resolveUserIdOrDie($conn, true);
}

if ($userId <= 0) {
    $userId = 1; // Default guest user
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

// Classify Sleep objectively (Never hardcoded "Adequate")
$sleepCategory = "Not Logged";
$sleepPhrase = "No sleep has been logged yet today.";
if ($sleepDurationMinutes > 0) {
    $sleepHours = $sleepDurationMinutes / 60.0;
    if ($sleepHours < 6.0) {
        $sleepCategory = "Poor (<6.0h)";
        $sleepPhrase = "Sleep was Poor at {$sleepFormatted}.";
    } elseif ($sleepHours < 7.0) {
        $sleepCategory = "Fair (6.0-7.0h)";
        $sleepPhrase = "Sleep was Fair at {$sleepFormatted}.";
    } elseif ($sleepHours <= 8.5) {
        $sleepCategory = "Optimal (7.0-8.5h)";
        $sleepPhrase = "Sleep was Optimal at {$sleepFormatted}.";
    } else {
        $sleepCategory = "Deep / Restorative (>8.5h)";
        $sleepPhrase = "Sleep was Deep / Restorative at {$sleepFormatted}.";
    }
}

$sleepDescription = ($sleepDurationMinutes > 0)
    ? "{$sleepFormatted} [{$sleepCategory}]"
    : "No sleep logged";

$hydrationPctRaw = ($targetWaterMl > 0) ? (($todayMl / (float)$targetWaterMl) * 100.0) : 0.0;
$hydrationPct = (int)round($hydrationPctRaw);
$hydrationRemainingMl = max(0, $targetWaterMl - $todayMl);
$hydrationDescription = number_format($todayMl) . " mL of " . number_format($targetWaterMl) . " mL ({$hydrationPct}%)";
$loggedWaterFmt = number_format($todayMl);
$targetWaterFmt = number_format($targetWaterMl);
$remWaterFmt = number_format($hydrationRemainingMl);

if ($hydrationPctRaw < 40.0) {
    $hydrationPhrase = "Water intake is critically low ({$loggedWaterFmt} / {$targetWaterFmt} ml, {$remWaterFmt} ml remaining). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Drink 1–2 glasses immediately to restore fluid balance.";
} elseif ($hydrationPctRaw < 75.0) {
    $hydrationPhrase = "Intake is lagging behind your daily goal ({$loggedWaterFmt} / {$targetWaterFmt} ml, {$remWaterFmt} ml remaining). Insufficient hydration can trigger false hunger cues, reduce physical endurance, and elevate resting heart rate. Try keeping a water bottle nearby to sip consistently.";
} elseif ($hydrationPctRaw < 100.0) {
    $hydrationPhrase = "You're close to your hydration target ({$loggedWaterFmt} / {$targetWaterFmt} ml)! Just {$remWaterFmt} ml remaining to ensure optimal electrolyte balance and physical performance.";
} else {
    $hydrationPhrase = "Daily hydration goal achieved ({$loggedWaterFmt} / {$targetWaterFmt} ml)! Optimal fluid balance supports muscle recovery and cognitive clarity. Maintain steady intake if working out.";
}

$stepsPctRaw = ($stepGoal > 0) ? (($todaySteps / (float)$stepGoal) * 100.0) : 0.0;
$stepsPct = (int)round($stepsPctRaw);
$stepsFmt = number_format($todaySteps);
$stepGoalFmt = number_format($stepGoal);
if ($stepsPctRaw < 50.0) {
    $stepsPhrase = "You have completed {$stepsFmt} of your {$stepGoalFmt} daily step goal. A 20-minute brisk walk will help you close the gap.";
} elseif ($stepsPctRaw < 100.0) {
    $remStepsFmt = number_format(max(0, $stepGoal - $todaySteps));
    $stepsPhrase = "Good progress! You're only {$remStepsFmt} steps away from hitting your personalized {$stepGoalFmt}-step target.";
} else {
    $excessStepsFmt = number_format(max(0, $todaySteps - $stepGoal));
    $stepsPhrase = ($todaySteps > $stepGoal)
        ? "Target unlocked! You exceeded your {$stepGoalFmt}-step goal by {$excessStepsFmt} steps today."
        : "Target unlocked! You exceeded your {$stepGoalFmt}-step goal by 0 steps today.";
}

// Assemble Telemetry Context (omit unmeasured biometrics completely)
$telemetryLines = [];
$telemetryLines[] = "- Steps: " . number_format($todaySteps) . " / " . number_format($stepGoal) . " ({$stepsPct}%)";
$telemetryLines[] = "- Sleep: {$sleepDescription}";
$telemetryLines[] = "- Hydration: {$hydrationDescription}";

$vitalsPhrases = [];
if ($heartRate !== null) {
    if ($heartRate < 60) {
        $hrRange = "low/athletic (<60 bpm)";
        $vitalsPhrases[] = "Resting heart rate is low/athletic at {$heartRate} bpm.";
    } elseif ($heartRate <= 100) {
        $hrRange = "normal recovery (60-100 bpm)";
        $vitalsPhrases[] = "Heart rate of {$heartRate} bpm sits in the normal recovery range.";
    } else {
        $hrRange = "elevated (>100 bpm)";
        $vitalsPhrases[] = "Heart rate is elevated at {$heartRate} bpm, suggesting strain or fatigue.";
    }
    $telemetryLines[] = "- Heart Rate: {$heartRate} bpm ({$hrRange})";
}
if ($spo2 !== null) {
    if ($spo2 >= 95) {
        $vitalsPhrases[] = "SpO2 is normal at {$spo2}%.";
        $telemetryLines[] = "- Blood Oxygen (SpO2): {$spo2}% (normal >=95%)";
    } else {
        $vitalsPhrases[] = "SpO2 is sub-optimal at {$spo2}%.";
        $telemetryLines[] = "- Blood Oxygen (SpO2): {$spo2}% (sub-optimal <95%)";
    }
}
if ($bloodPressure !== null) {
    $telemetryLines[] = "- Blood Pressure: {$bloodPressure} mmHg";
}

if (!$hasAnyBiometricsToday) {
    $telemetryLines[] = "- Biometric Sensors: " . ($isBtConnected ? "Device connected, awaiting today's biometric readings." : "No wearable device connected and no real readings logged today (omit HR, SpO2, BP).");
}

$telemetryText = implode("\n", $telemetryLines);

// 8. Synthesize 2-3 sentence Full-Vitals Fallback Summary & Action Tip
$summaryParts = [];
if (!empty($vitalsPhrases)) {
    $summaryParts[] = implode(" ", array_slice($vitalsPhrases, 0, 2));
}
$summaryParts[] = "{$stepsPhrase} {$sleepPhrase}";
if (!$hasAnyBiometricsToday && !$isBtConnected) {
    $summaryParts[] = "{$hydrationPhrase} Pair a wearable device to monitor resting heart rate and recovery metrics in real time.";
} else {
    $summaryParts[] = $hydrationPhrase;
}
$fallbackSummary = implode(" ", $summaryParts);

if ($computedScore >= 80) {
    $fallbackTitle = $hasAnyBiometricsToday ? "Strong Recovery & Vitals" : "Strong Daily Habits";
} elseif ($computedScore >= 55) {
    $fallbackTitle = "Steady Daily Progress";
} else {
    $fallbackTitle = $isBtConnected ? "Build Daily Momentum" : "Logged Metrics • Sensor Offline";
}

if (!$hasAnyBiometricsToday && !$isBtConnected) {
    $fallbackTip = "Pair a wearable device in Settings > Connected Devices to monitor resting heart rate and recovery metrics in real time.";
} elseif ($hydrationPctRaw < 40.0) {
    $fallbackTip = "Water intake is critically low ({$loggedWaterFmt} / {$targetWaterFmt} ml)—drink 1–2 glasses immediately to prevent fatigue and sluggish recovery.";
} elseif ($hydrationPctRaw < 75.0) {
    $fallbackTip = "Keep a water bottle nearby—{$remWaterFmt} ml left to reach your {$targetWaterFmt} ml hydration target.";
} elseif ($todaySteps < $stepGoal) {
    $remainingSteps = number_format(max(0, $stepGoal - $todaySteps));
    $fallbackTip = ($stepsPctRaw < 50.0)
        ? "Take a 20-minute brisk walk to build momentum toward your {$stepGoalFmt}-step goal."
        : "Just {$remainingSteps} steps left to reach your {$stepGoalFmt}-step goal today!";
} elseif ($sleepDurationMinutes > 0 && ($sleepDurationMinutes / 60.0) < 7.0) {
    $fallbackTip = "Aim for 7-8.5 hours of Optimal restorative sleep tonight.";
} else {
    $fallbackTip = "Target unlocked! Keep steady water pacing and balanced recovery through the evening.";
}

// 9. Call Ollama Local AI with format: "json"
$score = $computedScore;
$title = $fallbackTitle;
$summary = $fallbackSummary;
$actionTip = $fallbackTip;
$modelUsed = "vitalsync:fallback-engine";

$biometricRule = $hasAnyBiometricsToday
    ? "Measured biometrics ARE available today (" . trim(($heartRate !== null ? "HR {$heartRate} bpm " : "") . ($spo2 !== null ? "SpO2 {$spo2}% " : "") . ($bloodPressure !== null ? "BP {$bloodPressure} mmHg" : "")) . "). Evaluate measured vitals accurately (HR: <60 low/athletic, 60-100 normal recovery, >100 elevated; SpO2: >=95% normal, <95% sub-optimal). Omit any metric not listed in Patient Telemetry."
    : "NO wearable biometrics were logged today. You MUST OMIT heart rate, SpO2, and blood pressure values completely (never assume 72 bpm, 98%, or 120/80). Gently suggest pairing a wearable device to monitor resting heart rate and recovery metrics in real time.";

$sleepPromptRule = ($sleepDurationMinutes > 0)
    ? "Sleep ({$sleepDescription}): Classify strictly as <6.0h Poor, 6.0-7.0h Fair, 7.0-8.5h Optimal, >8.5h Deep / Restorative. NEVER use the word \"Adequate\"."
    : "Sleep: No sleep has been logged yet today. Mention that sleep isn't logged yet without labeling unlogged sleep as Poor. NEVER use the word \"Adequate\".";

$systemPrompt = <<<SYS
You are VitalSync AI, a concise clinical wellness engine.
Generate an accurate 2-3 sentence health insight strictly formatted as a JSON object with EXACTLY these keys:
{
  "status_title": "Short title under 6 words",
  "summary": "2-3 concise sentences synthesizing ONLY the provided telemetry",
  "action_tip": "1 specific actionable instruction for today"
}

TELEMETRY GROUNDING RULES:
1. {$biometricRule}
2. Steps: Use the user's saved goal ({$stepGoalFmt} steps) at {$stepsPct}% completion: {$stepsPhrase}
3. {$sleepPromptRule}
4. Hydration: {$hydrationPhrase}
5. Use exact numbers from Patient Telemetry. Do NOT invent sample values. Output ONLY valid JSON.
SYS;

$userPrompt = "Patient Telemetry:\n$telemetryText";

$payload = json_encode([
    "model" => OLLAMA_MODEL,
    "format" => "json",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userPrompt]
    ],
    "stream" => false,
    "options" => [
        "temperature" => 0.2,
        "num_predict" => 256
    ]
]);

$rawResponse = false;
$httpCode = 0;
$curlErr = "";

if (function_exists("curl_init")) {
    $ch = curl_init(OLLAMA_BASE_URL . "/api/chat");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10
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
                "header" => "Content-Type: application/json\r\n",
                "content" => $payload,
                "timeout" => 10,
                "ignore_errors" => true
            ]
        ];
        $ctx = stream_context_create($opts);
        $rawResponse = @file_get_contents(OLLAMA_BASE_URL . "/api/chat", false, $ctx);
        if ($rawResponse !== false) {
            $httpCode = 200;
        }
    }
}

if (!$curlErr && $httpCode >= 200 && $httpCode < 300 && !empty($rawResponse)) {
    $decodedOllama = json_decode($rawResponse, true);
    $content = trim($decodedOllama["message"]["content"] ?? "");
    if (!empty($content)) {
        $cleanJson = preg_replace('/^```(?:json)?/i', '', $content);
        $cleanJson = preg_replace('/```$/', '', $cleanJson);
        $cleanJson = trim($cleanJson);

        $parsed = json_decode($cleanJson, true);
        if (is_array($parsed)) {
            if (!empty($parsed["status_title"])) {
                $title = trim($parsed["status_title"]);
            }
            if (!empty($parsed["summary"])) {
                $summary = trim($parsed["summary"]);
            }
            if (!empty($parsed["action_tip"])) {
                $actionTip = trim($parsed["action_tip"]);
            }
            $modelUsed = OLLAMA_MODEL;
        }
    }
}

// 10. Post-processing Sanity Checks: prevent hallucinated vitals, hardcoded "Adequate", wrong sleep class, or stale samples
if (stripos($summary, "adequate") !== false) {
    $summary = $fallbackSummary;
}
if ($sleepDurationMinutes >= 420 && $sleepDurationMinutes <= 510 && (stripos($summary, "insufficient sleep") !== false || stripos($summary, "poor") !== false || stripos($summary, "fair") !== false)) {
    $summary = $fallbackSummary;
}
if ($sleepDurationMinutes > 0 && $sleepDurationMinutes < 420 && stripos($summary, "optimal") !== false) {
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

if (!$hasAnyBiometricsToday && !$isBtConnected) {
    $tipHasWearable = (stripos($actionTip, "pair") !== false || stripos($actionTip, "connect") !== false || stripos($actionTip, "wearable") !== false || stripos($actionTip, "device") !== false);
    $summaryHasWearable = (stripos($summary, "pair") !== false || stripos($summary, "wearable") !== false || stripos($summary, "connect") !== false);
    if (!$tipHasWearable && !$summaryHasWearable) {
        $actionTip = $fallbackTip;
    }
} elseif ($isBtConnected) {
    if (stripos($actionTip, "connect your bluetooth") !== false || stripos($actionTip, "connect your smartwatch") !== false || stripos($actionTip, "pair a wearable") !== false) {
        $actionTip = $fallbackTip;
    }
}

$conn->close();

respondJson(200, [
    "success" => true,
    "status" => "success",
    "score" => $score,
    "status_title" => $title,
    "summary" => $summary,
    "action_tip" => $actionTip,
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
        "status_title" => $title,
        "summary" => $summary,
        "action_tip" => $actionTip,
        "is_bt_connected" => $isBtConnected
    ],
    "model_used" => $modelUsed
]);
