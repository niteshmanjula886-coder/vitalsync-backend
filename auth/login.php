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

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST method required"
    ]);
    exit;
}

// Support JSON input and form data
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid or empty request data"
    ]);
    exit;
}

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$deviceName = trim($data["device_name"] ?? $_SERVER["HTTP_USER_AGENT"] ?? "Mobile Device");
if (strlen($deviceName) > 100) {
    $deviceName = substr($deviceName, 0, 100);
}

// Validate input
if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 255 ||
    !is_string($password) ||
    $password === "" ||
    strlen($password) > 72
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter a valid email and password"
    ]);
    exit;
}

// Find user account
$stmt = $conn->prepare(
    "SELECT id, full_name, email, password_hash, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database statement preparation failed"
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password"
    ]);
    $conn->close();
    exit;
}

// Record login session
$userId = (int)$user["id"];
$ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
$sessionStmt = $conn->prepare(
    "INSERT INTO login_sessions (user_id, device_name, ip_address, is_current)
     VALUES (?, ?, ?, 1)"
);
if ($sessionStmt) {
    $sessionStmt->bind_param("iss", $userId, $deviceName, $ip);
    $sessionStmt->execute();
    $sessionStmt->close();
}

// Build profile image URL
$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$baseUrl = $scheme . "://" . $host . "/vitalsync_background/";
$imageUrl = !empty($user["profile_image"]) ? $baseUrl . $user["profile_image"] : null;

// Return full profile response
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => [
        "id" => (int)$user["id"],
        "name" => $user["full_name"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "phone" => $user["phone"],
        "dob" => $user["dob"],
        "age" => $user["age"] !== null ? (int)$user["age"] : null,
        "gender" => $user["gender"],
        "height" => $user["height"] !== null ? (float)$user["height"] : null,
        "weight" => $user["weight"] !== null ? (float)$user["weight"] : null,
        "activity_level" => $user["activity_level"],
        "primary_goal" => $user["primary_goal"] ?? "Fitness",
        "profile_image" => $user["profile_image"],
        "image_url" => $imageUrl,
        "created_at" => $user["created_at"],
        "updated_at" => $user["updated_at"]
    ]
]);

$conn->close();
