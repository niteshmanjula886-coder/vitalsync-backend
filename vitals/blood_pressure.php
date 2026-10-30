<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, value_numeric AS systolic, value_secondary AS diastolic, value_text, status_label, source, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'blood_pressure' 
         ORDER BY recorded_at DESC LIMIT 10"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($r = $result->fetch_assoc()) {
        $history[] = [
            "id" => (int)$r["id"],
            "systolic" => (int)$r["systolic"],
            "diastolic" => (int)$r["diastolic"],
            "formatted" => $r["value_text"] ?: ($r["systolic"] . "/" . $r["diastolic"] . " mmHg"),
            "status" => $r["status_label"],
            "source" => $r["source"],
            "recorded_at" => $r["recorded_at"]
        ];
    }
    $stmt->close();
    $conn->close();

    $latest = !empty($history) ? $history[0] : [
        "systolic" => 0,
        "diastolic" => 0,
        "pulse" => 0,
        "formatted" => "--/-- mmHg",
        "status" => "No readings yet",
        "source" => "",
        "recorded_at" => ""
    ];

    respondJson(200, [
        "success" => true,
        "latest" => $latest,
        "history" => $history
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $systolic = isset($data["systolic"]) ? (int)$data["systolic"] : 0;
    $diastolic = isset($data["diastolic"]) ? (int)$data["diastolic"] : 0;

    if ($systolic < 70 || $systolic > 240 || $diastolic < 40 || $diastolic > 160) {
        respondJson(400, [
            "success" => false,
            "message" => "Please provide valid systolic (70-240) and diastolic (40-160) blood pressure values"
        ]);
    }

    if ($systolic < 120 && $diastolic < 80) {
        $status = "Normal";
    } elseif ($systolic <= 129 && $diastolic < 80) {
        $status = "Elevated";
    } else {
        $status = "Hypertension";
    }

    $text = "$systolic/$diastolic mmHg";
    $unit = "mmHg";
    $source = trim((string)($data["source"] ?? "Manual Entry"));

    $stmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source)
         VALUES (?, 'blood_pressure', ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iddssss", $userId, $systolic, $diastolic, $text, $unit, $status, $source);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    respondJson(201, [
        "success" => true,
        "message" => "Blood pressure recorded successfully",
        "id" => $newId,
        "systolic" => $systolic,
        "diastolic" => $diastolic,
        "status" => $status
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

