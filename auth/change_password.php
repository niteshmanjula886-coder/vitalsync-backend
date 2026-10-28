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

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, [
        "success" => false,
        "message" => "POST method required"
    ]);
}

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

$userId = isset($data["user_id"]) ? (int)$data["user_id"] : (isset($data["id"]) ? (int)$data["id"] : 0);
$email = strtolower(trim($data["email"] ?? ""));
$currentPassword = $data["current_password"] ?? "";
$newPassword = $data["new_password"] ?? "";
$confirmPassword = $data["confirm_password"] ?? "";

if ($userId <= 0 && $email === "") {
    respond(400, [
        "success" => false,
        "message" => "user_id or email is required to identify the user"
    ]);
}

if (!is_string($currentPassword) || $currentPassword === "") {
    respond(400, [
        "success" => false,
        "message" => "Current password is required"
    ]);
}

if (!is_string($newPassword) || strlen($newPassword) < 6 || strlen($newPassword) > 72) {
    respond(400, [
        "success" => false,
        "message" => "New password must be between 6 and 72 characters"
    ]);
}

if ($newPassword !== $confirmPassword) {
    respond(400, [
        "success" => false,
        "message" => "New passwords do not match"
    ]);
}

// Find user
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
}

if (!$stmt) {
    respond(500, [
        "success" => false,
        "message" => "Database statement preparation failed: " . $conn->error
    ]);
}

$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    respond(404, [
        "success" => false,
        "message" => "User account not found"
    ]);
}

// Verify current password
if (!password_verify($currentPassword, $user["password_hash"])) {
    respond(401, [
        "success" => false,
        "message" => "Current password is incorrect"
    ]);
}

// Check if new password is identical to current
if (password_verify($newPassword, $user["password_hash"])) {
    respond(400, [
        "success" => false,
        "message" => "New password cannot be the same as your current password"
    ]);
}

// Update password
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$targetId = (int)$user["id"];

$updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
if (!$updateStmt) {
    respond(500, [
        "success" => false,
        "message" => "Failed to prepare password update"
    ]);
}

$updateStmt->bind_param("si", $newHash, $targetId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "message" => "Password changed successfully"
    ]);
}

$updateStmt->close();
$conn->close();

respond(500, [
    "success" => false,
    "message" => "Unable to change password"
]);

