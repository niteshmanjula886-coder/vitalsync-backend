<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, value_numeric, status_label, source, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'spo2' 
         ORDER BY recorded_at DESC LIMIT 10"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($r = $result->fetch_assoc()) {
        $history[] = [
            "id" => (int)$r["id"],
            "percentage" => (int)$r["value_numeric"],
            "formatted" => ((int)$r["value_numeric"]) . "%",
            "status" => $r["status_label"],
            "source" => $r["source"],
            "recorded_at" => $r["recorded_at"]
        ];
    }
    $stmt->close();
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

    $stmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source)
         VALUES (?, 'spo2', ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("idssss", $userId, $percentage, $text, $unit, $status, $source);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
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

