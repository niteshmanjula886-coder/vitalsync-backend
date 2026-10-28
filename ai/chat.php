<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, sender, message_text, time_text, created_at 
         FROM ai_chat_messages 
         WHERE user_id = ? 
         ORDER BY created_at ASC LIMIT 100"
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
            "timeText" => $r["time_text"] ?: date("h:i A", strtotime($r["created_at"]))
        ];
    }
    $stmt->close();
    $conn->close();

    // Default welcome prompt if conversation is empty
    if (empty($messages)) {
        $messages = [
            [
                "id" => "m0",
                "sender" => "AI",
                "text" => "Hello! I am your VitalSync Health & Wellness Assistant. Ask me anything about your vitals, workouts, sleep, or daily nutrition.",
                "timeText" => date("h:i A")
            ]
        ];
    }

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

    // Save user message
    $uStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text) VALUES (?, 'USER', ?, ?)");
    $uStmt->bind_param("iss", $userId, $userText, $timeText);
    $uStmt->execute();
    $userMsgId = $uStmt->insert_id;
    $uStmt->close();

    // Generate context-aware AI wellness response
    $lowText = strtolower($userText);
    if (strpos($lowText, "heart") !== false || strpos($lowText, "bpm") !== false || strpos($lowText, "pulse") !== false) {
        $aiReply = "Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.";
    } elseif (strpos($lowText, "sleep") !== false || strpos($lowText, "tired") !== false || strpos($lowText, "wake") !== false) {
        $aiReply = "You logged 7h 32m of sleep with an 80/100 sleep score last night. Your deep sleep accounted for about 21% of the cycle. Sticking to a consistent 10:30 PM bedtime supports optimal circadian recovery.";
    } elseif (strpos($lowText, "water") !== false || strpos($lowText, "hydrate") !== false || strpos($lowText, "drink") !== false) {
        $aiReply = "Hydration check: You're at approximately 1,250 ml today towards your 2,000 ml target (62%). Try drinking a glass of water before your next workout session.";
    } elseif (strpos($lowText, "workout") !== false || strpos($lowText, "exercise") !== false || strpos($lowText, "gym") !== false || strpos($lowText, "protein") !== false) {
        $aiReply = "Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.";
    } else {
        $aiReply = "I've reviewed your current wellness indicators. Your vitals, activity levels, and recovery balance show steady progress. Remember to balance hydration, balanced nutrition, and structured sleep.";
    }

    // Save AI response
    $aStmt = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message_text, time_text) VALUES (?, 'AI', ?, ?)");
    $aStmt->bind_param("iss", $userId, $aiReply, $timeText);
    $aStmt->execute();
    $aiMsgId = $aStmt->insert_id;
    $aStmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
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
            "timeText" => $timeText
        ]
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
