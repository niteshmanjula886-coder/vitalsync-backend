<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

// Fetch user profile stats safely
$userProfile = null;
$uStmt = $conn->prepare("SELECT height, weight FROM users WHERE id = ? LIMIT 1");
if ($uStmt) {
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $userProfile = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();
}

$height = ($userProfile && ($userProfile["height"] ?? null) !== null) ? (float)$userProfile["height"] : 0.0;
$weight = ($userProfile && ($userProfile["weight"] ?? null) !== null) ? (float)$userProfile["weight"] : 0.0;
$hasValidBmi = ($height > 0 && $weight > 0);
$bmi = $hasValidBmi ? round($weight / (($height / 100) * ($height / 100)), 1) : 0.0;
$bmiCategory = $hasValidBmi ? (($bmi < 18.5) ? "Underweight" : (($bmi <= 24.9) ? "Healthy Range" : (($bmi <= 29.9) ? "Overweight" : "Obese"))) : "Not set";

$today = date("Y-m-d");

// 1. Today's Latest Heart Rate (vitals_logs first, fallback to vitals_readings — TODAY only)
$hrRow = null;
$hrStmt = $conn->prepare(
    "SELECT heart_rate as value_numeric, logged_at as recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND heart_rate > 0 AND DATE(logged_at) = ?
     ORDER BY logged_at DESC LIMIT 1"
);
if ($hrStmt) {
    $hrStmt->bind_param("is", $userId, $today);
    $hrStmt->execute();
    $hrRow = $hrStmt->get_result()->fetch_assoc();
    $hrStmt->close();
}

if (!$hrRow) {
    $hrStmt2 = $conn->prepare(
        "SELECT value_numeric, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) = ?
         ORDER BY recorded_at DESC LIMIT 1"
    );
    if ($hrStmt2) {
        $hrStmt2->bind_param("is", $userId, $today);
        $hrStmt2->execute();
        $hrRow = $hrStmt2->get_result()->fetch_assoc();
        $hrStmt2->close();
    }
}

if ($hrRow) {
    $curBpm = (int)$hrRow["value_numeric"];
    // Stats for today
    $stRow = null;
    $stStmt = $conn->prepare("SELECT MIN(heart_rate) as min_bpm, MAX(heart_rate) as max_bpm, AVG(heart_rate) as avg_bpm FROM vitals_logs WHERE user_id = ? AND heart_rate > 0 AND DATE(logged_at) = ?");
    if ($stStmt) {
        $stStmt->bind_param("is", $userId, $today);
        $stStmt->execute();
        $stRow = $stStmt->get_result()->fetch_assoc();
        $stStmt->close();
    }

    $minBpm = ($stRow && $stRow["min_bpm"] !== null) ? (int)$stRow["min_bpm"] : $curBpm;
    $maxBpm = ($stRow && $stRow["max_bpm"] !== null) ? (int)$stRow["max_bpm"] : $curBpm;
    $restingBpm = max(45, (int)round($minBpm * 1.02));

    $heartRate = [
        "bpm" => $curBpm,
        "status" => ($curBpm < 60) ? "Low / Athletic" : (($curBpm <= 100) ? "Normal" : "Elevated"),
        "resting_bpm" => $restingBpm,
        "min_bpm" => $minBpm,
        "max_bpm" => $maxBpm,
        "recorded_at" => $hrRow["recorded_at"],
        "has_data" => true
    ];
} else {
    $heartRate = [
        "bpm" => 0,
        "status" => "No Device Connected",
        "resting_bpm" => 0,
        "min_bpm" => 0,
        "max_bpm" => 0,
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 2. Today's Latest Blood Pressure
$bpRow = null;
$bpStmt = $conn->prepare(
    "SELECT systolic_bp AS systolic, diastolic_bp AS diastolic, logged_at AS recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND systolic_bp > 0 AND DATE(logged_at) = ?
     ORDER BY logged_at DESC LIMIT 1"
);
if ($bpStmt) {
    $bpStmt->bind_param("is", $userId, $today);
    $bpStmt->execute();
    $bpRow = $bpStmt->get_result()->fetch_assoc();
    $bpStmt->close();
}

if (!$bpRow) {
    $bpStmt2 = $conn->prepare(
        "SELECT value_numeric AS systolic, value_secondary AS diastolic, value_text, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) = ?
         ORDER BY recorded_at DESC LIMIT 1"
    );
    if ($bpStmt2) {
        $bpStmt2->bind_param("is", $userId, $today);
        $bpStmt2->execute();
        $bpRow = $bpStmt2->get_result()->fetch_assoc();
        $bpStmt2->close();
    }
}

if ($bpRow) {
    $sys = (int)$bpRow["systolic"];
    $dia = (int)$bpRow["diastolic"];
    $bpStatus = ($sys <= 120 && $dia <= 80) ? "Optimal" : (($sys <= 129 && $dia <= 80) ? "Elevated" : "Hypertension");
    $bloodPressure = [
        "systolic" => $sys,
        "diastolic" => $dia,
        "text" => "$sys/$dia mmHg",
        "status" => $bpStatus,
        "recorded_at" => $bpRow["recorded_at"],
        "has_data" => true
    ];
} else {
    $bloodPressure = [
        "systolic" => 0,
        "diastolic" => 0,
        "text" => "--/--",
        "status" => "No Device Connected",
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 3. Today's Latest SpO2
$spo2Row = null;
$spo2Stmt = $conn->prepare(
    "SELECT spo2 as value_numeric, logged_at as recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND spo2 > 0 AND DATE(logged_at) = ?
     ORDER BY logged_at DESC LIMIT 1"
);
if ($spo2Stmt) {
    $spo2Stmt->bind_param("is", $userId, $today);
    $spo2Stmt->execute();
    $spo2Row = $spo2Stmt->get_result()->fetch_assoc();
    $spo2Stmt->close();
}

if (!$spo2Row) {
    $spo2Stmt2 = $conn->prepare(
        "SELECT value_numeric, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) = ?
         ORDER BY recorded_at DESC LIMIT 1"
    );
    if ($spo2Stmt2) {
        $spo2Stmt2->bind_param("is", $userId, $today);
        $spo2Stmt2->execute();
        $spo2Row = $spo2Stmt2->get_result()->fetch_assoc();
        $spo2Stmt2->close();
    }
}

if ($spo2Row) {
    $pct = (int)$spo2Row["value_numeric"];
    $spo2Status = ($pct >= 95) ? "Optimal" : (($pct >= 90) ? "Moderate" : "Low");
    $spo2 = [
        "percentage" => $pct,
        "status" => $spo2Status,
        "recorded_at" => $spo2Row["recorded_at"],
        "has_data" => true
    ];
} else {
    $spo2 = [
        "percentage" => 0,
        "status" => "No Device Connected",
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 4. Today's Steps & Goal (sync across activity_goals & user_health_goals)
$sgRow = null;
$stepGoalStmt = $conn->prepare("SELECT daily_step_goal, updated_at FROM activity_goals WHERE user_id = ? LIMIT 1");
if ($stepGoalStmt) {
    $stepGoalStmt->bind_param("i", $userId);
    $stepGoalStmt->execute();
    $sgRow = $stepGoalStmt->get_result()->fetch_assoc();
    $stepGoalStmt->close();
}
$hgRow = null;
$hgStmt = $conn->prepare("SELECT target_steps, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
if ($hgStmt) {
    $hgStmt->bind_param("i", $userId);
    $hgStmt->execute();
    $hgRow = $hgStmt->get_result()->fetch_assoc();
    $hgStmt->close();
}
$agGoalVal = ($sgRow && (int)($sgRow["daily_step_goal"] ?? 0) > 0) ? (int)$sgRow["daily_step_goal"] : 0;
$hgGoalVal = ($hgRow && (int)($hgRow["target_steps"] ?? 0) > 0) ? (int)$hgRow["target_steps"] : 0;
if ($agGoalVal > 0 && $hgGoalVal > 0) {
    $stepGoal = (!empty($hgRow["updated_at"]) && !empty($sgRow["updated_at"]) && strtotime($hgRow["updated_at"]) > strtotime($sgRow["updated_at"]))
        ? $hgGoalVal : $agGoalVal;
} elseif ($agGoalVal > 0) {
    $stepGoal = $agGoalVal;
} elseif ($hgGoalVal > 0) {
    $stepGoal = $hgGoalVal;
} else {
    $stepGoal = 8000;
}

// 4. Today's Steps & Goal (workout_sessions first, fallback to activity_sessions, steps_logs)
$currentSteps = 0;
$distanceKm = 0.0;
$caloriesBurned = 0;

$wsStmt = $conn->prepare("SELECT COALESCE(SUM(steps), 0) AS total_steps, COALESCE(SUM(calories), 0) AS total_cal, COALESCE(SUM(CASE WHEN distance_km > 0 THEN distance_km ELSE distance_meters/1000.0 END), 0.0) AS total_km FROM workout_sessions WHERE user_id = ? AND DATE(start_time) = ?");
if ($wsStmt) {
    $wsStmt->bind_param("is", $userId, $today);
    $wsStmt->execute();
    $wsRow = $wsStmt->get_result()->fetch_assoc();
    if ($wsRow && ((int)$wsRow["total_steps"] > 0 || (float)$wsRow["total_km"] > 0 || (int)$wsRow["total_cal"] > 0)) {
        $currentSteps = (int)$wsRow["total_steps"];
        $distanceKm = (float)$wsRow["total_km"];
        $caloriesBurned = (int)$wsRow["total_cal"];
    }
    $wsStmt->close();
}

if ($currentSteps === 0) {
    $stepStmt = $conn->prepare("SELECT COALESCE(SUM(steps), 0) AS total_steps, COALESCE(SUM(calories_burned), 0) AS total_cal, COALESCE(SUM(distance_km), 0.0) AS total_km FROM activity_sessions WHERE user_id = ? AND DATE(start_time) = ?");
    if ($stepStmt) {
        $stepStmt->bind_param("is", $userId, $today);
        $stepStmt->execute();
        $actRow = $stepStmt->get_result()->fetch_assoc();
        if ($actRow && (int)$actRow["total_steps"] > 0) {
            $currentSteps = (int)$actRow["total_steps"];
            $distanceKm = (float)$actRow["total_km"];
            $caloriesBurned = (int)$actRow["total_cal"];
        }
        $stepStmt->close();
    }
}

if ($currentSteps === 0) {
    $slStmt2 = $conn->prepare("SELECT step_count, distance_km, calories_kcal FROM steps_logs WHERE user_id = ? AND logged_date = ? LIMIT 1");
    if ($slStmt2) {
        $slStmt2->bind_param("is", $userId, $today);
        $slStmt2->execute();
        $slRow2 = $slStmt2->get_result()->fetch_assoc();
        if ($slRow2) {
            $currentSteps = (int)$slRow2["step_count"];
            $distanceKm = (float)$slRow2["distance_km"];
            $caloriesBurned = (int)$slRow2["calories_kcal"];
        }
        $slStmt2->close();
    }
}

$stepsProgress = ($stepGoal > 0) ? min(100, (int)(($currentSteps / $stepGoal) * 100)) : 0;

// 5. Today's Hydration
$hydStmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(volume_ml, intake_ml, amount_ml)), 0) AS consumed_ml FROM hydration_logs WHERE user_id = ? AND (log_date = ? OR logged_date = ? OR date_string = ?)");
if ($hydStmt) {
    $hydStmt->bind_param("isss", $userId, $today, $today, $today);
    $hydStmt->execute();
    $hRow = $hydStmt->get_result()->fetch_assoc();
    $consumedMl = (int)$hRow["consumed_ml"];
    $hydStmt->close();
} else {
    $consumedMl = 0;
}

$hgRow = null;
$hgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
if ($hgStmt) {
    $hgStmt->bind_param("i", $userId);
    $hgStmt->execute();
    $hgRow = $hgStmt->get_result()->fetch_assoc();
    $hgStmt->close();
}
$targetMl = ($hgRow && (int)($hgRow["daily_target_ml"] ?? 0) > 0) ? (int)$hgRow["daily_target_ml"] : 2000;
$glasses = round($consumedMl / 250, 1);
$hydProgress = ($targetMl > 0) ? min(100, (int)(($consumedMl / $targetMl) * 100)) : 0;

// 6. Last Night's Sleep
$yesterday = date("Y-m-d", strtotime("-1 day"));
$sleepRow = null;

// Query sleep_logs table first
$slStmt = $conn->prepare("SELECT duration_minutes, COALESCE(quality_score, 0) AS sleep_score, bedtime, wake_time, start_time, end_time FROM sleep_logs WHERE user_id = ? AND (logged_at = ? OR logged_at = ? OR logged_date = ? OR logged_date = ?) ORDER BY id DESC LIMIT 1");
if ($slStmt) {
    $slStmt->bind_param("issss", $userId, $today, $yesterday, $today, $yesterday);
    $slStmt->execute();
    $sleepRow = $slStmt->get_result()->fetch_assoc();
    $slStmt->close();
}

if (!$sleepRow) {
    $slStmt2 = $conn->prepare("SELECT duration_minutes, sleep_score, quality_label, bedtime, wake_time FROM sleep_records WHERE user_id = ? AND (date_string = ? OR date_string = ?) ORDER BY date_string DESC LIMIT 1");
    if ($slStmt2) {
        $slStmt2->bind_param("iss", $userId, $today, $yesterday);
        $slStmt2->execute();
        $sleepRow = $slStmt2->get_result()->fetch_assoc();
        $slStmt2->close();
    }
}

$durationMin = 0;
if ($sleepRow) {
    $durationMin = (int)$sleepRow["duration_minutes"];
    $sleepHrs = $durationMin / 60.0;
    // Derive quality label & score dynamically from duration when sensor score missing or 0:
    // <6.0h Poor (~40-55), 6.0-7.0h Fair (~60-74), 7.0-8.5h Optimal (~75-90), >8.5h Deep / Restorative (~90-100)
    $computedSleepLabel = "No record";
    $derivedScore = 0;
    if ($durationMin > 0) {
        if ($sleepHrs < 6.0) {
            $computedSleepLabel = "Poor";
            $derivedScore = max(40, min(55, (int)round(40 + ($sleepHrs / 6.0) * 15)));
        } elseif ($sleepHrs < 7.0) {
            $computedSleepLabel = "Fair";
            $derivedScore = max(60, min(74, (int)round(60 + ($sleepHrs - 6.0) * 14)));
        } elseif ($sleepHrs <= 8.5) {
            $computedSleepLabel = "Optimal";
            $derivedScore = max(75, min(90, (int)round(75 + (($sleepHrs - 7.0) / 1.5) * 15)));
        } else {
            $computedSleepLabel = "Excellent";
            $derivedScore = max(90, min(100, (int)round(90 + min(1.0, ($sleepHrs - 8.5) / 1.5) * 10)));
        }
    }
    $explicitScore = isset($sleepRow["sleep_score"]) ? (int)$sleepRow["sleep_score"] : 0;
    $score = ($explicitScore > 0) ? $explicitScore : $derivedScore;
    $sleep = [
        "duration_minutes" => $durationMin,
        "duration_formatted" => $durationMin > 0 ? (intdiv($durationMin, 60) . "h " . ($durationMin % 60) . "m") : "--",
        "sleep_score" => $score,
        "quality" => $computedSleepLabel,
        "bedtime" => $sleepRow["bedtime"] ?: "",
        "wake_time" => $sleepRow["wake_time"] ?: "",
        "has_data" => ($durationMin > 0)
    ];
} else {
    $sleep = [
        "duration_minutes" => 0,
        "duration_formatted" => "--",
        "sleep_score" => 0,
        "quality" => "No record",
        "bedtime" => "",
        "wake_time" => "",
        "has_data" => false
    ];
}

$conn->close();

// Compute dynamic wellness score (0-100):
// round(min(35, (todaySteps/stepGoal)*35) + min(35, (todayWaterMl/waterGoalMl)*35) + min(30, (todaySleepMinutes/480)*30))
$stepsScorePart = ($stepGoal > 0) ? min(35.0, ($currentSteps / (float)$stepGoal) * 35.0) : 0.0;
$waterScorePart = ($targetMl > 0) ? min(35.0, ($consumedMl / (float)$targetMl) * 35.0) : 0.0;
$sleepScorePart = min(30.0, ($durationMin / 480.0) * 30.0);
$liveWellnessScore = max(0, min(100, (int)round($stepsScorePart + $waterScorePart + $sleepScorePart)));

if ($currentSteps > 0 || $consumedMl > 0 || $durationMin > 0) {
    $liveWellnessStatus = ($liveWellnessScore >= 80) ? "Optimal State" : (($liveWellnessScore >= 55) ? "Good Progress" : "Needs Attention");
} else {
    $liveWellnessStatus = "No data recorded";
}

respondJson(200, [
    "success" => true,
    "wellness_score" => $liveWellnessScore,
    "wellness_status" => $liveWellnessStatus,
    "summary" => [
        "heart_rate" => $heartRate,
        "blood_pressure" => $bloodPressure,
        "spo2" => $spo2,
        "bmi" => [
            "value" => $bmi,
            "category" => $bmiCategory,
            "height_cm" => $height,
            "weight_kg" => $weight
        ],
        "steps" => [
            "current" => $currentSteps,
            "goal" => $stepGoal,
            "distance_km" => $distanceKm,
            "calories_kcal" => $caloriesBurned,
            "progress_percent" => $stepsProgress
        ],
        "hydration" => [
            "consumed_ml" => $consumedMl,
            "target_ml" => $targetMl,
            "glasses" => $glasses,
            "progress_percent" => $hydProgress
        ],
        "sleep" => $sleep
    ]
]);
