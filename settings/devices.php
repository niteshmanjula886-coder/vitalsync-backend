<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, device_name, device_type, is_connected, battery_level, last_synced FROM connected_devices WHERE user_id = ? ORDER BY is_connected DESC, id ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $devices = [];
    while ($r = $result->fetch_assoc()) {
        $devices[] = [
            "id" => (int)$r["id"],
            "device_name" => $r["device_name"],
            "device_type" => $r["device_type"],
            "is_connected" => (bool)$r["is_connected"],
            "battery_level" => (int)$r["battery_level"],
            "last_synced" => $r["last_synced"],
            "last_synced_formatted" => date("M d, h:i A", strtotime($r["last_synced"]))
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($devices),
        "devices" => $devices
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $name = trim($data["device_name"] ?? "");
    $type = trim($data["device_type"] ?? "Smart Watch");
    $battery = isset($data["battery_level"]) ? (int)$data["battery_level"] : 100;
    $connected = isset($data["is_connected"]) ? ($data["is_connected"] ? 1 : 0) : 1;

    if ($name === "") {
        respondJson(400, [
            "success" => false,
            "message" => "device_name is required"
        ]);
    }

    $stmt = $conn->prepare("INSERT INTO connected_devices (user_id, device_name, device_type, is_connected, battery_level) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $userId, $name, $type, $connected, $battery);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Device paired successfully",
            "device" => [
                "id" => $newId,
                "device_name" => $name,
                "device_type" => $type,
                "is_connected" => (bool)$connected,
                "battery_level" => $battery
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to pair device: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

