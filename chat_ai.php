<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tunnel-Skip-Anti-Abuse");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

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

// Extract user_id from incoming JSON / POST
$userId = intval($input["user_id"] ?? $input["userId"] ?? 0);
if ($userId <= 0) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $userId = intval($_SESSION["user_id"] ?? $_SESSION["id"] ?? 0);
}
if ($userId <= 0) {
    $userId = 1;
}

$userMessage = trim((string)($input["message"] ?? $input["message_text"] ?? $input["prompt"] ?? ""));

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Message is required."
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Connect to MySQL using db_connect.php
require_once __DIR__ . "/db_connect.php";

if (isset($conn) && ($conn instanceof mysqli)) {
    $conn->set_charset("utf8mb4");
}

$latestHeartRate = null;
$latestSpo2 = null;
$todayHydrationMl = 0;

if (isset($conn) && ($conn instanceof mysqli)) {
    // 1. Fetch latest heart rate & SpO2 from vitals_logs
    try {
        $vStmt = $conn->prepare("SELECT heart_rate, spo2 FROM vitals_logs WHERE user_id = ? AND (heart_rate IS NOT NULL OR spo2 IS NOT NULL) ORDER BY logged_at DESC LIMIT 1");
        if ($vStmt) {
            $vStmt->bind_param("i", $userId);
            $vStmt->execute();
            $vRes = $vStmt->get_result()->fetch_assoc();
            $vStmt->close();
            if ($vRes) {
                if (!empty($vRes["heart_rate"])) $latestHeartRate = (int)$vRes["heart_rate"];
                if (!empty($vRes["spo2"])) $latestSpo2 = (int)$vRes["spo2"];
            }
        }
    } catch (Throwable $e) {
        error_log("Telemetry vitals_logs warning: " . $e->getMessage());
    }

    // Fallback to vitals_readings if needed
    if ($latestHeartRate === null) {
        try {
            $hrStmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' ORDER BY recorded_at DESC LIMIT 1");
            if ($hrStmt) {
                $hrStmt->bind_param("i", $userId);
                $hrStmt->execute();
                $hrRes = $hrStmt->get_result()->fetch_assoc();
                $hrStmt->close();
                if ($hrRes) $latestHeartRate = (int)$hrRes["value_numeric"];
            }
        } catch (Throwable $e) {}
    }

    if ($latestSpo2 === null) {
        try {
            $spo2Stmt = $conn->prepare("SELECT value_numeric FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' ORDER BY recorded_at DESC LIMIT 1");
            if ($spo2Stmt) {
                $spo2Stmt->bind_param("i", $userId);
                $spo2Stmt->execute();
                $spo2Res = $spo2Stmt->get_result()->fetch_assoc();
                $spo2Stmt->close();
                if ($spo2Res) $latestSpo2 = (int)$spo2Res["value_numeric"];
            }
        } catch (Throwable $e) {}
    }

    // 2. Fetch today's total hydration
    try {
        $today = date("Y-m-d");
        $hStmt = $conn->prepare("SELECT COALESCE(SUM(COALESCE(volume_ml, intake_ml, amount_ml, 0)), 0) as total_water FROM hydration_logs WHERE user_id = ? AND (log_date = ? OR logged_date = ? OR DATE(logged_at) = ?)");
        if ($hStmt) {
            $hStmt->bind_param("isss", $userId, $today, $today, $today);
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
$systemPrompt = "You are VitalSync Jr. Doctor, an empathetic and concise AI wellness assistant. Provide actionable, concise health guidance in 2-3 sentences based on available vitals.";

$telemetryContext = [];
if ($latestHeartRate !== null) {
    $telemetryContext[] = "Latest Heart Rate: {$latestHeartRate} bpm";
}
if ($latestSpo2 !== null) {
    $telemetryContext[] = "Latest SpO2: {$latestSpo2}%";
}
if ($todayHydrationMl > 0) {
    $telemetryContext[] = "Today's Water Intake: {$todayHydrationMl} mL";
}

if (!empty($telemetryContext)) {
    $systemPrompt .= "\nPatient Telemetry: " . implode(", ", $telemetryContext) . ".";
}

// Ollama payload configuration
$ollamaUrl = "http://127.0.0.1:11434/api/chat";
$ollamaPayload = [
    "model" => "qwen2.5:1.5b",
    "messages" => [
        [
            "role" => "system",
            "content" => $systemPrompt
        ],
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ],
    "stream" => false,
    "options" => [
        "temperature" => 0.4
    ]
];

$jsonPayload = json_encode($ollamaPayload, JSON_UNESCAPED_UNICODE);
$response = false;

// Dispatch cURL POST request to local Ollama server with HTTP stream fallback
if (function_exists("curl_init")) {
    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json; charset=utf-8"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $execRes = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($execRes !== false && $httpCode === 200) {
        $response = $execRes;
    }
}

// Resilient fallback using PHP stream context if curl is not loaded or failed
if ($response === false) {
    $opts = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json; charset=utf-8\r\n",
            "content" => $jsonPayload,
            "timeout" => 60,
            "ignore_errors" => true
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($ollamaUrl, false, $context);
}

if ($response === false || empty($response)) {
    error_log("Ollama connection failed: server unreachable at " . $ollamaUrl);
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "message" => "Local AI engine is offline. Ensure Ollama is running."
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

$ollamaData = json_decode($response, true);
$reply = $ollamaData["message"]["content"] ?? null;

if (empty($reply)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Empty or invalid response from AI engine."
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

$cleanReply = trim($reply);

// Persist conversation by inserting two rows into ai_chat_messages (one for user, one for ai)
if (isset($conn) && ($conn instanceof mysqli)) {
    try {
        // 1. Insert user message
        $s1 = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message) VALUES (?, 'user', ?)");
        if ($s1) {
            $s1->bind_param("is", $userId, $userMessage);
            $s1->execute();
            $s1->close();
        }

        // 2. Insert AI response
        $s2 = $conn->prepare("INSERT INTO ai_chat_messages (user_id, sender, message) VALUES (?, 'ai', ?)");
        if ($s2) {
            $s2->bind_param("is", $userId, $cleanReply);
            $s2->execute();
            $s2->close();
        }
    } catch (Throwable $e) {
        error_log("Chat persistence warning: " . $e->getMessage());
    }
}

// Return successful JSON response
http_response_code(200);
echo json_encode([
    "success" => true,
    "reply" => $cleanReply
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
