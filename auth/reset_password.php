<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, [
        "success" => false,
        "message" => "POST method required"
    ]);
}

// Read JSON or form input
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!is_array($data) || empty($data)) {
    respond(400, [
        "success" => false,
        "message" => "Invalid or empty request data"
    ]);
}

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["new_password"] ?? $data["password"] ?? "";
$confirm = $data["confirm_password"] ?? "";

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        "success" => false,
        "message" => "Enter a valid email address"
    ]);
}

// Validate password (UI checks min 6 characters)
if (!is_string($password) || strlen($password) < 6 || strlen($password) > 72) {
    respond(400, [
        "success" => false,
        "message" => "Password must be between 6 and 72 characters"
    ]);
}

// Confirm passwords match
if (!is_string($confirm) || $password !== $confirm) {
    respond(400, [
        "success" => false,
        "message" => "Passwords do not match"
    ]);
}

// Find user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    respond(500, [
        "success" => false,
        "message" => "Database query failed: " . $conn->error
    ]);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $conn->close();
    respond(404, [
        "success" => false,
        "message" => "No account found with this email"
    ]);
}

// Hash new password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$userId = (int)$user["id"];

$updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
if (!$updateStmt) {
    $conn->close();
    respond(500, [
        "success" => false,
        "message" => "Unable to prepare password update"
    ]);
}

$updateStmt->bind_param("si", $passwordHash, $userId);

if ($updateStmt->execute()) {
    $updateStmt->close();

    // Clean up any pending resets in password_resets table
    $cleanStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    if ($cleanStmt) {
        $cleanStmt->bind_param("i", $userId);
        $cleanStmt->execute();
        $cleanStmt->close();
    }

    $conn->close();

    respond(200, [
        "success" => true,
        "message" => "Password reset successfully. Please log in with your new password."
    ]);
}

$updateStmt->close();
$conn->close();

respond(500, [
    "success" => false,
    "message" => "Unable to update password"
]);
