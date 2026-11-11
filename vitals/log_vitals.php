<?php

ini_set("display_errors", "0");
date_default_timezone_set("Asia/Kolkata");
require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method !== "POST") {
    respondJson(405, [
        "success" => false,
        "status" => "error",
        "message" => "Method not allowed. Use POST.",
        "data" => null
    ]);
}

$input = getInputData();

$rawHr = $input["heart_rate"] ?? $input["heartRate"] ?? null;
$rawSpo2 = $input["spo2"] ?? null;
$rawSys = $input["systolic"] ?? $input["systolic_bp"] ?? $input["systolicBp"] ?? null;
$rawDia = $input["diastolic"] ?? $input["diastolic_bp"] ?? $input["diastolicBp"] ?? null;

$heartRate = ($rawHr !== null && $rawHr !== "" && is_numeric($rawHr)) ? (int)$rawHr : null;
$spo2 = ($rawSpo2 !== null && $rawSpo2 !== "" && is_numeric($rawSpo2)) ? (int)$rawSpo2 : null;
$systolic = ($rawSys !== null && $rawSys !== "" && is_numeric($rawSys)) ? (int)$rawSys : null;
$diastolic = ($rawDia !== null && $rawDia !== "" && is_numeric($rawDia)) ? (int)$rawDia : null;

$notes = isset($input["notes"]) ? trim((string)$input["notes"]) : null;
if ($notes === "") {
    $notes = null;
}
$loggedAt = !empty($input["logged_at"]) ? trim((string)$input["logged_at"]) : date("Y-m-d H:i:s");

if ($heartRate === null && $spo2 === null && $systolic === null && $diastolic === null) {
    respondJson(400, [
        "success" => false,
        "status" => "error",
        "message" => "Please enter at least one vital metric (Heart Rate, SpO2, or Blood Pressure).",
        "data" => null
    ]);
}

if ($heartRate !== null && ($heartRate < 30 || $heartRate > 220)) {
    respondJson(400, [
        "success" => false,
        "status" => "error",
        "message" => "Heart Rate must be between 30 and 220 BPM.",
        "data" => null
    ]);
}

if ($spo2 !== null && ($spo2 < 70 || $spo2 > 100)) {
    respondJson(400, [
        "success" => false,
        "status" => "error",
        "message" => "SpO2 must be between 70 and 100%.",
        "data" => null
    ]);
}

if (($systolic !== null && $diastolic === null) || ($systolic === null && $diastolic !== null)) {
    respondJson(400, [
        "success" => false,
        "status" => "error",
        "message" => "Both Systolic and Diastolic Blood Pressure values are required together.",
        "data" => null
    ]);
}

if ($systolic !== null && $diastolic !== null) {
    if ($systolic < 70 || $systolic > 220) {
        respondJson(400, [
            "success" => false,
            "status" => "error",
            "message" => "Systolic BP must be between 70 and 220 mmHg.",
            "data" => null
        ]);
    }
    if ($diastolic < 40 || $diastolic > 140) {
        respondJson(400, [
            "success" => false,
            "status" => "error",
            "message" => "Diastolic BP must be between 40 and 140 mmHg.",
            "data" => null
        ]);
    }
    if ($systolic <= $diastolic) {
        respondJson(400, [
            "success" => false,
            "status" => "error",
            "message" => "Systolic BP must be greater than Diastolic BP.",
            "data" => null
        ]);
    }
}

$source = "Manual Entry";

// 1. Insert combined manual record into vitals_logs
$stmt = $conn->prepare(
    "INSERT INTO vitals_logs (user_id, heart_rate, spo2, systolic_bp, diastolic_bp, source, logged_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "status" => "error",
        "message" => "Failed to prepare vitals log insert: " . $conn->error,
        "data" => null
    ]);
}
$stmt->bind_param("iiiiiss", $userId, $heartRate, $spo2, $systolic, $diastolic, $source, $loggedAt);
$stmt->execute();
$logId = (int)$stmt->insert_id;
$stmt->close();

// 2. Record typed entries in vitals_readings for historical logs & trend analytics
$hrStatus = null;
if ($heartRate !== null) {
    if ($heartRate < 50) {
        $hrStatus = "Severe Bradycardia";
    } elseif ($heartRate < 60) {
        $hrStatus = "Athletic / Low Resting";
    } elseif ($heartRate <= 100) {
        $hrStatus = "Optimal Resting";
    } elseif ($heartRate <= 129) {
        $hrStatus = "Elevated";
    } else {
        $hrStatus = "Tachycardia / High";
    }
    $vr1 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, notes, recorded_at)
         VALUES (?, 'heart_rate', ?, ?, 'bpm', ?, ?, ?, ?)"
    );
    if ($vr1) {
        $num = (float)$heartRate;
        $txt = "{$heartRate} BPM";
        $vr1->bind_param("idsssss", $userId, $num, $txt, $hrStatus, $source, $notes, $loggedAt);
        $vr1->execute();
        $vr1->close();
    }
}

$spo2Status = null;
if ($spo2 !== null) {
    $spo2Status = ($spo2 >= 95) ? "Optimal Oxygenation" : (($spo2 >= 90) ? "Mild Hypoxia" : "Low Oxygen / Consult Doctor");
    $vr2 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, notes, recorded_at)
         VALUES (?, 'spo2', ?, ?, '%', ?, ?, ?, ?)"
    );
    if ($vr2) {
        $num = (float)$spo2;
        $txt = "{$spo2}%";
        $vr2->bind_param("idsssss", $userId, $num, $txt, $spo2Status, $source, $notes, $loggedAt);
        $vr2->execute();
        $vr2->close();
    }
}

$bpStatus = null;
if ($systolic !== null && $diastolic !== null) {
    $bpEval = evaluateBloodPressure($systolic, $diastolic);
    $bpStatus = $bpEval["status_title"];

    $vr3 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, notes, recorded_at)
         VALUES (?, 'blood_pressure', ?, ?, ?, 'mmHg', ?, ?, ?, ?)"
    );
    if ($vr3) {
        $sysNum = (float)$systolic;
        $diaNum = (float)$diastolic;
        $txt = "{$systolic}/{$diastolic} mmHg";
        $vr3->bind_param("iddsssss", $userId, $sysNum, $diaNum, $txt, $bpStatus, $source, $notes, $loggedAt);
        $vr3->execute();
        $vr3->close();
    }
}

$conn->close();

respondJson(201, [
    "success" => true,
    "status" => "success",
    "message" => "Vitals logged successfully",
    "data" => [
        "id" => $logId,
        "user_id" => $userId,
        "heart_rate" => $heartRate,
        "heart_rate_status" => $hrStatus,
        "spo2" => $spo2,
        "spo2_status" => $spo2Status,
        "systolic" => $systolic,
        "diastolic" => $diastolic,
        "bp_status" => $bpStatus,
        "source" => $source,
        "notes" => $notes,
        "logged_at" => $loggedAt
    ]
]);
