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
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function resolveUser($conn, $input) {
    if (isset($input["user_id"]) && is_numeric($input["user_id"]) && (int)$input["user_id"] > 0) {
        return (int)$input["user_id"];
    }
    if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) && (int)$_GET["user_id"] > 0) {
        return (int)$_GET["user_id"];
    }
    $email = strtolower(trim($input["email"] ?? $_GET["email"] ?? ""));
    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row["id"];
        }
    }
    return 1; // Default fallback to user 1
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $userId = resolveUser($conn, $_GET);

    $stmt = $conn->prepare("SELECT id, user_id, subject, message, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = [
            "id" => (int)$row["id"],
            "subject" => $row["subject"],
            "message" => $row["message"],
            "status" => $row["status"],
            "created_at" => $row["created_at"]
        ];
    }
    $stmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "email_support" => "support@vitalsync.com",
        "hours" => "Mon-Fri, 9 AM - 6 PM",
        "tickets" => $tickets
    ]);
}

if ($method === "POST") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = resolveUser($conn, $data);
    $subject = trim((string)($data["subject"] ?? "General In-App Inquiry"));
    $message = trim((string)($data["message"] ?? ""));

    if ($message === "") {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "Message content is required"
        ]);
    }

    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
    $stmt->bind_param("iss", $userId, $subject, $message);
    $stmt->execute();
    $ticketId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    respond(201, [
        "success" => true,
        "status" => "success",
        "message" => "Support inquiry submitted successfully. Typical response under 24 hours.",
        "ticket_id" => $ticketId
    ]);
}

respond(405, [
    "success" => false,
    "status" => "error",
    "message" => "Method not allowed"
]);

