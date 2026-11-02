<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // 1. Fetch history from vitals_logs
    $stmt = $conn->prepare(
        "SELECT id, systolic_bp AS systolic, diastolic_bp AS diastolic, source, logged_at AS recorded_at 
         FROM vitals_logs 
         WHERE user_id = ? AND systolic_bp > 0 
         ORDER BY logged_at DESC LIMIT 15"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($r = $result->fetch_assoc()) {
        $sys = (int)$r["systolic"];
        $dia = (int)$r["diastolic"];
        $status = ($sys <= 120 && $dia <= 80) ? "Optimal" : (($sys <= 129 && $dia <= 80) ? "Elevated" : "Hypertension");
        $history[] = [
            "id" => (int)$r["id"],
            "systolic" => $sys,
            "diastolic" => $dia,
            "formatted" => "$sys/$dia mmHg",
            "status" => $status,
            "source" => $r["source"] ?: "Bluetooth Wearable",
            "recorded_at" => $r["recorded_at"]
        ];
    }
    $stmt->close();

    // Fallback to vitals_readings if empty
    if (empty($history)) {
        $stmt2 = $conn->prepare(
            "SELECT id, value_numeric AS systolic, value_secondary AS diastolic, value_text, status_label, source, recorded_at 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = 'blood_pressure' 
             ORDER BY recorded_at DESC LIMIT 10"
        );
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r2 = $res2->fetch_assoc()) {
            $history[] = [
                "id" => (int)$r2["id"],
                "systolic" => (int)$r2["systolic"],
                "diastolic" => (int)$r2["diastolic"],
                "formatted" => $r2["value_text"] ?: ($r2["systolic"] . "/" . $r2["diastolic"] . " mmHg"),
                "status" => $r2["status_label"],
                "source" => $r2["source"],
                "recorded_at" => $r2["recorded_at"]
            ];
        }
        $stmt2->close();
    }

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

    if ($systolic <= 120 && $diastolic <= 80) {
        $status = "Normal";
    } elseif ($systolic <= 129 && $diastolic <= 80) {
        $status = "Elevated";
    } else {
        $status = "Hypertension";
    }

    $text = "$systolic/$diastolic mmHg";
    $unit = "mmHg";
    $source = trim((string)($data["source"] ?? "Manual Entry"));
    $loggedAt = !empty($data["logged_at"]) ? trim((string)$data["logged_at"]) : date("Y-m-d H:i:s");

    // 1. Insert into vitals_logs
    $stmt1 = $conn->prepare("INSERT INTO vitals_logs (user_id, systolic_bp, diastolic_bp, source, logged_at) VALUES (?, ?, ?, ?, ?)");
    $stmt1->bind_param("iiiss", $userId, $systolic, $diastolic, $source, $loggedAt);
    $stmt1->execute();
    $newId = $stmt1->insert_id;
    $stmt1->close();

    // 2. Insert into vitals_readings
    $stmt2 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, recorded_at)
         VALUES (?, 'blood_pressure', ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("iddsssss", $userId, $systolic, $diastolic, $text, $unit, $status, $source, $loggedAt);
    $stmt2->execute();
    $stmt2->close();

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
