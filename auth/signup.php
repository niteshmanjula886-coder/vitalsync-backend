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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST method required"
    ]);
    exit;
}

// Support both JSON body and standard form-data / x-www-form-urlencoded
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

$name = trim($data["name"] ?? $data["full_name"] ?? "");
$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$confirmPassword = $data["confirm_password"] ?? $password;

// Optional initial profile setup fields
$phone = isset($data["phone"]) ? trim((string)$data["phone"]) : null;
$dob = isset($data["dob"]) ? trim((string)$data["dob"]) : null;
$age = isset($data["age"]) ? (int)$data["age"] : null;
$gender = isset($data["gender"]) ? trim((string)$data["gender"]) : null;
$height = isset($data["height"]) && is_numeric($data["height"]) ? (float)$data["height"] : null;
$weight = isset($data["weight"]) && is_numeric($data["weight"]) ? (float)$data["weight"] : null;
$activityLevel = isset($data["activity_level"]) ? trim((string)$data["activity_level"]) : null;
$primaryGoal = isset($data["primary_goal"]) ? trim((string)$data["primary_goal"]) : "Fitness";

// If dob is provided but age is not, calculate age
if ($age === null && !empty($dob)) {
    if (preg_match('/\b(19\d\d|20\d\d)\b/', $dob, $matches)) {
        $birthYear = (int)$matches[1];
        $currentYear = (int)date("Y");
        $age = max(1, min(120, $currentYear - $birthYear));
    }
}

// Input validation
if ($name === "" || mb_strlen($name) > 100) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter a valid name (1–100 characters)"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter a valid email address"
    ]);
    exit;
}

if (!is_string($password) || strlen($password) < 6 || strlen($password) > 72) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must be between 6 and 72 characters"
    ]);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Passwords do not match"
    ]);
    exit;
}

// Check whether email is already registered
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database prepare statement failed"
    ]);
    exit;
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "An account with this email already exists"
    ]);
    exit;
}
$checkStmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (full_name, email, password_hash, phone, dob, age, gender, height, weight, activity_level, primary_goal)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database statement preparation failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssssisddss",
    $name,
    $email,
    $passwordHash,
    $phone,
    $dob,
    $age,
    $gender,
    $height,
    $weight,
    $activityLevel,
    $primaryGoal
);

if ($stmt->execute()) {
    $newUserId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully",
        "user" => [
            "id" => $newUserId,
            "name" => $name,
            "full_name" => $name,
            "email" => $email,
            "phone" => $phone,
            "dob" => $dob,
            "age" => $age,
            "gender" => $gender,
            "height" => $height,
            "weight" => $weight,
            "activity_level" => $activityLevel,
            "primary_goal" => $primaryGoal
        ]
    ]);
} elseif ($stmt->errno === 1062) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "An account with this email already exists"
    ]);
    $stmt->close();
    $conn->close();
} else {
    error_log("VitalSync signup error: " . $stmt->error);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Account creation failed"
    ]);
    $stmt->close();
    $conn->close();
}
