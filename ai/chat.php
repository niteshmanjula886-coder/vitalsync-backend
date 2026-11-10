<?php

set_time_limit(180);
require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/ai_service.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "OPTIONS") {
    exit(0);
}

if ($method === "GET") {
    $sessionId = isset($_GET["session_id"]) ? (int)$_GET["session_id"] : 0;
    $sessionTitle = "New Chat";

    if ($sessionId > 0) {
        // Fetch session title
        $sStmt = $conn->prepare("SELECT title FROM ai_chat_sessions WHERE id = ? AND user_id = ? LIMIT 1");
        $sStmt->bind_param("ii", $sessionId, $userId);
        $sStmt->execute();
        $sRes = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();
        if ($sRes) {
            $sessionTitle = $sRes["title"];
        }

        $stmt = $conn->prepare(
            "SELECT id, session_id, sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content, time_text, model_used, created_at 
             FROM ai_chat_messages 
             WHERE user_id = ? AND session_id = ? 
             ORDER BY id ASC LIMIT 100"
        );
        $stmt->bind_param("ii", $userId, $sessionId);
    } else {
        // Check for latest session
        $lsStmt = $conn->prepare("SELECT id, title FROM ai_chat_sessions WHERE user_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1");
        $lsStmt->bind_param("i", $userId);
        $lsStmt->execute();
        $lsRes = $lsStmt->get_result()->fetch_assoc();
        $lsStmt->close();

        if ($lsRes) {
            $sessionId = (int)$lsRes["id"];
            $sessionTitle = $lsRes["title"];
            $stmt = $conn->prepare(
                "SELECT id, session_id, sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content, time_text, model_used, created_at 
                 FROM ai_chat_messages 
                 WHERE user_id = ? AND session_id = ? 
                 ORDER BY id ASC LIMIT 100"
            );
            $stmt->bind_param("ii", $userId, $sessionId);
        } else {
            // No sessions at all
            $stmt = $conn->prepare(
                "SELECT id, session_id, sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content, time_text, model_used, created_at 
                 FROM ai_chat_messages 
                 WHERE user_id = ? 
                 ORDER BY id ASC LIMIT 100"
            );
            $stmt->bind_param("i", $userId);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($r = $result->fetch_assoc()) {
        $normSender = (strcasecmp(trim((string)$r["sender"]), "user") === 0) ? "USER" : "AI";
        $messages[] = [
            "id" => (string)$r["id"],
            "sessionId" => (int)($r["session_id"] ?? $sessionId),
            "sender" => $normSender,
            "text" => (string)($r["msg_content"] ?? ""),
            "timeText" => $r["time_text"] ?: date("h:i A", strtotime($r["created_at"])),
            "modelUsed" => $r["model_used"] ?? "vitalsync:ai-engine"
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "session_id" => $sessionId > 0 ? $sessionId : null,
        "session_title" => $sessionTitle,
        "count" => count($messages),
        "messages" => $messages
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $userText = trim($data["message_text"] ?? $data["message"] ?? $data["text"] ?? "");

    if ($userText === "") {
        respondJson(400, [
            "success" => false,
            "message" => "Message text cannot be empty"
        ]);
    }

    $sessionId = isset($data["session_id"]) ? (int)$data["session_id"] : (isset($_GET["session_id"]) ? (int)$_GET["session_id"] : 0);
    $sessionTitle = "New Chat";

    // 1. If session_id is missing or invalid, automatically create a new session
    if ($sessionId <= 0) {
        $words = preg_split('/\s+/', $userText);
        $titleWords = array_slice($words, 0, 5);
        $sessionTitle = ucfirst(implode(' ', $titleWords));
        if (count($words) > 5) {
            $sessionTitle .= "...";
        }
        if (mb_strlen($sessionTitle) > 60) {
            $sessionTitle = mb_substr($sessionTitle, 0, 57) . "...";
        }

        $nsStmt = $conn->prepare("INSERT INTO ai_chat_sessions (user_id, title) VALUES (?, ?)");
        $nsStmt->bind_param("is", $userId, $sessionTitle);
        $nsStmt->execute();
        $sessionId = (int)$nsStmt->insert_id;
        $nsStmt->close();
    } else {
        // Retrieve title & touch updated_at
        $tStmt = $conn->prepare("SELECT title FROM ai_chat_sessions WHERE id = ? AND user_id = ? LIMIT 1");
        $tStmt->bind_param("ii", $sessionId, $userId);
        $tStmt->execute();
        $tRes = $tStmt->get_result()->fetch_assoc();
        $tStmt->close();

        if ($tRes) {
            $sessionTitle = $tRes["title"];
        }

        $upStmt = $conn->prepare("UPDATE ai_chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $upStmt->bind_param("ii", $sessionId, $userId);
        $upStmt->execute();
        $upStmt->close();
    }

    $timeText = date("h:i A");

    // 2. Load conversation history scoped to this specific session before inserting user message
    $historyMessages = loadConversationHistory($conn, $userId, 4, $sessionId);

    // 3. Save user message (populate both message and message_text for cross-schema compatibility)
    $uStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, session_id, sender, message, message_text, time_text) VALUES (?, ?, 'user', ?, ?, ?)");
    $uStmt->bind_param("iisss", $userId, $sessionId, $userText, $userText, $timeText);
    $uStmt->execute();
    $userMsgId = $uStmt->insert_id;
    $uStmt->close();

    // 4. Perform RAG Knowledge Base Retrieval
    $ragArticles = searchKnowledgeBase($conn, $userText, 1);

    // 5. Detect intent and fetch health context
    $isPersonalHealth = isUserPersonalHealthQuery($userText);
    $healthContext = fetchUserHealthContext($conn, $userId);

    // 6. Build general-purpose, RAG-grounded system prompt
    $systemPrompt = buildGeneralAiSystemPrompt($healthContext, $ragArticles, $isPersonalHealth);

    $aiInput = [];
    foreach ($historyMessages as $h) {
        $aiInput[] = [
            "role" => $h["role"],
            "content" => $h["content"]
        ];
    }
    // Include current prompt
    $aiInput[] = [
        "role" => "user",
        "content" => $userText
    ];

    // 7. Streaming Check (SSE)
    $isStream = (isset($_GET["stream"]) && ($_GET["stream"] === "1" || $_GET["stream"] === "true")) ||
                (isset($data["stream"]) && ($data["stream"] === true || $data["stream"] === "true" || $data["stream"] === 1));

    if ($isStream) {
        header("Content-Type: text/event-stream; charset=UTF-8");
        header("Cache-Control: no-cache");
        header("Connection: keep-alive");
        header("X-Accel-Buffering: no");

        while (ob_get_level()) {
            ob_end_clean();
        }

        $accumulated = "";
        $streamWorked = streamAiChat($aiInput, $systemPrompt, function($chunk, $done) use (&$accumulated) {
            $accumulated .= $chunk;
            echo "data: " . json_encode(["chunk" => $chunk, "done" => $done]) . "\n\n";
            @flush();
        });

        if (!$streamWorked || empty($accumulated)) {
            $fallback = generateIntelligentFallbackReply($userText, $healthContext, $ragArticles);
            $words = explode(" ", $fallback);
            foreach ($words as $idx => $w) {
                $isLast = ($idx === count($words) - 1);
                echo "data: " . json_encode(["chunk" => $w . " ", "done" => $isLast]) . "\n\n";
                @flush();
                usleep(12000);
            }
            $accumulated = $fallback;
        }

        $aStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, session_id, sender, message, message_text, time_text, model_used) VALUES (?, ?, 'ai', ?, ?, ?, 'vitalsync:ai-engine')");
        $aStmt->bind_param("iisss", $userId, $sessionId, $accumulated, $accumulated, $timeText);
        $aStmt->execute();
        $aStmt->close();
        $conn->close();
        exit;
    }

    // 8. Inference: Query AI service or perform RAG-enhanced intelligent fallback
    $aiResult = callAiChat($aiInput, $systemPrompt);

    if ($aiResult !== null && !empty($aiResult["text"])) {
        $aiReply = $aiResult["text"];
        $modelUsed = $aiResult["model"];
        $tokensUsed = $aiResult["eval_count"] ?? 0;
    } else {
        // Multi-domain RAG-enhanced intelligent reasoning
        $aiReply = generateIntelligentFallbackReply($userText, $healthContext, $ragArticles);
        $modelUsed = $isPersonalHealth ? "vitals:personal-sync" : (!empty($ragArticles) ? "rag:verified-kb" : "vitalsync:ai-engine");
        $tokensUsed = 0;
    }

    // 9. Save AI Response & touch session timestamp
    $aStmt = $conn->prepare(
        "INSERT INTO ai_chat_messages (user_id, session_id, sender, message, message_text, time_text, model_used, tokens_used) 
         VALUES (?, ?, 'ai', ?, ?, ?, ?, ?)"
    );
    $aStmt->bind_param("iissssi", $userId, $sessionId, $aiReply, $aiReply, $timeText, $modelUsed, $tokensUsed);
    $aStmt->execute();
    $aiMsgId = $aStmt->insert_id;
    $aStmt->close();

    $touchStmt = $conn->prepare("UPDATE ai_chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    if ($touchStmt) {
        $touchStmt->bind_param("ii", $sessionId, $userId);
        $touchStmt->execute();
        $touchStmt->close();
    }

    $conn->close();

    $ragSourcesSummary = array_map(function($a) {
        return ["topic" => $a["topic"], "category" => $a["category"]];
    }, $ragArticles);

    respondJson(200, [
        "success" => true,
        "status" => "success",
        "reply" => $aiReply,
        "session_id" => (int)$sessionId,
        "session_title" => $sessionTitle,
        "user_message" => [
            "id" => (string)$userMsgId,
            "sessionId" => (int)$sessionId,
            "sender" => "USER",
            "text" => $userText,
            "timeText" => $timeText
        ],
        "ai_response" => [
            "id" => (string)$aiMsgId,
            "sessionId" => (int)$sessionId,
            "sender" => "AI",
            "text" => $aiReply,
            "timeText" => $timeText,
            "model_used" => $modelUsed,
            "rag_sources" => $ragSourcesSummary
        ]
    ]);
}

if ($method === "DELETE") {
    $sessionId = isset($_GET["session_id"]) ? (int)$_GET["session_id"] : 0;
    if ($sessionId > 0) {
        $dmStmt = $conn->prepare("DELETE FROM ai_chat_messages WHERE session_id = ? AND user_id = ?");
        if ($dmStmt) {
            $dmStmt->bind_param("ii", $sessionId, $userId);
            $dmStmt->execute();
            $dmStmt->close();
        }
        $dStmt = $conn->prepare("DELETE FROM ai_chat_sessions WHERE id = ? AND user_id = ?");
        $dStmt->bind_param("ii", $sessionId, $userId);
        $dStmt->execute();
        $affected = $dStmt->affected_rows;
        $dStmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Chat session deleted successfully",
            "session_id" => $sessionId,
            "deleted_count" => $affected
        ]);
    } else {
        // Clear all chat sessions and messages for the user
        $dStmt1 = $conn->prepare("DELETE FROM ai_chat_sessions WHERE user_id = ?");
        $dStmt1->bind_param("i", $userId);
        $dStmt1->execute();
        $dStmt1->close();

        $dStmt2 = $conn->prepare("DELETE FROM ai_chat_messages WHERE user_id = ?");
        $dStmt2->bind_param("i", $userId);
        $dStmt2->execute();
        $affected = $dStmt2->affected_rows;
        $dStmt2->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "All chat history cleared successfully",
            "deleted_count" => $affected
        ]);
    }
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
