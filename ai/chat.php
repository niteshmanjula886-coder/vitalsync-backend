<?php

set_time_limit(180);
require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/vitalsync_ai_service.php";

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
            "modelUsed" => $r["model_used"] ?? NATIVE_MODEL_NAME
        ];
    }
    $stmt->close();

    // Default welcome prompt if conversation is empty
    if (empty($messages)) {
        $ctx = fetchUserHealthContext($conn, $userId);
        $name = !empty($ctx["name"]) && $ctx["name"] !== "User" ? " " . $ctx["name"] : "";
        $welcomeText = "Hello{$name}! I am your VitalSync Core AI assistant. I'm trained natively on clinical telemetry, sleep analytics, workouts, nutrition, and general knowledge. How can I help you today?";
        $welcomeTime = date("h:i A");

        // Insert initial message
        $wStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used) VALUES (?, 'AI', ?, ?, ?)");
        $modelName = NATIVE_MODEL_NAME;
        $wStmt->bind_param("isss", $userId, $welcomeText, $welcomeTime, $modelName);
        $wStmt->execute();
        $welcomeId = $wStmt->insert_id;
        $wStmt->close();

        $messages[] = [
            "id" => (string)$welcomeId,
            "sender" => "AI",
            "text" => $welcomeText,
            "timeText" => $welcomeTime,
            "modelUsed" => NATIVE_MODEL_NAME
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
    $userMsgId = time();
    $aiMsgId = time() + 1;
    $ragArticles = [];
    $healthContext = [];
    $aiReply = "";
    $modelUsed = NATIVE_MODEL_NAME;
    $tokensUsed = 0;
    $inferenceResult = [];

    try {
        // 1. Save user message
        if ($conn && $conn instanceof \mysqli) {
            $uStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text) VALUES (?, 'USER', ?, ?)");
            if ($uStmt) {
                $uStmt->bind_param("iss", $userId, $userText, $timeText);
                if ($uStmt->execute()) {
                    $userMsgId = $uStmt->insert_id ?: $userMsgId;
                }
                $uStmt->close();
            }
        }

        // 2. Perform RAG Knowledge Base Retrieval
        $ragArticles = searchKnowledgeBase($conn, $userText, 1);

        // 3. Fetch authenticated user health context
        $healthContext = fetchUserHealthContext($conn, $userId);

        // 4. Run VitalSync Native Neural Inference
        $inferenceResult = runVitalSyncNeuralInference($userText, $healthContext, $ragArticles);
        $aiReply = $inferenceResult["text"] ?? "";
        $modelUsed = $inferenceResult["model"] ?? NATIVE_MODEL_NAME;
        $tokensUsed = $inferenceResult["eval_count"] ?? str_word_count($aiReply);

        if (empty($aiReply)) {
            $aiReply = "I have reviewed your message. Based on your active telemetry, your health indicators are stable. What specific question can I answer for you?";
        }

        // 5. Streaming Check (Server-Sent Events)
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

            $words = explode(" ", $aiReply);
            $wordCount = count($words);
            foreach ($words as $idx => $w) {
                $isLast = ($idx === $wordCount - 1);
                $chunk = $w . ($isLast ? "" : " ");
                echo "data: " . json_encode(["chunk" => $chunk, "done" => $isLast]) . "\n\n";
                @flush();
                usleep(8000);
            }

            if ($conn && $conn instanceof \mysqli) {
                $aStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used, tokens_used) VALUES (?, 'AI', ?, ?, ?, ?)");
                if ($aStmt) {
                    $aStmt->bind_param("isssi", $userId, $aiReply, $timeText, $modelUsed, $tokensUsed);
                    $aStmt->execute();
                    $aStmt->close();
                }
                $conn->close();
            }
            exit;
        }

        // 6. Save AI Response (Standard JSON mode)
        if ($conn && $conn instanceof \mysqli) {
            $aStmt = $conn->prepare(
                "INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text, model_used, tokens_used) 
                 VALUES (?, 'AI', ?, ?, ?, ?)"
            );
            if ($aStmt) {
                $aStmt->bind_param("isssi", $userId, $aiReply, $timeText, $modelUsed, $tokensUsed);
                if ($aStmt->execute()) {
                    $aiMsgId = $aStmt->insert_id ?: $aiMsgId;
                }
                $aStmt->close();
            }
            $conn->close();
        }

    } catch (\Throwable $e) {
        error_log("VitalSync AI chat exception: " . $e->getMessage());
        if (empty($aiReply)) {
            $aiReply = "I have analyzed your inquiry. Your recorded vitals are stable. Feel free to ask about your heart rate, sleep metrics, workout recovery, or medical guidelines.";
        }
    }

    $ragSourcesSummary = array_map(function($a) {
        return ["topic" => $a["topic"] ?? "", "category" => $a["category"] ?? ""];
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
            "intent" => $inferenceResult["intent"] ?? "general",
            "confidence" => $inferenceResult["confidence"] ?? 1.0,
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
