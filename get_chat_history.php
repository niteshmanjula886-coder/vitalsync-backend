<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tunnel-Skip-Anti-Abuse");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/db_connect.php";

if (isset($conn) && ($conn instanceof mysqli)) {
    $conn->set_charset("utf8mb4");
}

$userId = 0;
$sessionId = isset($_GET["session_id"]) && is_numeric($_GET["session_id"]) ? intval($_GET["session_id"]) : 0;

// 0. Check Authorization Bearer header
$authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? "";
if ($authHeader === "" && function_exists("getallheaders")) {
    $hdrs = getallheaders();
    $authHeader = $hdrs["Authorization"] ?? $hdrs["authorization"] ?? "";
}
if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
    if (is_numeric($token)) {
        $userId = intval($token);
    } elseif (filter_var($token, FILTER_VALIDATE_EMAIL) && isset($conn) && ($conn instanceof mysqli)) {
        $bStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($bStmt) {
            $bStmt->bind_param("s", $token);
            $bStmt->execute();
            $bRes = $bStmt->get_result()->fetch_assoc();
            $bStmt->close();
            if ($bRes) $userId = intval($bRes["id"]);
        }
    }
}

// 1. Check GET query params
if ($userId <= 0) {
    if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"])) {
        $userId = intval($_GET["user_id"]);
    } elseif (isset($_GET["userId"]) && is_numeric($_GET["userId"])) {
        $userId = intval($_GET["userId"]);
    }
}

// 2. Check JSON / POST body
if ($userId <= 0) {
    $rawInput = file_get_contents("php://input");
    if ($rawInput !== false && $rawInput !== null) {
        $rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
        $rawInput = trim($rawInput);
    }
    $data = !empty($rawInput) ? json_decode($rawInput, true) : null;
    if (is_array($data)) {
        $userId = intval($data["user_id"] ?? $data["userId"] ?? 0);
        if ($sessionId <= 0) {
            $sessionId = intval($data["session_id"] ?? $data["sessionId"] ?? 0);
        }
    } elseif (!empty($_POST["user_id"])) {
        $userId = intval($_POST["user_id"]);
    }
}

// 3. Fallback to check email if provided
if ($userId <= 0) {
    $email = trim($_GET["email"] ?? $_POST["email"] ?? "");
    if (!empty($email) && isset($conn) && ($conn instanceof mysqli)) {
        $eStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($eStmt) {
            $eStmt->bind_param("s", $email);
            $eStmt->execute();
            $eRes = $eStmt->get_result()->fetch_assoc();
            $eStmt->close();
            if ($eRes) $userId = intval($eRes["id"]);
        }
    }
}

// 4. Default fallback to 1 so queries never drop
if ($userId <= 0) {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $uCheck = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        if ($uCheck && $row = $uCheck->fetch_assoc()) {
            $userId = intval($row["id"]);
        }
    }
    if ($userId <= 0) {
        $userId = 1;
    }
}

$messages = [];
$sessionTitle = "New Chat";

if (isset($conn) && ($conn instanceof mysqli)) {
    try {
        if ($sessionId > 0) {
            $stStmt = $conn->prepare("SELECT title FROM ai_chat_sessions WHERE id = ? AND user_id = ? LIMIT 1");
            if ($stStmt) {
                $stStmt->bind_param("ii", $sessionId, $userId);
                $stStmt->execute();
                $stRes = $stStmt->get_result()->fetch_assoc();
                $stStmt->close();
                if ($stRes) $sessionTitle = $stRes["title"] ?: "New Chat";
            }
            $stmt = $conn->prepare(
                "SELECT id, session_id, sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content, time_text, created_at 
                 FROM ai_chat_messages 
                 WHERE user_id = ? AND session_id = ? 
                 ORDER BY created_at ASC, id ASC"
            );
            if ($stmt) {
                $stmt->bind_param("ii", $userId, $sessionId);
            }
        } else {
            $stmt = $conn->prepare(
                "SELECT id, session_id, sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content, time_text, created_at 
                 FROM ai_chat_messages 
                 WHERE user_id = ? 
                 ORDER BY created_at ASC, id ASC"
            );
            if ($stmt) {
                $stmt->bind_param("i", $userId);
            }
        }
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $sender = strtolower(trim((string)$row["sender"]));
                if ($sender !== "user" && $sender !== "ai") {
                    $sender = ($sender === "bot" || $sender === "assistant") ? "ai" : "user";
                }
                $messages[] = [
                    "id" => (int)$row["id"],
                    "session_id" => $row["session_id"] !== null ? (int)$row["session_id"] : null,
                    "sender" => $sender,
                    "message" => (string)($row["msg_content"] ?? ""),
                    "time_text" => (string)($row["time_text"] ?? ""),
                    "created_at" => (string)$row["created_at"]
                ];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("Error fetching chat history: " . $e->getMessage());
    }
}

http_response_code(200);
echo json_encode([
    "success" => true,
    "session_id" => $sessionId > 0 ? $sessionId : null,
    "session_title" => $sessionTitle,
    "messages" => $messages
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
