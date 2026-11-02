<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

// Fetch user profile stats
$uStmt = $conn->prepare("SELECT height, weight FROM users WHERE id = ? LIMIT 1");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$userProfile = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$height = ($userProfile && $userProfile["height"] !== null) ? (float)$userProfile["height"] : 0.0;
$weight = ($userProfile && $userProfile["weight"] !== null) ? (float)$userProfile["weight"] : 0.0;
$hasValidBmi = ($height > 0 && $weight > 0);
$bmi = $hasValidBmi ? round($weight / (($height / 100) * ($height / 100)), 1) : 0.0;
$bmiCategory = $hasValidBmi ? (($bmi < 18.5) ? "Underweight" : (($bmi <= 24.9) ? "Healthy Range" : (($bmi <= 29.9) ? "Overweight" : "Obese"))) : "Not set";

$today = date("Y-m-d");

// 1. Latest Heart Rate (vitals_logs first, fallback to vitals_readings)
$hrStmt = $conn->prepare(
    "SELECT heart_rate as value_numeric, logged_at as recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND heart_rate > 0 
     ORDER BY logged_at DESC LIMIT 1"
);
$hrStmt->bind_param("i", $userId);
$hrStmt->execute();
$hrRow = $hrStmt->get_result()->fetch_assoc();
$hrStmt->close();

if (!$hrRow) {
    $hrStmt2 = $conn->prepare(
        "SELECT value_numeric, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'heart_rate' 
         ORDER BY recorded_at DESC LIMIT 1"
    );
    $hrStmt2->bind_param("i", $userId);
    $hrStmt2->execute();
    $hrRow = $hrStmt2->get_result()->fetch_assoc();
    $hrStmt2->close();
}

if ($hrRow) {
    $curBpm = (int)$hrRow["value_numeric"];
    // Stats for today
    $stStmt = $conn->prepare("SELECT MIN(heart_rate) as min_bpm, MAX(heart_rate) as max_bpm, AVG(heart_rate) as avg_bpm FROM vitals_logs WHERE user_id = ? AND heart_rate > 0 AND DATE(logged_at) = ?");
    $stStmt->bind_param("is", $userId, $today);
    $stStmt->execute();
    $stRow = $stStmt->get_result()->fetch_assoc();
    $stStmt->close();

    $minBpm = ($stRow && $stRow["min_bpm"] !== null) ? (int)$stRow["min_bpm"] : $curBpm;
    $maxBpm = ($stRow && $stRow["max_bpm"] !== null) ? (int)$stRow["max_bpm"] : $curBpm;
    $restingBpm = max(45, (int)round($minBpm * 1.02));

    $heartRate = [
        "bpm" => $curBpm,
        "status" => ($curBpm < 60) ? "Low Rate" : (($curBpm <= 100) ? "Normal" : "Elevated"),
        "resting_bpm" => $restingBpm,
        "min_bpm" => $minBpm,
        "max_bpm" => $maxBpm,
        "recorded_at" => $hrRow["recorded_at"],
        "has_data" => true
    ];
} else {
    $heartRate = [
        "bpm" => 0,
        "status" => "No readings yet",
        "resting_bpm" => 0,
        "min_bpm" => 0,
        "max_bpm" => 0,
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 2. Latest Blood Pressure
$bpStmt = $conn->prepare(
    "SELECT systolic_bp AS systolic, diastolic_bp AS diastolic, logged_at AS recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND systolic_bp > 0 
     ORDER BY logged_at DESC LIMIT 1"
);
$bpStmt->bind_param("i", $userId);
$bpStmt->execute();
$bpRow = $bpStmt->get_result()->fetch_assoc();
$bpStmt->close();

if (!$bpRow) {
    $bpStmt2 = $conn->prepare(
        "SELECT value_numeric AS systolic, value_secondary AS diastolic, value_text, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'blood_pressure' 
         ORDER BY recorded_at DESC LIMIT 1"
    );
    $bpStmt2->bind_param("i", $userId);
    $bpStmt2->execute();
    $bpRow = $bpStmt2->get_result()->fetch_assoc();
    $bpStmt2->close();
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
        "text" => "--/-- mmHg",
        "status" => "No readings yet",
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 3. Latest SpO2
$spo2Stmt = $conn->prepare(
    "SELECT spo2 as value_numeric, logged_at as recorded_at 
     FROM vitals_logs 
     WHERE user_id = ? AND spo2 > 0 
     ORDER BY logged_at DESC LIMIT 1"
);
$spo2Stmt->bind_param("i", $userId);
$spo2Stmt->execute();
$spo2Row = $spo2Stmt->get_result()->fetch_assoc();
$spo2Stmt->close();

if (!$spo2Row) {
    $spo2Stmt2 = $conn->prepare(
        "SELECT value_numeric, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'spo2' 
         ORDER BY recorded_at DESC LIMIT 1"
    );
    $spo2Stmt2->bind_param("i", $userId);
    $spo2Stmt2->execute();
    $spo2Row = $spo2Stmt2->get_result()->fetch_assoc();
    $spo2Stmt2->close();
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
        "status" => "No readings yet",
        "recorded_at" => "",
        "has_data" => false
    ];
}

// 4. Today's Steps & Goal
$stepGoalStmt = $conn->prepare("SELECT daily_step_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
$stepGoalStmt->bind_param("i", $userId);
$stepGoalStmt->execute();
$sgRow = $stepGoalStmt->get_result()->fetch_assoc();
$stepGoalStmt->close();
$stepGoal = $sgRow ? (int)$sgRow["daily_step_goal"] : 8000;

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

$hgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
$hgStmt->bind_param("i", $userId);
$hgStmt->execute();
$hgRow = $hgStmt->get_result()->fetch_assoc();
$hgStmt->close();
$targetMl = $hgRow ? (int)$hgRow["daily_target_ml"] : 2000;
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

if ($sleepRow) {
    $durationMin = (int)$sleepRow["duration_minutes"];
    $score = isset($sleepRow["sleep_score"]) ? (int)$sleepRow["sleep_score"] : min(100, (int)(($durationMin / 480) * 100));
    $sleep = [
        "duration_minutes" => $durationMin,
        "duration_formatted" => intdiv($durationMin, 60) . "h " . ($durationMin % 60) . "m",
        "sleep_score" => $score,
        "quality" => !empty($sleepRow["quality_label"]) ? $sleepRow["quality_label"] : ($score >= 80 ? "Optimal" : "Normal"),
        "bedtime" => $sleepRow["bedtime"] ?: "",
        "wake_time" => $sleepRow["wake_time"] ?: "",
        "has_data" => true
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

// Compute live wellness score
$vitalCount = 0;
$vitalScoreAcc = 0;
if ($hrRow) {
    $bpm = (int)$hrRow["value_numeric"];
    $vitalScoreAcc += ($bpm >= 60 && $bpm <= 90) ? 90 : (($bpm >= 50 && $bpm <= 100) ? 75 : 60);
    $vitalCount++;
}
if ($bpRow) {
    $sys = (int)$bpRow["systolic"];
    $dia = (int)$bpRow["diastolic"];
    $vitalScoreAcc += ($sys <= 120 && $dia <= 80) ? 95 : (($sys <= 130 && $dia <= 85) ? 80 : 65);
    $vitalCount++;
}
if ($spo2Row) {
    $pct = (int)$spo2Row["value_numeric"];
    $vitalScoreAcc += ($pct >= 97) ? 98 : (($pct >= 94) ? 80 : 60);
    $vitalCount++;
}
if ($currentSteps > 0) {
    $vitalScoreAcc += min(100, (int)(($currentSteps / $stepGoal) * 100));
    $vitalCount++;
}
if ($consumedMl > 0) {
    $vitalScoreAcc += min(100, (int)(($consumedMl / $targetMl) * 100));
    $vitalCount++;
}

if ($vitalCount > 0) {
    $liveWellnessScore = (int)round($vitalScoreAcc / $vitalCount);
    $liveWellnessStatus = ($liveWellnessScore >= 80) ? "Optimal State" : (($liveWellnessScore >= 60) ? "Good State" : "Needs Attention");
} else {
    $liveWellnessScore = 0;
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
