<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function resolveUserId($conn, $userId, $email) {
    if ($userId > 0) return $userId;
    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return (int)$row["id"];
        }
    }
    return 0;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $email = strtolower(trim($_GET["email"] ?? ""));
    $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);

    $uid = resolveUserId($conn, $userId, $email);
    if ($uid <= 0) {
        respond(400, [
            "success" => false,
            "message" => "Valid user_id or email required"
        ]);
    }

    $stmt = $conn->prepare("SELECT * FROM login_sessions WHERE user_id = ? ORDER BY is_current DESC, last_active DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = [
            "id" => (int)$row["id"],
            "device_name" => $row["device_name"],
            "device_os" => $row["device_os"],
            "ip_address" => $row["ip_address"],
            "location" => $row["location"] ?? "Current Location",
            "is_current" => (bool)$row["is_current"],
            "last_active" => $row["last_active"]
        ];
    }
    $stmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "count" => count($sessions),
        "sessions" => $sessions
    ]);
}

if ($method === "POST") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $email = strtolower(trim($data["email"] ?? ""));
    $userId = isset($data["user_id"]) ? (int)$data["user_id"] : 0;
    $action = strtolower(trim($data["action"] ?? ""));

    $uid = resolveUserId($conn, $userId, $email);
    if ($uid <= 0) {
        respond(400, [
            "success" => false,
            "message" => "Valid user_id or email required"
        ]);
    }

    if ($action === "logout_others") {
        $stmt = $conn->prepare("DELETE FROM login_sessions WHERE user_id = ? AND is_current = 0");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        respond(200, [
            "success" => true,
            "message" => "Logged out of all other sessions successfully",
            "terminated_sessions" => $deleted
        ]);
    }

    respond(400, [
        "success" => false,
        "message" => "Valid action required (e.g. action: 'logout_others')"
    ]);
}

respond(405, [
    "success" => false,
    "message" => "Method not allowed"
]);

