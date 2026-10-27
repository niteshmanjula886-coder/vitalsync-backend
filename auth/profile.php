
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    respond(405, [
        "success" => false,
        "message" => "GET method required"
    ]);
}

$email = strtolower(trim($_GET["email"] ?? ""));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        "success" => false,
        "message" => "Enter a valid email"
    ]);
}

$stmt = $conn->prepare(
    "SELECT id, full_name, email, profile_image, created_at
     FROM users
     WHERE email = ?
     LIMIT 1"
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
$conn->close();

if (!$user) {
    respond(404, [
        "success" => false,
        "message" => "Profile not found"
    ]);
}

respond(200, [
    "success" => true,
    "message" => "Profile fetched successfully",
    "profile" => $user
]);
?>
