<?php
require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/../config/url_helper.php";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    respondJson(405, [
        "success" => false,
        "message" => "Method not allowed"
    ]);
}

$userId = resolveUserIdOrDie($conn, false);

// Accept 'avatar' (primary), or 'profile_image' / 'image' for backwards compatibility
$file = $_FILES["avatar"] ?? ($_FILES["profile_image"] ?? ($_FILES["image"] ?? null));
if (!$file || !isset($file["error"])) {
    respondJson(400, [
        "success" => false,
        "message" => "No image file provided (expected 'avatar')"
    ]);
}

if ($file["error"] !== UPLOAD_ERR_OK) {
    respondJson(400, [
        "success" => false,
        "message" => "File upload failed with error code " . (int)$file["error"]
    ]);
}

$tmpPath = $file["tmp_name"] ?? "";
if ($tmpPath === "" || !is_uploaded_file($tmpPath) && !file_exists($tmpPath)) {
    respondJson(400, [
        "success" => false,
        "message" => "Uploaded file is missing"
    ]);
}

// Validate file size (max 5 MB)
$maxSizeBytes = 5 * 1024 * 1024;
$fileSize = (int)($file["size"] ?? filesize($tmpPath));
if ($fileSize <= 0 || $fileSize > $maxSizeBytes) {
    respondJson(400, [
        "success" => false,
        "message" => "Image size must be between 1 byte and 5 MB"
    ]);
}

// Validate image content and MIME type
$allowedMimes = ["image/jpeg", "image/png", "image/webp"];
$imageInfo = @getimagesize($tmpPath);
$detectedMime = is_array($imageInfo) && !empty($imageInfo["mime"]) ? strtolower($imageInfo["mime"]) : "";
if ($detectedMime === "" && function_exists("finfo_open")) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detectedMime = strtolower((string)finfo_file($finfo, $tmpPath));
        finfo_close($finfo);
    }
}

if (!in_array($detectedMime, $allowedMimes, true)) {
    respondJson(400, [
        "success" => false,
        "message" => "Unsupported file type. Only JPEG, PNG, and WEBP images are allowed."
    ]);
}

$uploadsDir = dirname(__DIR__) . "/uploads/avatars/";
if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
    respondJson(500, [
        "success" => false,
        "message" => "Failed to prepare avatar upload directory"
    ]);
}

$filename = "avatar_" . $userId . "_" . time() . ".jpg";
$destPath = $uploadsDir . $filename;
$relativePath = "uploads/avatars/" . $filename;

$moved = is_uploaded_file($tmpPath)
    ? @move_uploaded_file($tmpPath, $destPath)
    : @copy($tmpPath, $destPath);

if (!$moved) {
    respondJson(500, [
        "success" => false,
        "message" => "Failed to save uploaded avatar image"
    ]);
}

// Fetch existing avatar and full_name before overwriting
$oldRelativePath = null;
$fullName = "VitalSync User";
$userStmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ? LIMIT 1");
if ($userStmt) {
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $uRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    if ($uRow) {
        $fullName = !empty($uRow["full_name"]) ? $uRow["full_name"] : $fullName;
        $oldRelativePath = $uRow["profile_image"] ?? null;
    }
}

// Persist relative path in users table
$updateUserStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
if (!$updateUserStmt) {
    @unlink($destPath);
    respondJson(500, [
        "success" => false,
        "message" => "Database update failed"
    ]);
}
$updateUserStmt->bind_param("si", $relativePath, $userId);
$updateUserStmt->execute();
$updateUserStmt->close();

// Keep user_profiles.avatar_url synchronized
$syncProfileStmt = $conn->prepare(
    "INSERT INTO user_profiles (user_id, full_name, avatar_url)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE avatar_url = VALUES(avatar_url)"
);
if ($syncProfileStmt) {
    $syncProfileStmt->bind_param("iss", $userId, $fullName, $relativePath);
    $syncProfileStmt->execute();
    $syncProfileStmt->close();
}

// Remove superseded local avatar file safely
if (!empty($oldRelativePath) && strpos($oldRelativePath, "uploads/") === 0 && $oldRelativePath !== $relativePath) {
    $uploadsRoot = realpath(dirname(__DIR__) . "/uploads");
    $oldRealPath = realpath(dirname(__DIR__) . "/" . ltrim($oldRelativePath, "/"));
    if ($uploadsRoot && $oldRealPath && strpos($oldRealPath, $uploadsRoot) === 0 && is_file($oldRealPath)) {
        @unlink($oldRealPath);
    }
}

$profileImageUrl = resolveAvatarUrl($relativePath);

respondJson(200, [
    "success" => true,
    "message" => "Profile picture updated",
    "user_id" => $userId,
    "profile_image" => $relativePath,
    "profile_image_url" => $profileImageUrl,
    "image_url" => $profileImageUrl,
    "avatar_url" => $profileImageUrl
]);

