<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

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
        "message" => "Invalid request"
    ]);
    exit;
}

$name = trim($data["name"] ?? "");
$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$confirmPassword = $data["confirm_password"] ?? "";

if (
    $name === "" ||
    strlen($name) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 255 ||
    !is_string($password) ||
    strlen($password) < 8 ||
    strlen($password) > 72 ||
    !is_string($confirmPassword) ||
    $password !== $confirmPassword
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Check your details and confirm both passwords match"
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
    error_log("Signup error: " . $stmt->error);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Account creation failed"
    ]);
}

$stmt->close();
$conn->close();
?>