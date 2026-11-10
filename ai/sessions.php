<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "OPTIONS") {
    exit(0);
}

// Ensure ai_chat_sessions check never crashes on missing table/column
$sessionsTableExists = false;
try {
    $tblChk = $conn->query("SHOW TABLES LIKE 'ai_chat_sessions'");
    if ($tblChk && $tblChk->num_rows > 0) {
        $sessionsTableExists = true;
    }
} catch (Throwable $e) {
    $sessionsTableExists = false;
}

if (!$sessionsTableExists) {
    $conn->close();
    respondJson(200, [
        "success" => true,
        "count" => 0,
        "sessions" => []
    ]);
}

// 1. GET: Fetch list of chat sessions for user
if ($method === "GET") {
    $sessions = [];
    try {
        // Backfill any un-sessioned legacy messages for this user into a saved session so nothing is lost
        $orphanCheck = $conn->prepare(
            "SELECT COUNT(*) AS cnt,
                    (SELECT COALESCE(NULLIF(m0.message_text, ''), m0.message)
                     FROM ai_chat_messages m0
                     WHERE m0.user_id = ? AND (m0.session_id IS NULL OR m0.session_id = 0) AND LOWER(m0.sender) = 'user'
                     ORDER BY m0.id ASC LIMIT 1) AS first_prompt
             FROM ai_chat_messages
             WHERE user_id = ? AND (session_id IS NULL OR session_id = 0)"
        );
        if ($orphanCheck) {
            $orphanCheck->bind_param("ii", $userId, $userId);
            $orphanCheck->execute();
            $oRes = $orphanCheck->get_result()->fetch_assoc();
            $orphanCheck->close();
            if ($oRes && (int)$oRes["cnt"] > 0) {
                $rawTitle = trim((string)($oRes["first_prompt"] ?? ""));
                $legacyTitle = "Previous Conversation";
                if ($rawTitle !== "") {
                    $words = preg_split('/\s+/', $rawTitle);
                    $legacyTitle = ucfirst(implode(' ', array_slice($words, 0, 5)));
                    if (count($words) > 5) $legacyTitle .= "...";
                    if (mb_strlen($legacyTitle) > 60) $legacyTitle = mb_substr($legacyTitle, 0, 57) . "...";
                }
                $mkSess = $conn->prepare("INSERT INTO ai_chat_sessions (user_id, title) VALUES (?, ?)");
                if ($mkSess) {
                    $mkSess->bind_param("is", $userId, $legacyTitle);
                    $mkSess->execute();
                    $legacySessId = (int)$mkSess->insert_id;
                    $mkSess->close();
                    if ($legacySessId > 0) {
                        $linkStmt = $conn->prepare("UPDATE ai_chat_messages SET session_id = ? WHERE user_id = ? AND (session_id IS NULL OR session_id = 0)");
                        if ($linkStmt) {
                            $linkStmt->bind_param("ii", $legacySessId, $userId);
                            $linkStmt->execute();
                            $linkStmt->close();
                        }
                    }
                }
            }
        }

        $stmt = $conn->prepare(
            "SELECT s.id, s.title, s.created_at, s.updated_at,
                    (SELECT COUNT(*) FROM ai_chat_messages m WHERE m.session_id = s.id) AS message_count,
                    (SELECT COALESCE(NULLIF(m2.message_text, ''), m2.message) FROM ai_chat_messages m2 WHERE m2.session_id = s.id ORDER BY m2.id DESC LIMIT 1) AS last_message
             FROM ai_chat_sessions s 
             WHERE s.user_id = ? 
             ORDER BY s.updated_at DESC, s.id DESC 
             LIMIT 100"
        );
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $sessions[] = [
                    "id" => (int)$r["id"],
                    "title" => $r["title"] ?: "New Chat",
                    "message_count" => (int)($r["message_count"] ?? 0),
                    "last_message" => $r["last_message"] ?: "",
                    "created_at" => $r["created_at"],
                    "updated_at" => $r["updated_at"]
                ];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("Sessions query warning: " . $e->getMessage());
    }
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($sessions),
        "sessions" => $sessions
    ]);
}

// 2. POST: Create a fresh new chat session
if ($method === "POST") {
    $data = getInputData();
    $title = trim($data["title"] ?? "New Chat");
    if ($title === "") {
        $title = "New Chat";
    }

    $stmt = $conn->prepare("INSERT INTO ai_chat_sessions (user_id, title) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $title);
    $stmt->execute();
    $newSessionId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    $now = date("Y-m-d H:i:s");
    respondJson(201, [
        "success" => true,
        "message" => "Session created successfully",
        "session_id" => (int)$newSessionId,
        "session" => [
            "id" => (int)$newSessionId,
            "title" => $title,
            "created_at" => $now,
            "updated_at" => $now
        ]
    ]);
}

// 3. DELETE: Delete a specific session and its cascade messages
if ($method === "DELETE") {
    $sessionId = isset($_GET["session_id"]) ? (int)$_GET["session_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);
    if ($sessionId <= 0) {
        $data = getInputData();
        $sessionId = isset($data["session_id"]) ? (int)$data["session_id"] : (isset($data["id"]) ? (int)$data["id"] : 0);
    }

    if ($sessionId <= 0) {
        respondJson(400, [
            "success" => false,
            "message" => "Valid session_id is required"
        ]);
    }

    $dmStmt = $conn->prepare("DELETE FROM ai_chat_messages WHERE session_id = ? AND user_id = ?");
    if ($dmStmt) {
        $dmStmt->bind_param("ii", $sessionId, $userId);
        $dmStmt->execute();
        $dmStmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM ai_chat_sessions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $sessionId, $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected > 0) {
        respondJson(200, [
            "success" => true,
            "message" => "Session and associated messages deleted successfully",
            "session_id" => $sessionId
        ]);
    } else {
        respondJson(404, [
            "success" => false,
            "message" => "Session not found or already deleted"
        ]);
    }
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

