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

// Latest Heart Rate
$hrStmt = $conn->prepare(
    "SELECT value_numeric, status_label, recorded_at 
     FROM vitals_readings 
     WHERE user_id = ? AND reading_type = 'heart_rate' 
     ORDER BY recorded_at DESC LIMIT 1"
);
$hrStmt->bind_param("i", $userId);
$hrStmt->execute();
$hrRow = $hrStmt->get_result()->fetch_assoc();
$hrStmt->close();

if ($hrRow) {
    $heartRate = [
        "bpm" => (int)$hrRow["value_numeric"],
        "status" => $hrRow["status_label"] ?: "Normal",
        "resting_bpm" => 0,
        "min_bpm" => 0,
        "max_bpm" => 0,
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

// Latest Blood Pressure
$bpStmt = $conn->prepare(
    "SELECT value_numeric AS systolic, value_secondary AS diastolic, value_text, status_label, recorded_at 
     FROM vitals_readings 
     WHERE user_id = ? AND reading_type = 'blood_pressure' 
     ORDER BY recorded_at DESC LIMIT 1"
);
$bpStmt->bind_param("i", $userId);
$bpStmt->execute();
$bpRow = $bpStmt->get_result()->fetch_assoc();
$bpStmt->close();

if ($bpRow) {
    $bloodPressure = [
        "systolic" => (int)$bpRow["systolic"],
        "diastolic" => (int)$bpRow["diastolic"],
        "text" => $bpRow["value_text"] ?: ($bpRow["systolic"] . "/" . $bpRow["diastolic"] . " mmHg"),
        "status" => $bpRow["status_label"] ?: "Normal",
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

// Latest SpO2
$spo2Stmt = $conn->prepare(
    "SELECT value_numeric, status_label, recorded_at 
     FROM vitals_readings 
     WHERE user_id = ? AND reading_type = 'spo2' 
     ORDER BY recorded_at DESC LIMIT 1"
);
$spo2Stmt->bind_param("i", $userId);
$spo2Stmt->execute();
$spo2Row = $spo2Stmt->get_result()->fetch_assoc();
$spo2Stmt->close();

if ($spo2Row) {
    $spo2 = [
        "percentage" => (int)$spo2Row["value_numeric"],
        "status" => $spo2Row["status_label"] ?: "Optimal",
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

// Today's Steps & Goal
$today = date("Y-m-d");
$stepGoalStmt = $conn->prepare("SELECT daily_step_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
$stepGoalStmt->bind_param("i", $userId);
$stepGoalStmt->execute();
$sgRow = $stepGoalStmt->get_result()->fetch_assoc();
$stepGoalStmt->close();
$stepGoal = $sgRow ? (int)$sgRow["daily_step_goal"] : 8000;

$stepStmt = $conn->prepare("SELECT COALESCE(SUM(steps), 0) AS total_steps, COALESCE(SUM(calories_burned), 0) AS total_cal, COALESCE(SUM(distance_km), 0.0) AS total_km FROM activity_sessions WHERE user_id = ? AND DATE(start_time) = ?");
$stepStmt->bind_param("is", $userId, $today);
$stepStmt->execute();
$actRow = $stepStmt->get_result()->fetch_assoc();
$stepStmt->close();

$currentSteps = (int)$actRow["total_steps"];
$distanceKm = (float)$actRow["total_km"];
$caloriesBurned = (int)$actRow["total_cal"];
$stepsProgress = ($stepGoal > 0) ? min(100, (int)(($currentSteps / $stepGoal) * 100)) : 0;

// Today's Hydration
$hydStmt = $conn->prepare("SELECT COALESCE(SUM(amount_ml), 0) AS consumed_ml FROM hydration_logs WHERE user_id = ? AND date_string = ?");
$hydStmt->bind_param("is", $userId, $today);
$hydStmt->execute();
$hRow = $hydStmt->get_result()->fetch_assoc();
$hydStmt->close();

$consumedMl = (int)$hRow["consumed_ml"];
$hgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
$hgStmt->bind_param("i", $userId);
$hgStmt->execute();
$hgRow = $hgStmt->get_result()->fetch_assoc();
$hgStmt->close();
$targetMl = $hgRow ? (int)$hgRow["daily_target_ml"] : 2000;
$glasses = round($consumedMl / 250, 1);
$hydProgress = ($targetMl > 0) ? min(100, (int)(($consumedMl / $targetMl) * 100)) : 0;

// Last Night's Sleep
$yesterday = date("Y-m-d", strtotime("-1 day"));
$slStmt = $conn->prepare("SELECT duration_minutes, sleep_score, quality_label, bedtime, wake_time FROM sleep_records WHERE user_id = ? AND (date_string = ? OR date_string = ?) ORDER BY date_string DESC LIMIT 1");
$slStmt->bind_param("iss", $userId, $today, $yesterday);
$slStmt->execute();
$sleepRow = $slStmt->get_result()->fetch_assoc();
$slStmt->close();

if ($sleepRow) {
    $durationMin = (int)$sleepRow["duration_minutes"];
    $sleep = [
        "duration_minutes" => $durationMin,
        "duration_formatted" => intdiv($durationMin, 60) . "h " . ($durationMin % 60) . "m",
        "sleep_score" => (int)$sleepRow["sleep_score"],
        "quality" => $sleepRow["quality_label"] ?: "Normal",
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
