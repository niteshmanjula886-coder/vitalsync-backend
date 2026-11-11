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

$date = !empty($_GET["date"]) ? trim((string)$_GET["date"]) : date("Y-m-d");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date("Y-m-d");
}

$hasAnyLog = false;
$scoreComponents = [];

// 1. Fetch latest Heart Rate (from vitals_logs or vitals_readings)
$hrData = null;
$hrRow = null;
$stmtHr = $conn->prepare("SELECT heart_rate, logged_at FROM vitals_logs WHERE user_id = ? AND heart_rate IS NOT NULL AND heart_rate > 0 ORDER BY logged_at DESC LIMIT 1");
if ($stmtHr) {
    $stmtHr->bind_param("i", $userId);
    $stmtHr->execute();
    $hrRow = $stmtHr->get_result()->fetch_assoc();
    $stmtHr->close();
}

if (!$hrRow) {
    // Fallback to vitals_readings
    $hrRow2 = null;
    $stmtHr2 = $conn->prepare("SELECT value_numeric, status_label, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' ORDER BY recorded_at DESC LIMIT 1");
    if ($stmtHr2) {
        $stmtHr2->bind_param("i", $userId);
        $stmtHr2->execute();
        $hrRow2 = $stmtHr2->get_result()->fetch_assoc();
        $stmtHr2->close();
    }
    if ($hrRow2) {
        $bpm = (int)$hrRow2["value_numeric"];
        $status = $hrRow2["status_label"] ?: "Normal";
        $hrData = [
            "bpm" => $bpm,
            "status" => $status,
            "logged_at" => $hrRow2["recorded_at"]
        ];
        $hasAnyLog = true;
        $scoreComponents["hr"] = ($bpm >= 60 && $bpm <= 100) ? 100 : (($bpm >= 50 && $bpm <= 110) ? 80 : 60);
    }
} else {
    $bpm = (int)$hrRow["heart_rate"];
    $status = ($bpm >= 60 && $bpm <= 100) ? "Normal" : ($bpm < 60 ? "Low Rate" : "Elevated");
    $hrData = [
        "bpm" => $bpm,
        "status" => $status,
        "logged_at" => $hrRow["logged_at"]
    ];
    $hasAnyLog = true;
    $scoreComponents["hr"] = ($bpm >= 60 && $bpm <= 100) ? 100 : (($bpm >= 50 && $bpm <= 110) ? 80 : 60);
}

// 2. Fetch latest SpO2
$spo2Data = null;
$spo2Row = null;
$stmtSpo2 = $conn->prepare("SELECT spo2, logged_at FROM vitals_logs WHERE user_id = ? AND spo2 IS NOT NULL AND spo2 > 0 ORDER BY logged_at DESC LIMIT 1");
if ($stmtSpo2) {
    $stmtSpo2->bind_param("i", $userId);
    $stmtSpo2->execute();
    $spo2Row = $stmtSpo2->get_result()->fetch_assoc();
    $stmtSpo2->close();
}

if (!$spo2Row) {
    $spo2Row2 = null;
    $stmtSpo2_2 = $conn->prepare("SELECT value_numeric, status_label, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' ORDER BY recorded_at DESC LIMIT 1");
    if ($stmtSpo2_2) {
        $stmtSpo2_2->bind_param("i", $userId);
        $stmtSpo2_2->execute();
        $spo2Row2 = $stmtSpo2_2->get_result()->fetch_assoc();
        $stmtSpo2_2->close();
    }
    if ($spo2Row2) {
        $pct = (int)$spo2Row2["value_numeric"];
        $status = $spo2Row2["status_label"] ?: ($pct >= 95 ? "Optimal" : "Low");
        $spo2Data = [
            "percentage" => $pct,
            "status" => $status,
            "logged_at" => $spo2Row2["recorded_at"]
        ];
        $hasAnyLog = true;
        $scoreComponents["spo2"] = $pct >= 95 ? 100 : ($pct >= 90 ? 75 : 50);
    }
} else {
    $pct = (int)$spo2Row["spo2"];
    $status = $pct >= 95 ? "Optimal" : "Low";
    $spo2Data = [
        "percentage" => $pct,
        "status" => $status,
        "logged_at" => $spo2Row["logged_at"]
    ];
    $hasAnyLog = true;
    $scoreComponents["spo2"] = $pct >= 95 ? 100 : ($pct >= 90 ? 75 : 50);
}

// 3. Fetch latest Blood Pressure
$bpData = null;
$bpRow = null;
$stmtBp = $conn->prepare("SELECT systolic_bp, diastolic_bp, logged_at FROM vitals_logs WHERE user_id = ? AND systolic_bp IS NOT NULL AND systolic_bp > 0 ORDER BY logged_at DESC LIMIT 1");
if ($stmtBp) {
    $stmtBp->bind_param("i", $userId);
    $stmtBp->execute();
    $bpRow = $stmtBp->get_result()->fetch_assoc();
    $stmtBp->close();
}

if (!$bpRow) {
    $bpRow2 = null;
    $stmtBp2 = $conn->prepare("SELECT value_numeric, value_secondary, value_text, status_label, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' ORDER BY recorded_at DESC LIMIT 1");
    if ($stmtBp2) {
        $stmtBp2->bind_param("i", $userId);
        $stmtBp2->execute();
        $bpRow2 = $stmtBp2->get_result()->fetch_assoc();
        $stmtBp2->close();
    }
    if ($bpRow2) {
        $sys = (int)$bpRow2["value_numeric"];
        $dia = (int)$bpRow2["value_secondary"];
        $bpEval = evaluateBloodPressure($sys, $dia);
        $status = $bpEval["status_title"];
        $bpData = [
            "systolic" => $sys,
            "diastolic" => $dia,
            "formatted" => "{$sys}/{$dia} mmHg",
            "status" => $status,
            "logged_at" => $bpRow2["recorded_at"]
        ];
        $hasAnyLog = true;
        $scoreComponents["bp"] = ($bpEval["tier"] === "Optimal") ? 100 : (($bpEval["tier"] === "Alert") ? 25 : (($bpEval["urgency"] === "NEEDS_IMPROVEMENT") ? 55 : 80));
    }
} else {
    $sys = (int)$bpRow["systolic_bp"];
    $dia = (int)$bpRow["diastolic_bp"];
    $bpEval = evaluateBloodPressure($sys, $dia);
    $status = $bpEval["status_title"];
    $bpData = [
        "systolic" => $sys,
        "diastolic" => $dia,
        "formatted" => "{$sys}/{$dia} mmHg",
        "status" => $status,
        "logged_at" => $bpRow["logged_at"]
    ];
    $hasAnyLog = true;
    $scoreComponents["bp"] = ($bpEval["tier"] === "Optimal") ? 100 : (($bpEval["tier"] === "Alert") ? 25 : (($bpEval["urgency"] === "NEEDS_IMPROVEMENT") ? 55 : 80));
}

// 4. Fetch today's Steps Summary
$stepsData = null;
$stepRow = null;
$stmtSteps = $conn->prepare("SELECT step_count, target_steps, distance_km, calories_kcal, active_minutes, logged_date FROM steps_logs WHERE user_id = ? AND logged_date = ? LIMIT 1");
if ($stmtSteps) {
    $stmtSteps->bind_param("is", $userId, $date);
    $stmtSteps->execute();
    $stepRow = $stmtSteps->get_result()->fetch_assoc();
    $stmtSteps->close();
}

if ($stepRow && (int)$stepRow["step_count"] > 0) {
    $sc = (int)$stepRow["step_count"];
    $ts = (int)$stepRow["target_steps"];
    $prog = $ts > 0 ? min(100, (int)round(($sc / $ts) * 100)) : 0;
    $stepsData = [
        "step_count" => $sc,
        "target_steps" => $ts,
        "distance_km" => (float)$stepRow["distance_km"],
        "calories_kcal" => (int)$stepRow["calories_kcal"],
        "active_minutes" => (int)$stepRow["active_minutes"],
        "progress_percent" => $prog
    ];
    $hasAnyLog = true;
    $scoreComponents["steps"] = min(100, (int)round(($sc / max(1, $ts)) * 100));
}

// 5. Fetch today's Sleep Summary
$sleepData = null;
$sleepRow = null;
$stmtSleep = $conn->prepare("SELECT bedtime, wake_time, duration_minutes, status_color FROM sleep_logs WHERE user_id = ? AND logged_date = ? LIMIT 1");
if ($stmtSleep) {
    $stmtSleep->bind_param("is", $userId, $date);
    $stmtSleep->execute();
    $sleepRow = $stmtSleep->get_result()->fetch_assoc();
    $stmtSleep->close();
}

if ($sleepRow && (int)$sleepRow["duration_minutes"] > 0) {
    $dur = (int)$sleepRow["duration_minutes"];
    $h = intdiv($dur, 60);
    $m = $dur % 60;
    $sleepData = [
        "bedtime" => $sleepRow["bedtime"],
        "wake_time" => $sleepRow["wake_time"],
        "duration_minutes" => $dur,
        "duration_formatted" => "{$h}h {$m}m",
        "status_color" => $sleepRow["status_color"]
    ];
    $hasAnyLog = true;
    $scoreComponents["sleep"] = $dur >= 480 ? 100 : ($dur >= 360 ? 80 : 50);
}

// 6. Fetch today's Hydration Summary
$hydrationData = null;
$hydRow = null;
$stmtHyd = $conn->prepare("SELECT COALESCE(intake_ml, amount_ml) as intake, target_ml FROM hydration_logs WHERE user_id = ? AND (logged_date = ? OR date_string = ?) ORDER BY id DESC LIMIT 1");
if ($stmtHyd) {
    $stmtHyd->bind_param("iss", $userId, $date, $date);
    $stmtHyd->execute();
    $hydRow = $stmtHyd->get_result()->fetch_assoc();
    $stmtHyd->close();
}

if ($hydRow && (int)$hydRow["intake"] > 0) {
    $intake = (int)$hydRow["intake"];
    $tgt = !empty($hydRow["target_ml"]) ? (int)$hydRow["target_ml"] : 2000;
    $prog = $tgt > 0 ? min(100, (int)round(($intake / $tgt) * 100)) : 0;
    $hydrationData = [
        "intake_ml" => $intake,
        "target_ml" => $tgt,
        "progress_percent" => $prog
    ];
    $hasAnyLog = true;
    $scoreComponents["hydration"] = min(100, (int)round(($intake / max(1, $tgt)) * 100));
}

// Prompt requirement: wellness_score (calculated composite score 0–100, or null if no logs exist).
$wellnessScore = null;
$wellnessStatus = "No data recorded";

if ($hasAnyLog && count($scoreComponents) > 0) {
    $avgScore = (int)round(array_sum($scoreComponents) / count($scoreComponents));
    $wellnessScore = max(0, min(100, $avgScore));
    $wellnessStatus = $wellnessScore >= 80 ? "Optimal State" : ($wellnessScore >= 60 ? "Good" : "Needs Attention");
}

respondJson(200, [
    "status" => "success",
    "message" => "Dashboard summary retrieved successfully",
    "data" => [
        "user_id" => $userId,
        "date" => $date,
        "wellness_score" => $wellnessScore,
        "wellness_status" => $wellnessStatus,
        "vitals" => [
            "heart_rate" => $hrData,
            "spo2" => $spo2Data,
            "blood_pressure" => $bpData
        ],
        "steps_summary" => $stepsData,
        "sleep" => $sleepData,
        "hydration" => $hydrationData
    ]
]);

