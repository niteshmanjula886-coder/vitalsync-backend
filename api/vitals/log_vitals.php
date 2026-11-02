<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "POST") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use POST.",
        "data" => null
    ]);
}

$input = getInputData();

$heartRate = isset($input["heart_rate"]) && is_numeric($input["heart_rate"]) ? (int)$input["heart_rate"] : null;
$spo2 = isset($input["spo2"]) && is_numeric($input["spo2"]) ? (int)$input["spo2"] : null;
$systolicBp = isset($input["systolic_bp"]) && is_numeric($input["systolic_bp"]) ? (int)$input["systolic_bp"] : (isset($input["systolic"]) ? (int)$input["systolic"] : null);
$diastolicBp = isset($input["diastolic_bp"]) && is_numeric($input["diastolic_bp"]) ? (int)$input["diastolic_bp"] : (isset($input["diastolic"]) ? (int)$input["diastolic"] : null);
$loggedAt = !empty($input["logged_at"]) ? trim((string)$input["logged_at"]) : date("Y-m-d H:i:s");

if ($heartRate === null && $spo2 === null && $systolicBp === null) {
    respondJson(400, [
        "status" => "error",
        "message" => "At least one vital reading (heart_rate, spo2, or systolic_bp) must be provided",
        "data" => null
    ]);
}

// 1. Insert into vitals_logs
$stmt = $conn->prepare("INSERT INTO vitals_logs (user_id, heart_rate, spo2, systolic_bp, diastolic_bp, logged_at) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiis", $userId, $heartRate, $spo2, $systolicBp, $diastolicBp, $loggedAt);
$stmt->execute();
$logId = $stmt->insert_id;
$stmt->close();

// 2. Also sync to vitals_readings for backwards compatibility
if ($heartRate !== null && $heartRate > 0) {
    $hrStatus = ($heartRate >= 60 && $heartRate <= 100) ? "Normal" : ($heartRate < 60 ? "Low Rate" : "Elevated");
    $vr1 = $conn->prepare("INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at) VALUES (?, 'heart_rate', ?, ?, 'bpm', ?, 'Bluetooth/Sensor', ?)");
    if ($vr1) {
        $num = (float)$heartRate;
        $txt = "$heartRate BPM";
        $vr1->bind_param("idsss", $userId, $num, $txt, $hrStatus, $loggedAt);
        $vr1->execute();
        $vr1->close();
    }
}

if ($spo2 !== null && $spo2 > 0) {
    $spo2Status = ($spo2 >= 95) ? "Optimal" : "Low";
    $vr2 = $conn->prepare("INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at) VALUES (?, 'spo2', ?, ?, '%', ?, 'Bluetooth/Sensor', ?)");
    if ($vr2) {
        $num = (float)$spo2;
        $txt = "$spo2%";
        $vr2->bind_param("idsss", $userId, $num, $txt, $spo2Status, $loggedAt);
        $vr2->execute();
        $vr2->close();
    }
}

if ($systolicBp !== null && $diastolicBp !== null) {
    $bpStatus = ($systolicBp <= 120 && $diastolicBp <= 80) ? "Optimal" : (($systolicBp <= 129 && $diastolicBp <= 80) ? "Elevated" : "Hypertension");
    $vr3 = $conn->prepare("INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, recorded_at) VALUES (?, 'blood_pressure', ?, ?, ?, 'mmHg', ?, 'Bluetooth/Sensor', ?)");
    if ($vr3) {
        $sysNum = (float)$systolicBp;
        $diaNum = (float)$diastolicBp;
        $txt = "$systolicBp/$diastolicBp mmHg";
        $vr3->bind_param("iddsss", $userId, $sysNum, $diaNum, $txt, $bpStatus, $loggedAt);
        $vr3->execute();
        $vr3->close();
    }
}

respondJson(201, [
    "status" => "success",
    "message" => "Vitals logged successfully",
    "data" => [
        "id" => $logId,
        "user_id" => $userId,
        "heart_rate" => $heartRate,
        "spo2" => $spo2,
        "systolic_bp" => $systolicBp,
        "diastolic_bp" => $diastolicBp,
        "logged_at" => $loggedAt
    ]
]);
