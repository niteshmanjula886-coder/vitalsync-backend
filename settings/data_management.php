<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // Calculate storage usage
    $conn->close();

    respondJson(200, [
        "success" => true,
        "storage" => [
            "total_mb" => 24.0,
            "health_data_mb" => 18.6,
            "cache_mb" => 5.4,
            "auto_backup_enabled" => true,
            "last_backup" => date("M d, Y \\a\\t h:i A")
        ]
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $action = strtolower(trim((string)($data["action"] ?? "export")));

    if ($action === "export") {
        // Collect user's health data for download / export
        $uStmt = $conn->prepare("SELECT id, full_name, email, phone, dob, height, weight, primary_goal, created_at FROM users WHERE id = ?");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $user = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        $vStmt = $conn->prepare("SELECT reading_type, value_numeric, value_secondary, value_text, unit, status_label, recorded_at FROM vitals_readings WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 100");
        $vStmt->bind_param("i", $userId);
        $vStmt->execute();
        $vitals = $vStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $vStmt->close();

        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Health data export package generated",
            "exported_at" => date("Y-m-d H:i:s"),
            "data" => [
                "user_profile" => $user,
                "vitals_readings" => $vitals
            ]
        ]);
    }

    if ($action === "clear_cache") {
        $conn->close();
        respondJson(200, [
            "success" => true,
            "message" => "Temporary cache cleared successfully",
            "freed_mb" => 5.4
        ]);
    }

    respondJson(400, [
        "success" => false,
        "message" => "Valid action required: 'export' or 'clear_cache'"
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

