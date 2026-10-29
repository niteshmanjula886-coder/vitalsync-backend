<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/db.php";

function respondJson($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function getInputData() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }
    return is_array($data) ? $data : [];
}

/**
 * Resolves user_id from Authorization header, request body, or query param.
 * Ensures the resolved user actually exists in the database.
 */
function resolveUserIdOrDie($conn, $allowGuestFallback = false) {
    $userId = 0;
    $email = "";

    // 1. Check Authorization Bearer header
    $authHeader = "";
    if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
    } elseif (!empty($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
    } elseif (function_exists("getallheaders")) {
        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? $headers["authorization"] ?? "";
    }
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
        if (is_numeric($token)) {
            $userId = (int)$token;
        } elseif (filter_var($token, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($token);
        }
    }

    // 2. Check query params
    if ($userId <= 0 && $email === "") {
        if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"])) {
            $userId = (int)$_GET["user_id"];
        } elseif (isset($_GET["id"]) && is_numeric($_GET["id"])) {
            $userId = (int)$_GET["id"];
        } elseif (isset($_GET["email"]) && filter_var($_GET["email"], FILTER_VALIDATE_EMAIL)) {
            $email = strtolower(trim($_GET["email"]));
        }
    }

    // 3. Check JSON / POST body
    if ($userId <= 0 && $email === "") {
        $data = getInputData();
        if (isset($data["user_id"]) && is_numeric($data["user_id"])) {
            $userId = (int)$data["user_id"];
        } elseif (isset($data["id"]) && is_numeric($data["id"])) {
            $userId = (int)$data["id"];
        } elseif (isset($data["email"]) && filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $email = strtolower(trim($data["email"]));
        }
    }

    // Verify user exists in database
    if ($userId > 0) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (int)$row["id"];
    }

    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (int)$row["id"];
    }

    // If fallback is enabled (for local testing with primary user when unspecified)
    if ($allowGuestFallback) {
        $check = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            return (int)$row["id"];
        }
    }

    respondJson(401, [
        "success" => false,
        "message" => "Unauthorized: Please provide a valid user_id or email via header, query parameter, or request body"
    ]);
}

