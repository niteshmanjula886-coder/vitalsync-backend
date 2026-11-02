<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // 1. Fetch from vitals_logs
    $stmt = $conn->prepare(
        "SELECT id, spo2 as percentage, source, logged_at as recorded_at 
         FROM vitals_logs 
         WHERE user_id = ? AND spo2 > 0 
         ORDER BY logged_at DESC LIMIT 15"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($r = $result->fetch_assoc()) {
        $pct = (int)$r["percentage"];
        $status = ($pct >= 95) ? "Optimal" : (($pct >= 90) ? "Moderate" : "Low");
        $history[] = [
            "id" => (int)$r["id"],
            "percentage" => $pct,
            "formatted" => "$pct%",
            "status" => $status,
            "source" => $r["source"] ?: "Bluetooth Wearable",
            "recorded_at" => $r["recorded_at"]
        ];
    }
    $stmt->close();

    // Fallback to vitals_readings if empty
    if (empty($history)) {
        $stmt2 = $conn->prepare(
            "SELECT id, value_numeric, status_label, source, recorded_at 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = 'spo2' 
             ORDER BY recorded_at DESC LIMIT 10"
        );
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r2 = $res2->fetch_assoc()) {
            $history[] = [
                "id" => (int)$r2["id"],
                "percentage" => (int)$r2["value_numeric"],
                "formatted" => ((int)$r2["value_numeric"]) . "%",
                "status" => $r2["status_label"],
                "source" => $r2["source"],
                "recorded_at" => $r2["recorded_at"]
            ];
        }
        $stmt2->close();
    }

    $conn->close();

    $latest = !empty($history) ? $history[0] : [
        "id" => 0,
        "percentage" => 0,
        "formatted" => "--%",
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
    $percentage = isset($data["percentage"]) ? (int)$data["percentage"] : (isset($data["value_numeric"]) ? (int)$data["value_numeric"] : 0);

    if ($percentage < 50 || $percentage > 100) {
        respondJson(400, [
            "success" => false,
            "message" => "Please enter a valid oxygen saturation percentage between 50 and 100"
        ]);
    }

    $status = ($percentage >= 95) ? "Optimal" : (($percentage >= 90) ? "Moderate" : "Low");
    $text = "$percentage%";
    $unit = "%";
    $source = trim((string)($data["source"] ?? "VitalSync Watch"));
    $loggedAt = !empty($data["logged_at"]) ? trim((string)$data["logged_at"]) : date("Y-m-d H:i:s");

    // 1. Insert into vitals_logs
    $stmt1 = $conn->prepare("INSERT INTO vitals_logs (user_id, spo2, source, logged_at) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("iiss", $userId, $percentage, $source, $loggedAt);
    $stmt1->execute();
    $newId = $stmt1->insert_id;
    $stmt1->close();

    // 2. Insert into vitals_readings
    $stmt2 = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source, recorded_at)
         VALUES (?, 'spo2', ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("idsssss", $userId, $percentage, $text, $unit, $status, $source, $loggedAt);
    $stmt2->execute();
    $stmt2->close();

    $conn->close();

    respondJson(201, [
        "success" => true,
        "message" => "SpO2 reading recorded successfully",
        "id" => $newId,
        "percentage" => $percentage,
        "status" => $status
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
