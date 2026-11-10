<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tunnel-Skip-Anti-Abuse");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("Asia/Kolkata");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST method required."
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Read and decode incoming JSON request body or form POST
$rawInput = file_get_contents("php://input");
if ($rawInput !== false && $rawInput !== null) {
    $rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
    $rawInput = trim($rawInput);
}
$input = !empty($rawInput) ? json_decode($rawInput, true) : null;
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

// Extract user_id or email from incoming JSON / POST / Authorization header
$userId = intval($input["user_id"] ?? $input["userId"] ?? 0);
$userEmail = strtolower(trim((string)($input["email"] ?? $_GET["email"] ?? $_POST["email"] ?? "")));
if ($userId <= 0 && $userEmail === "") {
    $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? "";
    if ($authHeader === "" && function_exists("getallheaders")) {
        $hdrs = getallheaders();
        $authHeader = $hdrs["Authorization"] ?? $hdrs["authorization"] ?? "";
    }
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
        if (is_numeric($token)) {
            $userId = intval($token);
        } elseif (filter_var($token, FILTER_VALIDATE_EMAIL)) {
            $userEmail = strtolower($token);
        }
    }
}

$userMessage = trim((string)($input["message"] ?? $input["message_text"] ?? $input["prompt"] ?? ""));
$requestedSessionId = intval($input["session_id"] ?? $input["sessionId"] ?? $_GET["session_id"] ?? 0);

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Message is required."
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Connect to MySQL using db_connect.php (also initializes ai_chat_sessions / session_id schema)
require_once __DIR__ . "/db_connect.php";
require_once __DIR__ . "/ai/ai_service.php";

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection error"
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

$conn->set_charset("utf8mb4");

// Resolve verified user_id in database
if ($userId <= 0 && $userEmail !== "") {
    $euStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if ($euStmt) {
        $euStmt->bind_param("s", $userEmail);
        $euStmt->execute();
        $euRes = $euStmt->get_result()->fetch_assoc();
        $euStmt->close();
        if ($euRes) {
            $userId = intval($euRes["id"]);
        }
    }
}
if ($userId <= 0) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $userId = intval($_SESSION["user_id"] ?? $_SESSION["id"] ?? 0);
}
if ($userId <= 0) {
    $firstU = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    if ($firstU && ($firstRow = $firstU->fetch_assoc())) {
        $userId = intval($firstRow["id"]);
    }
}
if ($userId <= 0) {
    $userId = 1;
}

$latestHeartRate = null;
$latestSpo2 = null;
$todayHydrationMl = 0;
$targetHydrationMl = 2500;

if (isset($conn) && ($conn instanceof mysqli)) {
    // 1. Fetch TODAY's heart rate & SpO2 from vitals_logs only (no stale old vitals)
    try {
        $vStmt = $conn->prepare("SELECT heart_rate, spo2 FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE() AND (heart_rate IS NOT NULL OR spo2 IS NOT NULL) ORDER BY logged_at DESC LIMIT 1");
        if ($vStmt) {
            $vStmt->bind_param("i", $userId);
            $vStmt->execute();
            $vRes = $vStmt->get_result()->fetch_assoc();
            $vStmt->close();
            if ($vRes) {
                if (!empty($vRes["heart_rate"]) && (int)$vRes["heart_rate"] > 0) $latestHeartRate = (int)$vRes["heart_rate"];
                if (!empty($vRes["spo2"]) && (int)$vRes["spo2"] > 0) $latestSpo2 = (int)$vRes["spo2"];
            }
        }
    } catch (Throwable $e) {
        error_log("Telemetry vitals_logs warning: " . $e->getMessage());
    }

    // Fallback to vitals_readings for TODAY only if needed
    if ($latestHeartRate === null) {
        try {
            $hrStmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1");
            if ($hrStmt) {
                $hrStmt->bind_param("i", $userId);
                $hrStmt->execute();
                $hrRes = $hrStmt->get_result()->fetch_assoc();
                $hrStmt->close();
                if ($hrRes && (float)$hrRes["value_numeric"] > 0) $latestHeartRate = (int)$hrRes["value_numeric"];
            }
        } catch (Throwable $e) {}
    }

    if ($latestSpo2 === null) {
        try {
            $spo2Stmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1");
            if ($spo2Stmt) {
                $spo2Stmt->bind_param("i", $userId);
                $spo2Stmt->execute();
                $spo2Res = $spo2Stmt->get_result()->fetch_assoc();
                $spo2Stmt->close();
                if ($spo2Res && (float)$spo2Res["value_numeric"] > 0) $latestSpo2 = (int)$spo2Res["value_numeric"];
            }
        } catch (Throwable $e) {}
    }

    // 2. Fetch user's target & today's total hydration
    try {
        $tgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
        if ($tgStmt) {
            $tgStmt->bind_param("i", $userId);
            $tgStmt->execute();
            $tgRes = $tgStmt->get_result()->fetch_assoc();
            $tgStmt->close();
            if ($tgRes && !empty($tgRes["daily_target_ml"])) {
                $targetHydrationMl = (int)$tgRes["daily_target_ml"];
            }
        }
    } catch (Throwable $e) {}

    try {
        $today = date("Y-m-d");
        $hStmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(volume_ml, intake_ml, amount_ml, 0)), 0) as total_water FROM hydration_logs WHERE user_id = ? AND (log_date = ? OR logged_date = ? OR date_string = ? OR DATE(logged_at) = ? OR DATE(created_at) = ?)");
        if ($hStmt) {
            $hStmt->bind_param("isssss", $userId, $today, $today, $today, $today, $today);
            $hStmt->execute();
            $hRes = $hStmt->get_result()->fetch_assoc();
            $hStmt->close();
            if ($hRes) {
                $todayHydrationMl = (int)$hRes["total_water"];
            }
        }
    } catch (Throwable $e) {
        error_log("Telemetry hydration warning: " . $e->getMessage());
    }
}

// Format system prompt establishing the persona
$systemPrompt = "You are VitalSync AI, an authentic, supportive health and wellness assistant. Answer the user's question directly, concisely, and practically. Use the user's health metrics only when directly relevant to their question.";

$isMetricQuery = preg_match(
    '/\b(my metrics|my stats|my vitals|my data|today\'?s|water|hydration|drink|fluid|steps|walk|heart rate|bpm|pulse|spo2|oxygen|sleep|progress|summary|overview|status|readiness)\b/i',
    $userMessage
) === 1;

if ($isMetricQuery) {
    $telemetryContext = [];
    if ($latestHeartRate !== null) {
        $telemetryContext[] = "Today's Heart Rate: {$latestHeartRate} bpm";
    }
    if ($latestSpo2 !== null) {
        $telemetryContext[] = "Today's SpO2: {$latestSpo2}%";
    }
    $telemetryContext[] = "Today's Water Intake: {$todayHydrationMl} / {$targetHydrationMl} ml";

    if (!empty($telemetryContext)) {
        $systemPrompt .= "\nUser's today metrics (reference only when relevant): " . implode("; ", $telemetryContext) . ".";
    }
}

$sessionId = $requestedSessionId;
$sessionTitle = "New Chat";
$timeText = date("h:i A");
$userMsgId = 0;
$aiMsgId = 0;

if (isset($conn) && ($conn instanceof mysqli)) {
    try {
        if ($sessionId > 0) {
            $tStmt = $conn->prepare("SELECT title FROM ai_chat_sessions WHERE id = ? AND user_id = ? LIMIT 1");
            if ($tStmt) {
                $tStmt->bind_param("ii", $sessionId, $userId);
                $tStmt->execute();
                $tRes = $tStmt->get_result()->fetch_assoc();
                $tStmt->close();
                if ($tRes) {
                    $sessionTitle = $tRes["title"] ?: "New Chat";
                } else {
                    $sessionId = 0;
                }
            }
        }

        if ($sessionId <= 0) {
            $words = preg_split('/\s+/', $userMessage);
            $sessionTitle = ucfirst(implode(' ', array_slice($words, 0, 5)));
            if (count($words) > 5) $sessionTitle .= "...";
            if (mb_strlen($sessionTitle) > 60) $sessionTitle = mb_substr($sessionTitle, 0, 57) . "...";
            if ($sessionTitle === "") $sessionTitle = "New Chat";

            $nsStmt = $conn->prepare("INSERT INTO ai_chat_sessions (user_id, title) VALUES (?, ?)");
            if ($nsStmt) {
                $nsStmt->bind_param("is", $userId, $sessionTitle);
                $nsStmt->execute();
                $sessionId = (int)$nsStmt->insert_id;
                $nsStmt->close();
            }
        } else {
            $upStmt = $conn->prepare("UPDATE ai_chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            if ($upStmt) {
                $upStmt->bind_param("ii", $sessionId, $userId);
                $upStmt->execute();
                $upStmt->close();
            }
        }
    } catch (Throwable $e) {
        error_log("Chat session setup warning: " . $e->getMessage());
    }
}

$priorHistory = (isset($conn) && ($conn instanceof mysqli) && $sessionId > 0)
    ? loadConversationHistory($conn, $userId, 4, $sessionId)
    : [];

// Save user message immediately so no conversation is ever lost
if (isset($conn) && ($conn instanceof mysqli)) {
    try {
        $sessParam = $sessionId > 0 ? $sessionId : null;
        $s1 = $conn->prepare("INSERT INTO ai_chat_messages (user_id, session_id, sender, message, message_text, time_text) VALUES (?, ?, 'user', ?, ?, ?)");
        if ($s1) {
            $s1->bind_param("iisss", $userId, $sessParam, $userMessage, $userMessage, $timeText);
            $s1->execute();
            $userMsgId = (int)$s1->insert_id;
            $s1->close();
        }
    } catch (Throwable $e) {
        error_log("User message persistence warning: " . $e->getMessage());
    }
}

// Build message list with prior session context (exclude legacy canned greeting turns)
$ollamaMessages = [
    [
        "role" => "system",
        "content" => $systemPrompt
    ]
];
foreach ($priorHistory as $h) {
    $priorContent = trim((string)($h["content"] ?? ""));
    if ($priorContent === "") {
        continue;
    }
    if ($h["role"] === "assistant" && stripos($priorContent, "I'm tracking your activity:") !== false) {
        continue;
    }
    $ollamaMessages[] = [
        "role" => $h["role"],
        "content" => $priorContent
    ];
}
$ollamaMessages[] = [
    "role" => "user",
    "content" => $userMessage
];

/**
 * Dispatches an HTTP POST JSON request to the local Ollama server via cURL,
 * falling back to native PHP HTTP streams when php_curl is unavailable in Apache.
 */
function dispatchOllamaChatRequest(string $url, array $payload, int $timeoutSeconds = 13): array {
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($jsonPayload === false) {
        return ["ok" => false, "http_code" => 0, "data" => null, "error" => "JSON encode failed: " . json_last_error_msg()];
    }

    $execRes = false;
    $httpCode = 0;
    $transportError = "";

    if (function_exists("curl_init")) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=utf-8"
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);

        $execRes = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $transportError = curl_error($ch);
        curl_close($ch);
    } elseif (filter_var(ini_get("allow_url_fopen"), FILTER_VALIDATE_BOOLEAN) || ini_get("allow_url_fopen") == "1") {
        $context = stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => "Content-Type: application/json; charset=utf-8\r\nContent-Length: " . strlen($jsonPayload) . "\r\nConnection: close\r\n",
                "content" => $jsonPayload,
                "timeout" => (float)$timeoutSeconds,
                "ignore_errors" => true
            ]
        ]);
        $execRes = @file_get_contents($url, false, $context);
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            }
        }
        if ($execRes === false) {
            $lastErr = error_get_last();
            $transportError = $lastErr["message"] ?? "HTTP stream request to Ollama failed";
        }
    } else {
        return ["ok" => false, "http_code" => 0, "data" => null, "error" => "Neither cURL nor allow_url_fopen is available"];
    }

    if ($execRes === false || $httpCode !== 200) {
        return [
            "ok" => false,
            "http_code" => $httpCode,
            "data" => null,
            "error" => $transportError !== "" ? $transportError : "Ollama returned HTTP {$httpCode}"
        ];
    }

    $decoded = json_decode((string)$execRes, true);
    if (!is_array($decoded)) {
        return ["ok" => false, "http_code" => $httpCode, "data" => null, "error" => "Invalid JSON response from Ollama"];
    }

    return ["ok" => true, "http_code" => $httpCode, "data" => $decoded, "error" => ""];
}

// Ollama payload configuration
$ollamaUrl = "http://127.0.0.1:11434/api/chat";
$ollamaPayload = [
    "model" => "qwen2.5:1.5b",
    "messages" => $ollamaMessages,
    "stream" => false,
    "keep_alive" => "15m",
    "options" => [
        "temperature" => 0.4,
        "num_predict" => 260
    ]
];

$ollamaResult = dispatchOllamaChatRequest($ollamaUrl, $ollamaPayload, 13);
$ollamaData = $ollamaResult["data"];
$reply = $ollamaResult["ok"]
    ? ($ollamaData["message"]["content"] ?? $ollamaData["response"] ?? null)
    : null;
$modelUsed = "qwen2.5:1.5b";

if (empty($reply) || trim((string)$reply) === "") {
    error_log("VitalSync chat_ai Ollama error: " . ($ollamaResult["error"] ?: "Empty response payload"));
    $errMsg = "VitalSync AI engine is currently unreachable. Please verify Ollama is running.";
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $errMsg,
        "reply" => $errMsg,
        "session_id" => $sessionId > 0 ? $sessionId : null,
        "session_title" => $sessionTitle
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

$cleanReply = trim(str_replace(["\r\n", "\r"], "\n", (string)$reply));
$cleanReply = preg_replace('/\n{3,}/', "\n\n", $cleanReply);

// Persist AI response linked to the same session_id
if (isset($conn) && ($conn instanceof mysqli)) {
    try {
        $sessParam = $sessionId > 0 ? $sessionId : null;
        $s2 = $conn->prepare("INSERT INTO ai_chat_messages (user_id, session_id, sender, message, message_text, time_text, model_used) VALUES (?, ?, 'ai', ?, ?, ?, ?)");
        if ($s2) {
            $s2->bind_param("iissss", $userId, $sessParam, $cleanReply, $cleanReply, $timeText, $modelUsed);
            $s2->execute();
            $aiMsgId = (int)$s2->insert_id;
            $s2->close();
        }
        if ($sessionId > 0) {
            $tsStmt = $conn->prepare("UPDATE ai_chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            if ($tsStmt) {
                $tsStmt->bind_param("ii", $sessionId, $userId);
                $tsStmt->execute();
                $tsStmt->close();
            }
        }
    } catch (Throwable $e) {
        error_log("AI message persistence warning: " . $e->getMessage());
    }
}

// Return successful JSON response with session linkage
http_response_code(200);
echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => $cleanReply,
    "reply" => $cleanReply,
    "session_id" => $sessionId > 0 ? $sessionId : null,
    "session_title" => $sessionTitle,
    "user_message" => [
        "id" => (string)$userMsgId,
        "sessionId" => $sessionId > 0 ? $sessionId : null,
        "sender" => "USER",
        "text" => $userMessage,
        "timeText" => $timeText
    ],
    "ai_response" => [
        "id" => (string)$aiMsgId,
        "sessionId" => $sessionId > 0 ? $sessionId : null,
        "sender" => "AI",
        "text" => $cleanReply,
        "timeText" => $timeText,
        "model_used" => $modelUsed
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
