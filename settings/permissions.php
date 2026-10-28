<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT vital_signs, activity, sleep, hydration, write_health_data FROM health_permissions WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    $perms = [
        "vital_signs" => $row ? (bool)$row["vital_signs"] : true,
        "activity" => $row ? (bool)$row["activity"] : true,
        "sleep" => $row ? (bool)$row["sleep"] : true,
        "hydration" => $row ? (bool)$row["hydration"] : false,
        "write_health_data" => $row ? (bool)$row["write_health_data"] : true
    ];

    respondJson(200, [
        "success" => true,
        "permissions" => $perms
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $vitals = isset($data["vital_signs"]) ? ($data["vital_signs"] ? 1 : 0) : 1;
    $act = isset($data["activity"]) ? ($data["activity"] ? 1 : 0) : 1;
    $sleep = isset($data["sleep"]) ? ($data["sleep"] ? 1 : 0) : 1;
    $hyd = isset($data["hydration"]) ? ($data["hydration"] ? 1 : 0) : 0;
    $write = isset($data["write_health_data"]) ? ($data["write_health_data"] ? 1 : 0) : 1;

    $stmt = $conn->prepare(
        "INSERT INTO health_permissions (user_id, vital_signs, activity, sleep, hydration, write_health_data)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            vital_signs = VALUES(vital_signs),
            activity = VALUES(activity),
            sleep = VALUES(sleep),
            hydration = VALUES(hydration),
            write_health_data = VALUES(write_health_data)"
    );
    $stmt->bind_param("iiiiii", $userId, $vitals, $act, $sleep, $hyd, $write);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Health permissions updated successfully",
            "permissions" => [
                "vital_signs" => (bool)$vitals,
                "activity" => (bool)$act,
                "sleep" => (bool)$sleep,
                "hydration" => (bool)$hyd,
                "write_health_data" => (bool)$write
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to update permissions: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

