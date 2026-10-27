
<?php
header("Content-Type: application/json; charset=UTF-8");

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

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    respond(400, [
        "success" => false,
        "message" => "Invalid JSON data"
    ]);
}

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["new_password"] ?? "";
$confirm = $data["confirm_password"] ?? "";

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        "success" => false,
        "message" => "Enter a valid email address"
    ]);
}

// Validate password
if (
    !is_string($password) ||
    strlen($password) < 8 ||
    strlen($password) > 72
) {
    respond(400, [
        "success" => false,
        "message" => "Password must be 8–72 characters"
    ]);
}

// Confirm passwords match
if (!is_string($confirm) || $password !== $confirm) {
    respond(400, [
        "success" => false,
        "message" => "Passwords do not match"
    ]);
}

// Find the user
$stmt = $conn->prepare(
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

if (!$stmt) {
    respond(500, [
        "success" => false,
        "message" => "Database query failed"
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

// Securely hash the new password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Update the password
$stmt = $conn->prepare(
    "UPDATE users SET password_hash = ? WHERE id = ?"
);

if (!$stmt) {
    $conn->close();

    respond(500, [
        "success" => false,
        "message" => "Unable to prepare password update"
    ]);
}

$userId = (int) $user["id"];
$stmt->bind_param("si", $passwordHash, $userId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "message" => "Password updated successfully. Please log in."
    ]);
}

$stmt->close();
$conn->close();

respond(500, [
    "success" => false,
    "message" => "Unable to update password"
]);
?>
