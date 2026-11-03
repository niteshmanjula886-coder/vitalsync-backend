<?php

require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/ollama_service.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, sender, message_text, time_text, model_used, created_at 
         FROM ai_chat_messages 
         WHERE user_id = ? 
         ORDER BY id ASC LIMIT 100"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($r = $result->fetch_assoc()) {
        $messages[] = [
            "id" => (string)$r["id"],
            "sender" => $r["sender"],
            "text" => $r["message_text"],
            "timeText" => $r["time_text"] ?: date("h:i A", strtotime($r["created_at"])),
            "modelUsed" => $r["model_used"] ?? "qwen3:4b"
        ];
    }
    $stmt->close();

    // Default welcome prompt if conversation is empty
    if (empty($messages)) {
        $ctx = fetchUserHealthContext($conn, $userId);
        $name = !empty($ctx["name"]) && $ctx["name"] !== "User" ? " " . $ctx["name"] : "";
        $welcomeText = "Hello{$name}! I am your VitalSync AI assistant powered by Qwen3 (4B). I'm here for anything—from daily health, sleep, and workouts, to general knowledge, movies, tech, and everyday curiosity. How can I help you today?";
        $welcomeTime = date("h:i A");

        // Insert initial message
        $wStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used) VALUES (?, 'AI', ?, ?, 'qwen3:4b')");
        $wStmt->bind_param("iss", $userId, $welcomeText, $welcomeTime);
        $wStmt->execute();
        $welcomeId = $wStmt->insert_id;
        $wStmt->close();

        $messages[] = [
            "id" => (string)$welcomeId,
            "sender" => "AI",
            "text" => $welcomeText,
            "timeText" => $welcomeTime,
            "modelUsed" => "qwen3:4b"
        ];
    }

    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($messages),
        "messages" => $messages
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $userText = trim($data["message"] ?? $data["text"] ?? $data["message_text"] ?? "");

    if ($userText === "") {
        respondJson(400, [
            "success" => false,
            "message" => "Message text cannot be empty"
        ]);
    }

    $timeText = date("h:i A");

    // 1. Save user message
    $uStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text) VALUES (?, 'USER', ?, ?)");
    $uStmt->bind_param("iss", $userId, $userText, $timeText);
    $uStmt->execute();
    $userMsgId = $uStmt->insert_id;
    $uStmt->close();

    // 2. Perform RAG Knowledge Base Retrieval
    $ragArticles = searchKnowledgeBase($conn, $userText, 2);

    // 3. Detect intent and fetch health context
    $isPersonalHealth = isUserPersonalHealthQuery($userText);
    $healthContext = fetchUserHealthContext($conn, $userId);
    $historyMessages = loadConversationHistory($conn, $userId, 8);

    // 4. Build general-purpose, RAG-grounded system prompt
    $systemPrompt = buildGeneralAiSystemPrompt($healthContext, $ragArticles, $isPersonalHealth);

    $ollamaInput = [];
    foreach ($historyMessages as $h) {
        $ollamaInput[] = [
            "role" => $h["role"],
            "content" => $h["content"]
        ];
    }
    // Include current prompt
    $ollamaInput[] = [
        "role" => "user",
        "content" => $userText
    ];

    // 5. Streaming Check (SSE)
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
        $streamWorked = streamOllamaChat($ollamaInput, $systemPrompt, function($chunk, $done) use (&$accumulated) {
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

        $aStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used) VALUES (?, 'AI', ?, ?, 'qwen3:4b')");
        $aStmt->bind_param("iss", $userId, $accumulated, $timeText);
        $aStmt->execute();
        $aStmt->close();
        $conn->close();
        exit;
    }

    // 5. Inference: Query Ollama with fallback (Standard JSON mode)
    $ollamaResult = callOllamaChat($ollamaInput, $systemPrompt);

    if ($ollamaResult !== null && !empty($ollamaResult["text"])) {
        $aiReply = $ollamaResult["text"];
        $modelUsed = $ollamaResult["model"];
        $tokensUsed = $ollamaResult["eval_count"] ?? 0;
    } else {
        // Multi-domain RAG-enhanced intelligent fallback
        $aiReply = generateIntelligentFallbackReply($userText, $healthContext, $ragArticles);
        $modelUsed = $isPersonalHealth ? "vitals:personal-sync" : (!empty($ragArticles) ? "rag:verified-kb" : "qwen3:fallback");
        $tokensUsed = 0;
    }

    // 6. Save AI Response
    $aStmt = $conn->prepare(
        "INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used, tokens_used) 
         VALUES (?, 'AI', ?, ?, ?, ?)"
    );
    $aStmt->bind_param("isssi", $userId, $aiReply, $timeText, $modelUsed, $tokensUsed);
    $aStmt->execute();
    $aiMsgId = $aStmt->insert_id;
    $aStmt->close();

    $conn->close();

    $ragSourcesSummary = array_map(function($a) {
        return ["topic" => $a["topic"], "category" => $a["category"]];
    }, $ragArticles);

    respondJson(200, [
        "success" => true,
        "status" => "success",
        "user_message" => [
            "id" => (string)$userMsgId,
            "sender" => "USER",
            "text" => $userText,
            "timeText" => $timeText
        ],
        "ai_response" => [
            "id" => (string)$aiMsgId,
            "sender" => "AI",
            "text" => $aiReply,
            "timeText" => $timeText,
            "model_used" => $modelUsed,
            "rag_sources" => $ragSourcesSummary
        ]
    ]);
}

if ($method === "DELETE") {
    $dStmt = $conn->prepare("DELETE FROM ai_chat_messages WHERE user_id = ?");
    $dStmt->bind_param("i", $userId);
    $dStmt->execute();
    $affected = $dStmt->affected_rows;
    $dStmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "message" => "Chat history cleared successfully",
        "deleted_count" => $affected
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
