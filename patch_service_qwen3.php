<?php

$file = "C:/xampp/htdocs/vitalsync_background/ai/ollama_service.php";
$code = file_get_contents($file);

$code = str_replace(
    'define("PRIMARY_MODEL", "qwen2.5:1.5b");',
    'define("PRIMARY_MODEL", "qwen3:4b");',
    $code
);

$code = str_replace(
    'define("FALLBACK_MODEL", "qwen2.5:0.5b");',
    'define("FALLBACK_MODEL", "qwen2.5:1.5b");',
    $code
);

// Add streamOllamaChat if not present
if (strpos($code, "function streamOllamaChat") === false) {
    $streamFn = <<<EOT


/**
 * Streams Ollama /api/chat chunks in real-time.
 */
function streamOllamaChat(array \$messages, \$systemPrompt, callable \$chunkCallback) {
    \$formattedMessages = [];
    if (!empty(\$systemPrompt)) {
        \$formattedMessages[] = [
            "role" => "system",
            "content" => \$systemPrompt
        ];
    }
    foreach (\$messages as \$m) {
        \$formattedMessages[] = [
            "role" => (\$m["role"] === "AI" || \$m["role"] === "assistant") ? "assistant" : "user",
            "content" => (string)\$m["content"]
        ];
    }

    \$payload = json_encode([
        "model" => PRIMARY_MODEL,
        "messages" => \$formattedMessages,
        "stream" => true,
        "options" => [
            "temperature" => 0.7,
            "top_p" => 0.9,
            "num_predict" => 400
        ]
    ]);

    \$ch = curl_init(OLLAMA_BASE_URL . "/api/chat");
    curl_setopt_array(\$ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => \$payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen(\$payload)
        ],
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_WRITEFUNCTION => function(\$curlHandle, \$data) use (\$chunkCallback) {
            \$lines = explode("\n", \$data);
            foreach (\$lines as \$line) {
                \$line = trim(\$line);
                if (empty(\$line)) continue;
                \$decoded = json_decode(\$line, true);
                if (isset(\$decoded["message"]["content"])) {
                    \$chunk = \$decoded["message"]["content"];
                    \$isDone = \$decoded["done"] ?? false;
                    \$chunkCallback(\$chunk, \$isDone);
                }
            }
            return strlen(\$data);
        }
    ]);

    \$success = curl_exec(\$ch);
    \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
    curl_close(\$ch);

    return (\$success && \$httpCode >= 200 && \$httpCode < 300);
}
EOT;

    $code = str_replace(
        'function isHealthKnowledgeQuery',
        $streamFn . "\n\nfunction isHealthKnowledgeQuery",
        $code
    );
}

file_put_contents($file, $code);
echo "ollama_service.php updated with Qwen3 4B and streaming\n";

