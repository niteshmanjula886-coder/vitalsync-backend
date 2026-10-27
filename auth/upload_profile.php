
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

// Get the registered user's email
$email = strtolower(trim($_POST["email"] ?? ""));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        "success" => false,
        "message" => "Enter a valid email"
    ]);
}

// Check uploaded image
if (!isset($_FILES["profile_image"])) {
    respond(400, [
        "success" => false,
        "message" => "Please select a profile image"
    ]);
}

$file = $_FILES["profile_image"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    respond(400, [
        "success" => false,
        "message" => "Image upload failed"
    ]);
}

// Limit image size to 5 MB
if ($file["size"] > 5 * 1024 * 1024) {
    respond(400, [
        "success" => false,
        "message" => "Image must be smaller than 5 MB"
    ]);
}

// Validate actual image type
$imageInfo = getimagesize($file["tmp_name"]);

if ($imageInfo === false) {
    respond(400, [
        "success" => false,
        "message" => "Only valid image files are allowed"
    ]);
}

$allowedTypes = [
    IMAGETYPE_JPEG => "jpg",
    IMAGETYPE_PNG  => "png",
    IMAGETYPE_WEBP => "webp"
];

$imageType = $imageInfo[2];

if (!isset($allowedTypes[$imageType])) {
    respond(400, [
        "success" => false,
        "message" => "Use JPG, PNG, or WEBP images"
    ]);
}

// Check whether user exists
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
    respond(404, [
        "success" => false,
        "message" => "User not found"
    ]);
}

// Create upload directory if needed
$uploadDir = __DIR__ . "/../uploads/profiles/";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        respond(500, [
            "success" => false,
            "message" => "Could not create upload directory"
        ]);
    }
}

// Generate a unique filename
$filename = "user_" . $user["id"] . "_" .
            bin2hex(random_bytes(8)) . "." .
            $allowedTypes[$imageType];

$destination = $uploadDir . $filename;

// Move uploaded image
if (!move_uploaded_file($file["tmp_name"], $destination)) {
    respond(500, [
        "success" => false,
        "message" => "Could not save profile image"
    ]);
}

// Store relative image path in database
$imagePath = "uploads/profiles/" . $filename;

$stmt = $conn->prepare(
    "UPDATE users SET profile_image = ? WHERE id = ?"
);

if (!$stmt) {
    @unlink($destination);
    respond(500, [
        "success" => false,
        "message" => "Could not prepare database update"
    ]);
}

$stmt->bind_param("si", $imagePath, $user["id"]);

if (!$stmt->execute()) {
    @unlink($destination);
    $stmt->close();

    respond(500, [
        "success" => false,
        "message" => "Could not update profile image"
    ]);
}

$stmt->close();
$conn->close();

respond(200, [
    "success" => true,
    "message" => "Profile photo uploaded successfully",
    "profile_image" => $imagePath,
    "image_url" => "http://localhost/vitalsync_background/" . $imagePath
]);
?>
