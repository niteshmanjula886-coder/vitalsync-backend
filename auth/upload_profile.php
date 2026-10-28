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

// User identifier: email or user_id
$email = strtolower(trim($_POST["email"] ?? $_GET["email"] ?? ""));
$userId = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : (isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0);

if ($userId <= 0 && $email === "") {
    respond(400, [
        "success" => false,
        "message" => "Please provide user_id or email to attach the profile image"
    ]);
}

// Check uploaded image
if (!isset($_FILES["profile_image"])) {
    respond(400, [
        "success" => false,
        "message" => "Please select an image file under form key 'profile_image'"
    ]);
}

$file = $_FILES["profile_image"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    respond(400, [
        "success" => false,
        "message" => "Image upload failed with error code: " . $file["error"]
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
$imageInfo = @getimagesize($file["tmp_name"]);
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

// Find user in database
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id, profile_image FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $conn->prepare("SELECT id, profile_image FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
}

if (!$stmt) {
    respond(500, [
        "success" => false,
        "message" => "Database query failed: " . $conn->error
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

// Create upload directory if needed
$uploadDir = __DIR__ . "/../uploads/profiles/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        respond(500, [
            "success" => false,
            "message" => "Could not create upload directory on server"
        ]);
    }
}

// Generate unique filename
$targetUserId = (int)$user["id"];
$extension = $allowedTypes[$imageType];
$filename = "user_" . $targetUserId . "_" . bin2hex(random_bytes(8)) . "." . $extension;
$destination = $uploadDir . $filename;

// Move uploaded image
if (!move_uploaded_file($file["tmp_name"], $destination)) {
    respond(500, [
        "success" => false,
        "message" => "Could not save profile image to disk"
    ]);
}

$imagePath = "uploads/profiles/" . $filename;

// Delete old profile picture if present
if (!empty($user["profile_image"])) {
    $oldFilePath = __DIR__ . "/../" . $user["profile_image"];
    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
        @unlink($oldFilePath);
    }
}

// Update database record
$updateStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
if (!$updateStmt) {
    @unlink($destination);
    respond(500, [
        "success" => false,
        "message" => "Could not prepare database update: " . $conn->error
    ]);
}

$updateStmt->bind_param("si", $imagePath, $targetUserId);

if (!$updateStmt->execute()) {
    @unlink($destination);
    $updateStmt->close();
    respond(500, [
        "success" => false,
        "message" => "Could not update profile image in database"
    ]);
}

$updateStmt->close();
$conn->close();

// Build URL
$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$fullImageUrl = $scheme . "://" . $host . "/vitalsync_background/" . $imagePath;

respond(200, [
    "success" => true,
    "message" => "Profile photo uploaded successfully",
    "user_id" => $targetUserId,
    "profile_image" => $imagePath,
    "image_url" => $fullImageUrl
]);
