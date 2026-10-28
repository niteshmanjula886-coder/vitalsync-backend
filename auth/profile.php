<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

function buildProfileResponse($user) {
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $baseUrl = $scheme . "://" . $host . "/vitalsync_background/";
    $imageUrl = !empty($user["profile_image"]) ? $baseUrl . $user["profile_image"] : null;

    $dob = $user["dob"] ?? null;
    $age = $user["age"] !== null ? (int)$user["age"] : null;
    if ($age === null && !empty($dob)) {
        if (preg_match('/\b(19\d\d|20\d\d)\b/', $dob, $matches)) {
            $birthYear = (int)$matches[1];
            $currentYear = (int)date("Y");
            $age = max(1, min(120, $currentYear - $birthYear));
        }
    }

    return [
        "id" => (int)$user["id"],
        "name" => $user["full_name"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "phone" => $user["phone"],
        "dob" => $user["dob"],
        "age" => $age,
        "gender" => $user["gender"],
        "height" => $user["height"] !== null ? (float)$user["height"] : null,
        "weight" => $user["weight"] !== null ? (float)$user["weight"] : null,
        "activity_level" => $user["activity_level"],
        "primary_goal" => $user["primary_goal"] ?? "Fitness",
        "profile_image" => $user["profile_image"],
        "image_url" => $imageUrl,
        "created_at" => $user["created_at"],
        "updated_at" => $user["updated_at"]
    ];
}

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: Fetch Profile
// ==========================================
if ($method === "GET") {
    $email = strtolower(trim($_GET["email"] ?? ""));
    $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);

    if ($userId <= 0 && $email === "") {
        respond(400, [
            "success" => false,
            "message" => "Please provide a valid user email or user_id in the query parameter"
        ]);
    }

    if ($userId > 0) {
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
    } else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(400, [
                "success" => false,
                "message" => "Enter a valid email address"
            ]);
        }
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
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
    $conn->close();

    if (!$user) {
        respond(404, [
            "success" => false,
            "message" => "User profile not found"
        ]);
    }

    respond(200, [
        "success" => true,
        "message" => "Profile fetched successfully",
        "profile" => buildProfileResponse($user)
    ]);
}

// ==========================================
// 2. POST / PUT: Update Profile
// ==========================================
if ($method === "POST" || $method === "PUT") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    if (!is_array($data) || empty($data)) {
        respond(400, [
            "success" => false,
            "message" => "Invalid or empty update data"
        ]);
    }

    $userId = isset($data["user_id"]) ? (int)$data["user_id"] : (isset($data["id"]) ? (int)$data["id"] : (isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0)));
    $email = strtolower(trim($data["email"] ?? $_GET["email"] ?? ""));

    if ($userId <= 0 && $email === "") {
        respond(400, [
            "success" => false,
            "message" => "user_id or email is required to identify which profile to update"
        ]);
    }

    // Check if user exists
    if ($userId > 0) {
        $checkStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1");
        $checkStmt->bind_param("i", $userId);
    } else {
        $checkStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
        $checkStmt->bind_param("s", $email);
    }
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $existing = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$existing) {
        respond(404, [
            "success" => false,
            "message" => "No profile found for the specified user"
        ]);
    }

    $targetUserId = (int)$existing["id"];

    // Fields to update dynamically
    $updates = [];
    $types = "";
    $params = [];

    if (isset($data["name"]) || isset($data["full_name"])) {
        $name = trim($data["name"] ?? $data["full_name"]);
        if ($name !== "" && mb_strlen($name) <= 100) {
            $updates[] = "full_name = ?";
            $types .= "s";
            $params[] = $name;
        }
    }

    if (isset($data["phone"])) {
        $updates[] = "phone = ?";
        $types .= "s";
        $params[] = trim((string)$data["phone"]);
    }

    if (isset($data["dob"])) {
        $dobVal = trim((string)$data["dob"]);
        $updates[] = "dob = ?";
        $types .= "s";
        $params[] = $dobVal;

        // Auto compute age if not explicitly set
        if (!isset($data["age"]) && !empty($dobVal)) {
            if (preg_match('/\b(19\d\d|20\d\d)\b/', $dobVal, $matches)) {
                $birthYear = (int)$matches[1];
                $currentYear = (int)date("Y");
                $calculatedAge = max(1, min(120, $currentYear - $birthYear));
                $updates[] = "age = ?";
                $types .= "i";
                $params[] = $calculatedAge;
            }
        }
    }

    if (isset($data["age"])) {
        $ageVal = (int)$data["age"];
        if ($ageVal > 0 && $ageVal <= 130) {
            $updates[] = "age = ?";
            $types .= "i";
            $params[] = $ageVal;
        }
    }

    if (isset($data["gender"])) {
        $updates[] = "gender = ?";
        $types .= "s";
        $params[] = trim((string)$data["gender"]);
    }

    if (isset($data["height"])) {
        $updates[] = "height = ?";
        $types .= "d";
        $params[] = (float)$data["height"];
    }

    if (isset($data["weight"])) {
        $updates[] = "weight = ?";
        $types .= "d";
        $params[] = (float)$data["weight"];
    }

    if (isset($data["activity_level"])) {
        $updates[] = "activity_level = ?";
        $types .= "s";
        $params[] = trim((string)$data["activity_level"]);
    }

    if (isset($data["primary_goal"])) {
        $updates[] = "primary_goal = ?";
        $types .= "s";
        $params[] = trim((string)$data["primary_goal"]);
    }

    if (empty($updates)) {
        respond(400, [
            "success" => false,
            "message" => "No valid fields provided to update"
        ]);
    }

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $targetUserId;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(500, [
            "success" => false,
            "message" => "Database statement preparation failed: " . $conn->error
        ]);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        respond(500, [
            "success" => false,
            "message" => "Failed to update profile: " . $stmt->error
        ]);
    }
    $stmt->close();

    // Fetch the updated profile to return
    $fetchStmt = $conn->prepare(
        "SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );
    $fetchStmt->bind_param("i", $targetUserId);
    $fetchStmt->execute();
    $updatedUser = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "message" => "Profile updated successfully",
        "profile" => buildProfileResponse($updatedUser)
    ]);
}

respond(405, [
    "success" => false,
    "message" => "Method not allowed. Use GET or POST/PUT"
]);
