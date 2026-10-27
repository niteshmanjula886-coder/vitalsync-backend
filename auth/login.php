
<?php

header("Content-Type: application/json; charset=UTF-8");

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

// Read JSON request from Android
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request data"
    ]);
    exit;
}

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";

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

// Find the account
$stmt = $conn->prepare(
    "SELECT id, full_name, email, password_hash
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

// Check account and password
if ($user = $result->fetch_assoc()) {

    if (password_verify($password, $user["password_hash"])) {

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user["id"],
                "name" => $user["full_name"],
                "email" => $user["email"]
            ]
        ]);

    } else {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
    }

} else {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password"
    ]);
}

$stmt->close();
$conn->close();
?>
