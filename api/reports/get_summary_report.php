<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use GET.",
        "data" => null
    ]);
}

$period = strtolower(trim((string)($_GET["period"] ?? "weekly")));
$today = date("Y-m-d");

$startDate = trim($_GET["start_date"] ?? "");
$endDate = trim($_GET["end_date"] ?? "");

if (empty($startDate) || empty($endDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    if ($period === "monthly") {
        $startDate = date("Y-m-d", strtotime("-29 days"));
        $endDate = $today;
    } else {
        $period = "weekly";
        $startDate = date("Y-m-d", strtotime("-6 days"));
        $endDate = $today;
    }
}

// 1. Fetch User Profile
$uStmt = $conn->prepare("SELECT id, COALESCE(name, full_name) as display_name, email, age, gender, height, weight FROM users WHERE id = ? LIMIT 1");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$userProfile = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$userName = $userProfile ? ($userProfile["display_name"] ?: "VitalSync User") : "VitalSync User";
$userEmail = $userProfile ? $userProfile["email"] : "";
$userAge = $userProfile && $userProfile["age"] ? (int)$userProfile["age"] : null;
$userGender = $userProfile && $userProfile["gender"] ? $userProfile["gender"] : null;
$userHeight = $userProfile && $userProfile["height"] ? (float)$userProfile["height"] : null;
$userWeight = $userProfile && $userProfile["weight"] ? (float)$userProfile["weight"] : null;
$userBmi = null;
$userBmiCategory = "Not calculated";
if ($userHeight && $userWeight && $userHeight > 50 && $userWeight > 20) {
    $hM = $userHeight / 100.0;
    $userBmi = round($userWeight / ($hM * $hM), 1);
    $userBmiCategory = $userBmi < 18.5 ? "Underweight" : ($userBmi <= 24.9 ? "Healthy Range" : ($userBmi <= 29.9 ? "Overweight" : "Obese"));
}

// 2. Vitals Analysis in Period
$hrStmt = $conn->prepare("SELECT AVG(heart_rate) as avg_hr, MIN(heart_rate) as min_hr, MAX(heart_rate) as max_hr, COUNT(*) as cnt FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND heart_rate > 0");
$hrStmt->bind_param("iss", $userId, $startDate, $endDate);
$hrStmt->execute();
$hrStats = $hrStmt->get_result()->fetch_assoc();
$hrStmt->close();

$avgHr = ($hrStats && $hrStats["avg_hr"] !== null) ? round((float)$hrStats["avg_hr"]) : null;
$minHr = ($hrStats && $hrStats["min_hr"] !== null) ? (int)$hrStats["min_hr"] : null;
$maxHr = ($hrStats && $hrStats["max_hr"] !== null) ? (int)$hrStats["max_hr"] : null;

// Fallback to vitals_readings if vitals_logs is empty
if ($avgHr === null) {
    $hrFallback = $conn->prepare("SELECT AVG(value_numeric) as avg_hr, MIN(value_numeric) as min_hr, MAX(value_numeric) as max_hr FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $hrFallback->bind_param("iss", $userId, $startDate, $endDate);
    $hrFallback->execute();
    $hrFb = $hrFallback->get_result()->fetch_assoc();
    $hrFallback->close();
    if ($hrFb && $hrFb["avg_hr"] !== null) {
        $avgHr = round((float)$hrFb["avg_hr"]);
        $minHr = (int)$hrFb["min_hr"];
        $maxHr = (int)$hrFb["max_hr"];
    }
}

$spo2Stmt = $conn->prepare("SELECT AVG(spo2) as avg_spo2, MIN(spo2) as min_spo2, MAX(spo2) as max_spo2 FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND spo2 > 0");
$spo2Stmt->bind_param("iss", $userId, $startDate, $endDate);
$spo2Stmt->execute();
$spo2Stats = $spo2Stmt->get_result()->fetch_assoc();
$spo2Stmt->close();

$avgSpo2 = ($spo2Stats && $spo2Stats["avg_spo2"] !== null) ? round((float)$spo2Stats["avg_spo2"], 1) : null;
$minSpo2 = ($spo2Stats && $spo2Stats["min_spo2"] !== null) ? (int)$spo2Stats["min_spo2"] : null;

if ($avgSpo2 === null) {
    $spo2Fb = $conn->prepare("SELECT AVG(value_numeric) as avg_spo2, MIN(value_numeric) as min_spo2 FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $spo2Fb->bind_param("iss", $userId, $startDate, $endDate);
    $spo2Fb->execute();
    $sFb = $spo2Fb->get_result()->fetch_assoc();
    $spo2Fb->close();
    if ($sFb && $sFb["avg_spo2"] !== null) {
        $avgSpo2 = round((float)$sFb["avg_spo2"], 1);
        $minSpo2 = (int)$sFb["min_spo2"];
    }
}

$bpStmt = $conn->prepare("SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND systolic_bp > 0");
$bpStmt->bind_param("iss", $userId, $startDate, $endDate);
$bpStmt->execute();
$bpStats = $bpStmt->get_result()->fetch_assoc();
$bpStmt->close();

$avgSys = ($bpStats && $bpStats["avg_sys"] !== null) ? round((float)$bpStats["avg_sys"]) : null;
$avgDia = ($bpStats && $bpStats["avg_dia"] !== null) ? round((float)$bpStats["avg_dia"]) : null;

if ($avgSys === null) {
    $bpFb = $conn->prepare("SELECT AVG(value_numeric) as avg_sys, AVG(value_secondary) as avg_dia FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $bpFb->bind_param("iss", $userId, $startDate, $endDate);
    $bpFb->execute();
    $bFb = $bpFb->get_result()->fetch_assoc();
    $bpFb->close();
    if ($bFb && $bFb["avg_sys"] !== null && $bFb["avg_dia"] !== null) {
        $avgSys = round((float)$bFb["avg_sys"]);
        $avgDia = round((float)$bFb["avg_dia"]);
    }
}

$bpClassification = "No readings";
if ($avgSys !== null && $avgDia !== null) {
    if ($avgSys <= 120 && $avgDia <= 80) $bpClassification = "Normal / Optimal";
    elseif ($avgSys <= 129 && $avgDia <= 80) $bpClassification = "Elevated";
    elseif ($avgSys <= 139 || $avgDia <= 89) $bpClassification = "Stage 1 Hypertension";
    else $bpClassification = "Stage 2 Hypertension";
}

// 3. Activity & Steps in Period
$stepStmt = $conn->prepare("SELECT SUM(step_count) as total_steps, AVG(step_count) as avg_steps, SUM(distance_km) as total_dist, SUM(calories_kcal) as total_cal, SUM(active_minutes) as total_active, COUNT(*) as days_logged FROM steps_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ?");
$stepStmt->bind_param("iss", $userId, $startDate, $endDate);
$stepStmt->execute();
$stStats = $stepStmt->get_result()->fetch_assoc();
$stepStmt->close();

$totalSteps = ($stStats && $stStats["total_steps"] !== null) ? (int)$stStats["total_steps"] : 0;
$avgDailySteps = ($stStats && $stStats["avg_steps"] !== null) ? (int)round((float)$stStats["avg_steps"]) : 0;
$totalDistanceKm = ($stStats && $stStats["total_dist"] !== null) ? round((float)$stStats["total_dist"], 2) : round($totalSteps * 0.00075, 2);
$totalCaloriesBurned = ($stStats && $stStats["total_cal"] !== null) ? (int)$stStats["total_cal"] : (int)round($totalSteps * 0.04);
$totalActiveMinutes = ($stStats && $stStats["total_active"] !== null) ? (int)$stStats["total_active"] : (int)round($totalSteps / 100);

// 4. Sleep in Period
$slStmt = $conn->prepare("SELECT SUM(duration_minutes) as total_sleep, AVG(duration_minutes) as avg_sleep, COUNT(CASE WHEN duration_minutes >= 480 THEN 1 END) as optimal_nights, COUNT(*) as sleep_days FROM sleep_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ? AND duration_minutes > 0");
$slStmt->bind_param("iss", $userId, $startDate, $endDate);
$slStmt->execute();
$slStats = $slStmt->get_result()->fetch_assoc();
$slStmt->close();

$totalSleepMin = ($slStats && $slStats["total_sleep"] !== null) ? (int)$slStats["total_sleep"] : 0;
$avgSleepMin = ($slStats && $slStats["avg_sleep"] !== null) ? (int)round((float)$slStats["avg_sleep"]) : 0;
$optimalNights = ($slStats && $slStats["optimal_nights"] !== null) ? (int)$slStats["optimal_nights"] : 0;
$sleepDaysCount = ($slStats && $slStats["sleep_days"] !== null) ? (int)$slStats["sleep_days"] : 0;
$optimalNightsPct = $sleepDaysCount > 0 ? (int)round(($optimalNights / $sleepDaysCount) * 100) : 0;

$avgSleepHoursFormatted = (intdiv($avgSleepMin, 60)) . "h " . ($avgSleepMin % 60) . "m";

// 5. Hydration in Period
$hyStmt = $conn->prepare("SELECT SUM(COALESCE(intake_ml, amount_ml)) as total_intake, AVG(COALESCE(intake_ml, amount_ml)) as avg_intake, COUNT(*) as hyd_days FROM hydration_logs WHERE user_id = ? AND (logged_date BETWEEN ? AND ? OR date_string BETWEEN ? AND ?)");
$hyStmt->bind_param("issss", $userId, $startDate, $endDate, $startDate, $endDate);
$hyStmt->execute();
$hyStats = $hyStmt->get_result()->fetch_assoc();
$hyStmt->close();

$totalHydrationMl = ($hyStats && $hyStats["total_intake"] !== null) ? (int)$hyStats["total_intake"] : 0;
$avgHydrationMl = ($hyStats && $hyStats["avg_intake"] !== null) ? (int)round((float)$hyStats["avg_intake"]) : 0;
$totalHydrationLiters = round($totalHydrationMl / 1000.0, 2);

// 6. Workouts in Period
$woStmt = $conn->prepare("SELECT COUNT(*) as total_workouts, SUM(duration_seconds) as total_dur_sec, SUM(calories_burned) as total_cal FROM workouts WHERE user_id = ? AND DATE(started_at) BETWEEN ? AND ?");
$woStmt->bind_param("iss", $userId, $startDate, $endDate);
$woStmt->execute();
$woStats = $woStmt->get_result()->fetch_assoc();
$woStmt->close();

$totalWorkouts = ($woStats && $woStats["total_workouts"] !== null) ? (int)$woStats["total_workouts"] : 0;
$totalWorkoutSec = ($woStats && $woStats["total_dur_sec"] !== null) ? (int)$woStats["total_dur_sec"] : 0;
$totalWorkoutMin = (int)round($totalWorkoutSec / 60.0);

// Also add activity_sessions if present
$asStmt = $conn->prepare("SELECT COUNT(*) as act_count, SUM(duration_seconds) as act_dur_sec FROM activity_sessions WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?");
if ($asStmt) {
    $asStmt->bind_param("iss", $userId, $startDate, $endDate);
    $asStmt->execute();
    $asRow = $asStmt->get_result()->fetch_assoc();
    $asStmt->close();
    if ($asRow) {
        $totalWorkouts += (int)$asRow["act_count"];
        $totalWorkoutMin += (int)round(((int)$asRow["act_dur_sec"]) / 60.0);
    }
}

// 7. Clinical & Wellness Summary
$scorePieces = [];
if ($avgDailySteps > 0) $scorePieces[] = min(100, (int)round(($avgDailySteps / 8000.0) * 100));
if ($avgSleepMin > 0) $scorePieces[] = $avgSleepMin >= 480 ? 100 : ($avgSleepMin >= 360 ? 80 : 50);
if ($avgHydrationMl > 0) $scorePieces[] = min(100, (int)round(($avgHydrationMl / 2000.0) * 100));
if ($avgHr !== null) $scorePieces[] = ($avgHr >= 60 && $avgHr <= 100) ? 100 : 75;

$overallScore = count($scorePieces) > 0 ? (int)round(array_sum($scorePieces) / count($scorePieces)) : null;
$scoreGrade = $overallScore !== null ? ($overallScore >= 85 ? "A (Excellent)" : ($overallScore >= 70 ? "B (Good)" : "C (Needs Attention)")) : "N/A";

// Observations for PDF builder
$observations = [];
if ($avgDailySteps >= 8000) {
    $observations[] = "Physical activity goal achieved with an average of " . number_format($avgDailySteps) . " steps/day.";
} elseif ($avgDailySteps > 0) {
    $observations[] = "Daily physical activity averaged " . number_format($avgDailySteps) . " steps/day. Target: 8,000 steps/day.";
}

if ($avgSleepMin >= 420) {
    $observations[] = "Healthy restorative sleep pattern maintained at {$avgSleepHoursFormatted} average nightly rest.";
} elseif ($avgSleepMin > 0) {
    $observations[] = "Average sleep was {$avgSleepHoursFormatted}. Aim for 7 to 8 hours nightly.";
}

if ($avgSys !== null && $avgDia !== null) {
    $observations[] = "Blood pressure averaged {$avgSys}/{$avgDia} mmHg ({$bpClassification}).";
}

if ($avgSpo2 !== null) {
    $observations[] = "Blood oxygen saturation averaged {$avgSpo2}%, reflecting good respiratory health.";
}

if (empty($observations)) {
    $observations[] = "Initial monitoring period. Continue logging daily vitals to generate longitudinal trends.";
}

respondJson(200, [
    "status" => "success",
    "message" => "Summary report generated successfully",
    "data" => [
        "report_id" => "VS-REP-" . date("Ymd") . "-$userId",
        "generated_at" => date("Y-m-d H:i:s"),
        "period" => $period,
        "date_range" => [
            "start_date" => $startDate,
            "end_date" => $endDate
        ],
        "user_profile" => [
            "user_id" => $userId,
            "name" => $userName,
            "email" => $userEmail,
            "age" => $userAge,
            "gender" => $userGender,
            "height_cm" => $userHeight,
            "weight_kg" => $userWeight,
            "bmi" => $userBmi,
            "bmi_category" => $userBmiCategory
        ],
        "executive_summary" => [
            "wellness_score" => $overallScore,
            "grade" => $scoreGrade,
            "status_headline" => $overallScore >= 80 ? "Optimal Health Performance" : "Stable Baseline with Opportunities to Improve",
            "observations" => $observations
        ],
        "vitals_summary" => [
            "heart_rate" => [
                "avg_bpm" => $avgHr,
                "min_bpm" => $minHr,
                "max_bpm" => $maxHr,
                "status" => ($avgHr !== null && $avgHr >= 60 && $avgHr <= 100) ? "Normal" : "Review Needed"
            ],
            "spo2" => [
                "avg_percentage" => $avgSpo2,
                "min_percentage" => $minSpo2,
                "status" => ($avgSpo2 !== null && $avgSpo2 >= 95) ? "Optimal" : "Review Needed"
            ],
            "blood_pressure" => [
                "avg_systolic" => $avgSys,
                "avg_diastolic" => $avgDia,
                "formatted" => ($avgSys !== null && $avgDia !== null) ? "{$avgSys}/{$avgDia} mmHg" : "--/-- mmHg",
                "classification" => $bpClassification
            ]
        ],
        "activity_summary" => [
            "total_steps" => $totalSteps,
            "avg_daily_steps" => $avgDailySteps,
            "total_distance_km" => $totalDistanceKm,
            "total_calories_kcal" => $totalCaloriesBurned,
            "total_active_minutes" => $totalActiveMinutes
        ],
        "sleep_summary" => [
            "total_sleep_hours" => round($totalSleepMin / 60.0, 1),
            "avg_daily_sleep_minutes" => $avgSleepMin,
            "avg_daily_sleep_formatted" => $avgSleepHoursFormatted,
            "optimal_nights_percent" => $optimalNightsPct
        ],
        "hydration_summary" => [
            "total_consumed_liters" => $totalHydrationLiters,
            "avg_daily_intake_ml" => $avgHydrationMl,
            "daily_target_ml" => 2000,
            "target_completion_percent" => min(100, (int)round(($avgHydrationMl / 2000.0) * 100))
        ],
        "workout_summary" => [
            "total_workouts" => $totalWorkouts,
            "total_duration_minutes" => $totalWorkoutMin
        ]
    ]
]);

