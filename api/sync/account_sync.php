<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../config/db_connect.php";
require_once __DIR__ . "/../../config/url_helper.php";

/**
 * Formats duration in minutes to readable "Xh Ym"
 */
function formatDurationMinutes(int $minutes): string {
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0) {
        return "{$h}h {$m}m";
    }
    return "{$m}m";
}

/**
 * Formats seconds to readable "Xm Ys" or "Xh Ym"
 */
function formatDurationSeconds(int $seconds): string {
    if ($seconds >= 3600) {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return "{$h}h {$m}m";
    }
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    return "{$m}m {$s}s";
}

// 1. Resolve User ID from GET / POST or Authorization token
$input = getJsonInput();
$userId = resolveUserId($pdo, array_merge($_GET, $input));

// Check Bearer Token if user_id not in params
if ($userId <= 0 && !empty($_SERVER["HTTP_AUTHORIZATION"])) {
    if (preg_match('/Bearer\s+(\S+)/', $_SERVER["HTTP_AUTHORIZATION"], $matches)) {
        $token = $matches[1];
        if (is_numeric($token)) {
            $userId = (int)$token;
        } else {
            $tStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $tStmt->execute([$token]);
            $tRow = $tStmt->fetch();
            if ($tRow) {
                $userId = (int)$tRow["id"];
            }
        }
    }
}

if ($userId <= 0) {
    sendResponse("error", "Unauthorized or invalid user_id / email provided for cloud sync", null, 401);
}

// 2. Ensure the user exists in `users`
$uStmt = $pdo->prepare("SELECT id, email, COALESCE(NULLIF(full_name, ''), name) AS name, profile_image, created_at FROM users WHERE id = ? LIMIT 1");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

if (!$user) {
    sendResponse("error", "User account not found in database", null, 404);
}

// =========================================================================
// TWO-WAY SYNC (POST): INGEST OFFLINE RECORDS & DELTA UPDATES ATOMICALLY
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $pdo->beginTransaction();

        // 1. Profile Ingestion
        if (!empty($input["profile"]) && is_array($input["profile"])) {
            $p = $input["profile"];
            $fullName = !empty($p["full_name"]) ? trim($p["full_name"]) : (!empty($p["name"]) ? trim($p["name"]) : $user["name"]);
            $age = isset($p["age"]) && is_numeric($p["age"]) ? (int)$p["age"] : null;
            $gender = !empty($p["gender"]) ? trim($p["gender"]) : null;
            $heightCm = isset($p["height_cm"]) && is_numeric($p["height_cm"]) ? (float)$p["height_cm"] : (isset($p["height"]) ? (float)$p["height"] : null);
            $weightKg = isset($p["weight_kg"]) && is_numeric($p["weight_kg"]) ? (float)$p["weight_kg"] : (isset($p["weight"]) ? (float)$p["weight"] : null);
            $bloodGroup = !empty($p["blood_group"]) ? trim($p["blood_group"]) : null;
            $phone = !empty($p["phone"]) ? trim($p["phone"]) : null;
            $avatarUrl = !empty($p["avatar_url"]) ? trim($p["avatar_url"]) : null;

            $upsertProfile = $pdo->prepare("
                INSERT INTO user_profiles (user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    age = COALESCE(VALUES(age), age),
                    gender = COALESCE(VALUES(gender), gender),
                    height_cm = COALESCE(VALUES(height_cm), height_cm),
                    weight_kg = COALESCE(VALUES(weight_kg), weight_kg),
                    blood_group = COALESCE(VALUES(blood_group), blood_group),
                    phone = COALESCE(VALUES(phone), phone),
                    avatar_url = COALESCE(VALUES(avatar_url), avatar_url),
                    updated_at = NOW()
            ");
            $upsertProfile->execute([$userId, $fullName, $age, $gender, $heightCm, $weightKg, $bloodGroup, $phone, $avatarUrl]);

            if ($fullName !== $user["name"]) {
                $uName = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
                $uName->execute([$fullName, $userId]);
            }
        }

        // 2. Health Goals Ingestion
        if (!empty($input["health_goals"]) && is_array($input["health_goals"])) {
            $g = $input["health_goals"];
            $pGoal = !empty($g["primary_goal"]) ? trim($g["primary_goal"]) : "Improve Fitness";
            $tSteps = isset($g["target_steps"]) ? (int)$g["target_steps"] : 10000;
            $tSleep = isset($g["target_sleep_hours"]) ? (float)$g["target_sleep_hours"] : 8.0;
            $tWater = isset($g["target_water_ml"]) ? (int)$g["target_water_ml"] : 2500;
            $tCalories = isset($g["target_calories"]) ? (int)$g["target_calories"] : 2200;
            $tWeight = isset($g["target_weight_kg"]) && is_numeric($g["target_weight_kg"]) ? (float)$g["target_weight_kg"] : null;

            $upsertGoals = $pdo->prepare("
                INSERT INTO user_health_goals (user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_calories, target_weight_kg)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    primary_goal = VALUES(primary_goal),
                    target_steps = VALUES(target_steps),
                    target_sleep_hours = VALUES(target_sleep_hours),
                    target_water_ml = VALUES(target_water_ml),
                    target_calories = VALUES(target_calories),
                    target_weight_kg = COALESCE(VALUES(target_weight_kg), target_weight_kg),
                    updated_at = NOW()
            ");
            $upsertGoals->execute([$userId, $pGoal, $tSteps, $tSleep, $tWater, $tCalories, $tWeight]);
        }

        // 3. App Settings Ingestion
        if (!empty($input["app_settings"]) && is_array($input["app_settings"])) {
            $s = $input["app_settings"];
            $push = isset($s["push_notifications"]) ? ($s["push_notifications"] ? 1 : 0) : 1;
            $vital = isset($s["vital_alerts"]) ? ($s["vital_alerts"] ? 1 : 0) : 1;
            $daily = isset($s["daily_reminders"]) ? ($s["daily_reminders"] ? 1 : 0) : 1;
            $units = !empty($s["metric_units"]) ? trim($s["metric_units"]) : "metric";
            $dark = isset($s["dark_mode"]) ? ($s["dark_mode"] ? 1 : 0) : 0;

            $upsertSettings = $pdo->prepare("
                INSERT INTO user_app_settings (user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    push_notifications = VALUES(push_notifications),
                    vital_alerts = VALUES(vital_alerts),
                    daily_reminders = VALUES(daily_reminders),
                    metric_units = VALUES(metric_units),
                    dark_mode = VALUES(dark_mode),
                    updated_at = NOW()
            ");
            $upsertSettings->execute([$userId, $push, $vital, $daily, $units, $dark]);
        }

        // 4. Batch Workouts Ingestion
        if (!empty($input["workouts"]) && is_array($input["workouts"])) {
            $wStmt = $pdo->prepare("
                INSERT INTO workout_sessions (
                    user_id, session_uuid, activity_type, start_time, end_time,
                    duration_seconds, target_type, target_value, steps, distance_km,
                    avg_pace, calories, tracking_source, distance_meters, avg_heart_rate
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    activity_type = VALUES(activity_type),
                    start_time = VALUES(start_time),
                    end_time = VALUES(end_time),
                    duration_seconds = VALUES(duration_seconds),
                    target_type = VALUES(target_type),
                    target_value = VALUES(target_value),
                    steps = VALUES(steps),
                    distance_km = VALUES(distance_km),
                    avg_pace = VALUES(avg_pace),
                    calories = VALUES(calories),
                    tracking_source = VALUES(tracking_source),
                    distance_meters = VALUES(distance_meters),
                    avg_heart_rate = VALUES(avg_heart_rate)
            ");
            foreach ($input["workouts"] as $w) {
                if (empty($w) || !is_array($w)) continue;
                $uuid = !empty($w["session_uuid"]) ? trim($w["session_uuid"]) : bin2hex(random_bytes(16));
                $actType = in_array(ucfirst(strtolower($w["activity_type"] ?? "")), ["Cycling", "Running", "Walking"]) ? ucfirst(strtolower($w["activity_type"])) : "Walking";
                $st = !empty($w["start_time"]) ? trim($w["start_time"]) : date("Y-m-d H:i:s");
                $dur = isset($w["duration_seconds"]) ? (int)$w["duration_seconds"] : 0;
                $et = !empty($w["end_time"]) ? trim($w["end_time"]) : date("Y-m-d H:i:s", strtotime($st) + $dur);
                $targetType = in_array(strtolower($w["target_type"] ?? ""), ["time", "distance"]) ? strtolower($w["target_type"]) : "time";
                $targetVal = isset($w["target_value"]) ? (int)$w["target_value"] : 45;
                $steps = isset($w["steps"]) ? (int)$w["steps"] : 0;
                $distKm = isset($w["distance_km"]) ? (float)$w["distance_km"] : (isset($w["distance_meters"]) ? round((float)$w["distance_meters"] / 1000.0, 2) : 0.00);
                $distM = isset($w["distance_meters"]) ? (float)$w["distance_meters"] : ($distKm * 1000.0);
                $pace = !empty($w["avg_pace"]) ? trim($w["avg_pace"]) : ($distKm > 0 ? gmdate("i:s", (int)($dur / $distKm)) . " /km" : "00:00 /km");
                $cals = isset($w["calories"]) ? (int)$w["calories"] : 0;
                $source = !empty($w["tracking_source"]) ? trim($w["tracking_source"]) : "phone_sensor";
                $avgHr = isset($w["avg_heart_rate"]) && is_numeric($w["avg_heart_rate"]) ? (int)$w["avg_heart_rate"] : null;

                $wStmt->execute([$userId, $uuid, $actType, $st, $et, $dur, $targetType, $targetVal, $steps, $distKm, $pace, $cals, $source, $distM, $avgHr]);
            }
        }

        // 5. Batch Vitals Ingestion
        if (!empty($input["vitals"]) && is_array($input["vitals"])) {
            $vStmt = $pdo->prepare("
                INSERT INTO vitals_logs (user_id, heart_rate, spo2, systolic_bp, diastolic_bp, source, logged_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($input["vitals"] as $v) {
                if (empty($v) || !is_array($v)) continue;
                $hr = isset($v["heart_rate"]) && is_numeric($v["heart_rate"]) ? (int)$v["heart_rate"] : null;
                $spo2 = isset($v["spo2"]) && is_numeric($v["spo2"]) ? (int)$v["spo2"] : null;
                $sys = isset($v["systolic_bp"]) && is_numeric($v["systolic_bp"]) ? (int)$v["systolic_bp"] : null;
                $dia = isset($v["diastolic_bp"]) && is_numeric($v["diastolic_bp"]) ? (int)$v["diastolic_bp"] : null;
                $src = !empty($v["source"]) ? substr(trim($v["source"]), 0, 20) : "bluetooth";
                $logAt = !empty($v["logged_at"]) ? trim($v["logged_at"]) : date("Y-m-d H:i:s");

                if ($hr !== null || $spo2 !== null || $sys !== null) {
                    $vStmt->execute([$userId, $hr, $spo2, $sys, $dia, $src, $logAt]);
                }
            }
        }

        // 6. Sleep Log Ingestion
        if (!empty($input["sleep"]) && is_array($input["sleep"])) {
            $sl = $input["sleep"];
            $durMin = isset($sl["duration_minutes"]) ? (int)$sl["duration_minutes"] : 0;
            $score = isset($sl["quality_score"]) ? (int)$sl["quality_score"] : 80;
            $logDate = !empty($sl["logged_at"]) ? trim($sl["logged_at"]) : (!empty($sl["logged_date"]) ? trim($sl["logged_date"]) : date("Y-m-d"));
            $stTime = !empty($sl["start_time"]) ? trim($sl["start_time"]) : date("Y-m-d H:i:s", strtotime("$logDate 23:00"));
            $endTime = !empty($sl["end_time"]) ? trim($sl["end_time"]) : date("Y-m-d H:i:s", strtotime($stTime) + ($durMin * 60));

            if ($durMin > 0) {
                $checkSl = $pdo->prepare("SELECT id FROM sleep_logs WHERE user_id = ? AND logged_at = ? LIMIT 1");
                $checkSl->execute([$userId, $logDate]);
                $existSl = $checkSl->fetch();
                if ($existSl) {
                    $uSl = $pdo->prepare("UPDATE sleep_logs SET start_time = ?, end_time = ?, duration_minutes = ?, quality_score = ? WHERE id = ?");
                    $uSl->execute([$stTime, $endTime, $durMin, $score, $existSl["id"]]);
                } else {
                    $iSl = $pdo->prepare("INSERT INTO sleep_logs (user_id, start_time, end_time, duration_minutes, quality_score, logged_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $iSl->execute([$userId, $stTime, $endTime, $durMin, $score, $logDate]);
                }
            }
        }

        // 7. Hydration Log Ingestion
        if (!empty($input["hydration"]) && is_array($input["hydration"])) {
            $hy = $input["hydration"];
            $vol = isset($hy["volume_ml"]) ? (int)$hy["volume_ml"] : (isset($hy["amount_ml"]) ? (int)$hy["amount_ml"] : 0);
            $hyDate = !empty($hy["log_date"]) ? trim($hy["log_date"]) : (!empty($hy["date_string"]) ? trim($hy["date_string"]) : date("Y-m-d"));
            $hyAt = !empty($hy["logged_at"]) ? trim($hy["logged_at"]) : date("Y-m-d H:i:s");

            if ($vol > 0) {
                $iHy = $pdo->prepare("INSERT INTO hydration_logs (user_id, volume_ml, log_date, logged_at) VALUES (?, ?, ?, ?)");
                $iHy->execute([$userId, $vol, $hyDate, $hyAt]);
            }
        }

        $pdo->commit();
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse("error", "Cloud sync transaction failed: " . $e->getMessage(), null, 500);
    }
}

// =========================================================================
// REHYDRATION ENGINE: ASSEMBLE FULL INSTAGRAM-STYLE CLOUD ACCOUNT SNAPSHOT
// =========================================================================

// 1. User Profile
$pStmt = $pdo->prepare("SELECT full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
$pStmt->execute([$userId]);
$profile = $pStmt->fetch();

if (!$profile) {
    // Auto-provision profile from users table
    $insP = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
    $insP->execute([$userId, $user["name"]]);

    $pStmt->execute([$userId]);
    $profile = $pStmt->fetch();
}

$avatarFull = resolveAvatarUrl(!empty($user["profile_image"]) ? $user["profile_image"] : ($profile["avatar_url"] ?? null));

// Calculate BMI
$bmi = null;
$bmiCategory = "Not Available";
if (!empty($profile["height_cm"]) && !empty($profile["weight_kg"]) && (float)$profile["height_cm"] > 0) {
    $hM = (float)$profile["height_cm"] / 100.0;
    $bmi = round((float)$profile["weight_kg"] / ($hM * $hM), 1);
    if ($bmi < 18.5) $bmiCategory = "Underweight";
    elseif ($bmi <= 24.9) $bmiCategory = "Normal";
    elseif ($bmi <= 29.9) $bmiCategory = "Overweight";
    else $bmiCategory = "Obese";
}

// 2. Health Goals
$gStmt = $pdo->prepare("SELECT primary_goal, target_steps, target_sleep_hours, target_water_ml, target_calories, target_weight_kg, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
$gStmt->execute([$userId]);
$goals = $gStmt->fetch();

if (!$goals) {
    $insG = $pdo->prepare("INSERT INTO user_health_goals (user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_calories) VALUES (?, 'Improve Fitness', 10000, 8.0, 2500, 2200) ON DUPLICATE KEY UPDATE user_id = user_id");
    $insG->execute([$userId]);

    $gStmt->execute([$userId]);
    $goals = $gStmt->fetch();
}

// 3. App Settings
$sStmt = $pdo->prepare("SELECT push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
$sStmt->execute([$userId]);
$settings = $sStmt->fetch();

if (!$settings) {
    $insS = $pdo->prepare("INSERT INTO user_app_settings (user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode) VALUES (?, 1, 1, 1, 'metric', 0) ON DUPLICATE KEY UPDATE user_id = user_id");
    $insS->execute([$userId]);

    $sStmt->execute([$userId]);
    $settings = $sStmt->fetch();
}

// 4. Today's Consolidated Live Metrics & Progress
$today = date("Y-m-d");

// Daily Workouts Aggregation (Steps, Calories, Distance)
$wAggStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(steps), 0) AS total_steps,
        COALESCE(SUM(calories), 0) AS total_calories,
        COALESCE(SUM(CASE WHEN distance_km > 0 THEN distance_km ELSE distance_meters/1000.0 END), 0.0) AS total_distance
    FROM workout_sessions 
    WHERE user_id = ? AND DATE(start_time) = ?
");
$wAggStmt->execute([$userId, $today]);
$wAgg = $wAggStmt->fetch();

$todaySteps = (int)$wAgg["total_steps"];
$todayCalories = (int)$wAgg["total_calories"];
$todayDistanceKm = round((float)$wAgg["total_distance"], 2);
$todayDistanceMeters = $todayDistanceKm * 1000.0;

$targetSteps = (int)($goals["target_steps"] ?: 10000);
$stepsProgress = $targetSteps > 0 ? min(100, (int)round(($todaySteps / $targetSteps) * 100)) : 0;

$targetCalories = (int)($goals["target_calories"] ?: 2200);
$caloriesProgress = $targetCalories > 0 ? min(100, (int)round(($todayCalories / $targetCalories) * 100)) : 0;

// Daily Hydration Aggregation
$hyAggStmt = $pdo->prepare("
    SELECT COALESCE(SUM(COALESCE(volume_ml, 0)), 0) AS total_consumed
    FROM hydration_logs
    WHERE user_id = ? AND (log_date = ? OR DATE(logged_at) = ?)
");
$hyAggStmt->execute([$userId, $today, $today]);
$hyAgg = $hyAggStmt->fetch();

$todayWaterMl = (int)$hyAgg["total_consumed"];
$targetWaterMl = (int)($goals["target_water_ml"] ?: 2500);
$waterProgress = $targetWaterMl > 0 ? min(100, (int)round(($todayWaterMl / $targetWaterMl) * 100)) : 0;
$waterGlasses = round($todayWaterMl / 250.0, 1);

// Today's Sleep
$slStmt = $pdo->prepare("
    SELECT duration_minutes, quality_score, start_time, end_time, logged_at
    FROM sleep_logs
    WHERE user_id = ? AND logged_at <= ?
    ORDER BY logged_at DESC LIMIT 1
");
$slStmt->execute([$userId, $today]);
$todaySleep = $slStmt->fetch();

$sleepDurationMin = $todaySleep ? (int)$todaySleep["duration_minutes"] : 0;
$sleepQuality = $todaySleep && $todaySleep["quality_score"] !== null ? (int)$todaySleep["quality_score"] : 0;
$targetSleepHours = (float)($goals["target_sleep_hours"] ?: 8.0);
$targetSleepMin = (int)round($targetSleepHours * 60);
$sleepProgress = $targetSleepMin > 0 ? min(100, (int)round(($sleepDurationMin / $targetSleepMin) * 100)) : 0;

// Latest Vitals (Heart Rate, SpO2, Blood Pressure)
$hrStmt = $pdo->prepare("SELECT heart_rate, logged_at FROM vitals_logs WHERE user_id = ? AND heart_rate IS NOT NULL ORDER BY logged_at DESC LIMIT 1");
$hrStmt->execute([$userId]);
$latestHrRow = $hrStmt->fetch();

$latestHr = $latestHrRow ? (int)$latestHrRow["heart_rate"] : null;
$hrStatus = "No data";
if ($latestHr !== null) {
    if ($latestHr < 60) $hrStatus = "Low Rate";
    elseif ($latestHr <= 90) $hrStatus = "Normal";
    else $hrStatus = "High Rate";
}

$spo2Stmt = $pdo->prepare("SELECT spo2, logged_at FROM vitals_logs WHERE user_id = ? AND spo2 IS NOT NULL ORDER BY logged_at DESC LIMIT 1");
$spo2Stmt->execute([$userId]);
$latestSpo2Row = $spo2Stmt->fetch();

$latestSpo2 = $latestSpo2Row ? (int)$latestSpo2Row["spo2"] : null;
$spo2Status = "No data";
if ($latestSpo2 !== null) {
    $spo2Status = ($latestSpo2 >= 95) ? "Optimal" : "Low";
}

$bpStmt = $pdo->prepare("SELECT systolic_bp, diastolic_bp, logged_at FROM vitals_logs WHERE user_id = ? AND systolic_bp IS NOT NULL ORDER BY logged_at DESC LIMIT 1");
$bpStmt->execute([$userId]);
$latestBpRow = $bpStmt->fetch();

$latestSys = $latestBpRow ? (int)$latestBpRow["systolic_bp"] : null;
$latestDia = $latestBpRow ? (int)$latestBpRow["diastolic_bp"] : null;
$bpStatus = "No data";
$bpFormatted = null;
if ($latestSys !== null && $latestDia !== null) {
    $bpFormatted = "{$latestSys}/{$latestDia} mmHg";
    if ($latestSys < 120 && $latestDia < 80) $bpStatus = "Normal";
    elseif ($latestSys <= 129 && $latestDia < 80) $bpStatus = "Elevated";
    else $bpStatus = "Hypertension";
}

// 5. Workout Sessions History (Multi-session: Walking, Cycling, Running)
$wkHistoryStmt = $pdo->prepare("
    SELECT id, session_uuid, activity_type, start_time, end_time, duration_seconds, target_type, target_value, distance_km, distance_meters, steps, avg_pace, calories, tracking_source, avg_heart_rate, created_at
    FROM workout_sessions
    WHERE user_id = ?
    ORDER BY start_time DESC LIMIT 25
");
$wkHistoryStmt->execute([$userId]);
$rawWorkouts = $wkHistoryStmt->fetchAll();

$workoutSessions = [];
foreach ($rawWorkouts as $w) {
    $durSec = (int)$w["duration_seconds"];
    $distKm = (float)$w["distance_km"] > 0 ? (float)$w["distance_km"] : round((float)$w["distance_meters"] / 1000.0, 2);
    $distM = (float)$w["distance_meters"] > 0 ? (float)$w["distance_meters"] : ($distKm * 1000.0);
    $pace = !empty($w["avg_pace"]) && $w["avg_pace"] !== "00:00 /km" ? $w["avg_pace"] : ($distKm > 0 ? gmdate("i:s", (int)($durSec / $distKm)) . " /km" : "00:00 /km");
    $workoutSessions[] = [
        "id" => (int)$w["id"],
        "session_uuid" => $w["session_uuid"],
        "activity_type" => $w["activity_type"],
        "start_time" => $w["start_time"],
        "end_time" => $w["end_time"],
        "duration_seconds" => $durSec,
        "duration_formatted" => formatDurationSeconds($durSec),
        "target_type" => $w["target_type"] ?? "time",
        "target_value" => (int)($w["target_value"] ?? 45),
        "distance_meters" => $distM,
        "distance_km" => $distKm,
        "steps" => (int)$w["steps"],
        "avg_pace" => $pace,
        "calories" => (int)$w["calories"],
        "tracking_source" => $w["tracking_source"] ?? "phone_sensor",
        "avg_heart_rate" => $w["avg_heart_rate"] !== null ? (int)$w["avg_heart_rate"] : null,
        "created_at" => $w["created_at"]
    ];
}

// 6. 7-Day Sleep Log History
$sleepHistoryStmt = $pdo->prepare("
    SELECT id, start_time, end_time, duration_minutes, quality_score, logged_at, created_at
    FROM sleep_logs
    WHERE user_id = ?
    ORDER BY logged_at DESC LIMIT 7
");
$sleepHistoryStmt->execute([$userId]);
$rawSleep = $sleepHistoryStmt->fetchAll();

$sleepHistory = [];
foreach ($rawSleep as $s) {
    $durM = (int)$s["duration_minutes"];
    $sleepHistory[] = [
        "id" => (int)$s["id"],
        "date" => $s["logged_at"],
        "logged_at" => $s["logged_at"],
        "start_time" => $s["start_time"],
        "end_time" => $s["end_time"],
        "duration_minutes" => $durM,
        "duration_formatted" => formatDurationMinutes($durM),
        "quality_score" => $s["quality_score"] !== null ? (int)$s["quality_score"] : null,
        "created_at" => $s["created_at"]
    ];
}

// 7. 7-Day Hydration History
$dayOfWeek = (int)date("N", strtotime($today));
$monday = date("Y-m-d", strtotime($today . " - " . ($dayOfWeek - 1) . " days"));
$sunday = date("Y-m-d", strtotime($monday . " + 6 days"));

$hyHistoryStmt = $pdo->prepare("
    SELECT log_date, SUM(volume_ml) as total_ml
    FROM hydration_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    GROUP BY log_date
");
$hyHistoryStmt->execute([$userId, $monday, $sunday]);
$hyMap = [];
while ($row = $hyHistoryStmt->fetch()) {
    $hyMap[$row["log_date"]] = (int)$row["total_ml"];
}

$dayLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
$weeklyHydration = [];
for ($i = 0; $i < 7; $i++) {
    $dStr = date("Y-m-d", strtotime($monday . " + $i days"));
    $vol = $hyMap[$dStr] ?? 0;
    $weeklyHydration[] = [
        "day" => $dayLabels[$i],
        "date" => $dStr,
        "volume_ml" => $vol,
        "target_ml" => $targetWaterMl,
        "progress_percent" => $targetWaterMl > 0 ? min(100, (int)round(($vol / $targetWaterMl) * 100)) : 0,
        "is_today" => ($dStr === $today)
    ];
}

// 8. Time-Series Vital History (Latest 30 records)
$vitalsHistoryStmt = $pdo->prepare("
    SELECT id, heart_rate, spo2, systolic_bp, diastolic_bp, source, logged_at
    FROM vitals_logs
    WHERE user_id = ?
    ORDER BY logged_at DESC LIMIT 30
");
$vitalsHistoryStmt->execute([$userId]);
$rawVitals = $vitalsHistoryStmt->fetchAll();

$vitalsHistory = [];
foreach ($rawVitals as $v) {
    $vitalsHistory[] = [
        "id" => (int)$v["id"],
        "heart_rate" => $v["heart_rate"] !== null ? (int)$v["heart_rate"] : null,
        "spo2" => $v["spo2"] !== null ? (int)$v["spo2"] : null,
        "systolic_bp" => $v["systolic_bp"] !== null ? (int)$v["systolic_bp"] : null,
        "diastolic_bp" => $v["diastolic_bp"] !== null ? (int)$v["diastolic_bp"] : null,
        "source" => $v["source"],
        "logged_at" => $v["logged_at"]
    ];
}

// 9. Active Reminders
$reminders = [];
try {
    $remStmt = $pdo->prepare("SELECT id, title, time, frequency, is_enabled FROM reminders WHERE user_id = ? ORDER BY id ASC");
    $remStmt->execute([$userId]);
    while ($r = $remStmt->fetch()) {
        $reminders[] = [
            "id" => (int)$r["id"],
            "title" => $r["title"],
            "reminder_type" => "general",
            "time" => $r["time"],
            "frequency" => $r["frequency"],
            "is_enabled" => (bool)$r["is_enabled"]
        ];
    }
} catch (\Throwable $e) {
    $reminders = [];
}

// 10. Paired Bluetooth Devices (legacy compatibility)
$devices = [];
try {
    $devStmt = $pdo->prepare("SELECT id, device_name, mac_address, device_category, is_connected, battery_level, last_synced_at FROM user_devices WHERE user_id = ?");
    $devStmt->execute([$userId]);
    while ($d = $devStmt->fetch()) {
        $devices[] = [
            "id" => (int)$d["id"],
            "device_name" => $d["device_name"],
            "mac_address" => $d["mac_address"],
            "device_category" => $d["device_category"],
            "is_connected" => (bool)$d["is_connected"],
            "battery_level" => $d["battery_level"] !== null ? (int)$d["battery_level"] : null,
            "last_synced_at" => $d["last_synced_at"]
        ];
    }
} catch (\Throwable $e) {
    $devices = [];
}

// =========================================================================
// ASSEMBLE COMPLETE PRODUCTION RESPONSE (REHYDRATION BUNDLE)
// =========================================================================
$rehydrationSnapshot = [
    "synced_at" => date("c"),
    "user" => [
        "id" => (int)$user["id"],
        "email" => $user["email"],
        "name" => $profile["full_name"] ?: $user["name"],
        "created_at" => $user["created_at"],
        "avatar_url" => $avatarFull
    ],
    "profile" => [
        "user_id" => (int)$userId,
        "full_name" => $profile["full_name"] ?: $user["name"],
        "age" => $profile["age"] !== null ? (int)$profile["age"] : null,
        "gender" => $profile["gender"],
        "height_cm" => $profile["height_cm"] !== null ? (float)$profile["height_cm"] : null,
        "weight_kg" => $profile["weight_kg"] !== null ? (float)$profile["weight_kg"] : null,
        "blood_group" => $profile["blood_group"],
        "phone" => $profile["phone"],
        "avatar_url" => $avatarFull,
        "bmi" => $bmi,
        "bmi_category" => $bmiCategory,
        "updated_at" => $profile["updated_at"]
    ],
    "health_goals" => [
        "user_id" => (int)$userId,
        "primary_goal" => $goals["primary_goal"] ?: "Improve Fitness",
        "target_steps" => $targetSteps,
        "target_sleep_hours" => $targetSleepHours,
        "target_water_ml" => $targetWaterMl,
        "target_calories" => $targetCalories,
        "target_weight_kg" => $goals["target_weight_kg"] !== null ? (float)$goals["target_weight_kg"] : null,
        "updated_at" => $goals["updated_at"]
    ],
    "app_settings" => [
        "user_id" => (int)$userId,
        "push_notifications" => (bool)$settings["push_notifications"],
        "vital_alerts" => (bool)$settings["vital_alerts"],
        "daily_reminders" => (bool)$settings["daily_reminders"],
        "metric_units" => $settings["metric_units"] ?: "metric",
        "dark_mode" => (bool)$settings["dark_mode"],
        "updated_at" => $settings["updated_at"]
    ],
    "dashboard_summary" => [
        "today_date" => $today,
        "steps" => [
            "current" => $todaySteps,
            "target" => $targetSteps,
            "progress_percent" => $stepsProgress
        ],
        "calories" => [
            "current" => $todayCalories,
            "target" => $targetCalories,
            "progress_percent" => $caloriesProgress
        ],
        "distance" => [
            "meters" => $todayDistanceMeters,
            "km" => $todayDistanceKm
        ],
        "hydration" => [
            "consumed_ml" => $todayWaterMl,
            "target_ml" => $targetWaterMl,
            "progress_percent" => $waterProgress,
            "glasses" => $waterGlasses
        ],
        "sleep" => [
            "duration_minutes" => $sleepDurationMin,
            "duration_formatted" => formatDurationMinutes($sleepDurationMin),
            "quality_score" => $sleepQuality,
            "target_hours" => $targetSleepHours,
            "progress_percent" => $sleepProgress
        ],
        "vitals" => [
            "heart_rate" => [
                "bpm" => $latestHr,
                "status" => $hrStatus
            ],
            "spo2" => [
                "percentage" => $latestSpo2,
                "status" => $spo2Status
            ],
            "blood_pressure" => [
                "systolic" => $latestSys,
                "diastolic" => $latestDia,
                "formatted" => $bpFormatted,
                "status" => $bpStatus
            ]
        ]
    ],
    "workouts" => $workoutSessions,
    "sleep_history" => $sleepHistory,
    "hydration_history" => $weeklyHydration,
    "vitals_history" => $vitalsHistory,
    "reminders" => $reminders,
    "devices" => $devices
];

sendResponse("success", "Account state rehydrated successfully from cloud database", $rehydrationSnapshot, 200, [
    "account_sync" => true,
    "records_synced" => count($workoutSessions) + count($sleepHistory) + count($vitalsHistory)
]);

