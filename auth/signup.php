<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST method required"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request data"
    ]);
    exit;
}

$name = trim($data["name"] ?? "");
$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$confirmPassword = $data["confirm_password"] ?? "";

if ($name === "" || strlen($name) > 100) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter a valid name"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter a valid email"
    ]);
    exit;
}

if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must be 8–72 characters"
    ]);
    exit;
}

if (!is_string($confirmPassword) || $password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Passwords do not match"
    ]);
    exit;
}

// Enforce one account for this app
$check = $conn->query("SELECT id FROM users LIMIT 1");

if (!$check) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to check account"
    ]);
    exit;
}

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "Account already exists"
    ]);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (full_name, email, password_hash)
     VALUES (?, ?, ?)"
);

$stmt->bind_param("sss", $name, $email, $passwordHash);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully"
    ]);
} elseif ($stmt->errno === 1062) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "Email already registered"
    ]);
} else {
    error_log("VitalSync signup error: " . $stmt->error);

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Account creation failed"
    ]);
}

$stmt->close();
$conn->close();