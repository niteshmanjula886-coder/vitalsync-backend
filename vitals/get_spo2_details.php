<?php

ini_set("display_errors", "0");
date_default_timezone_set("Asia/Kolkata");
require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

function formatSpo2Timestamp($datetimeStr) {
    if (empty($datetimeStr)) {
        return "";
    }
    $ts = strtotime($datetimeStr);
    if (!$ts) {
        return (string)$datetimeStr;
    }
    $day = date("Y-m-d", $ts);
    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 day"));
    if ($day === $today) {
        return "Today, " . date("g:i A", $ts);
    }
    if ($day === $yesterday) {
        return "Yesterday, " . date("g:i A", $ts);
    }
    return date("M j, g:i A", $ts);
}

function classifySpo2($spo2) {
    if ($spo2 >= 95) {
        return [
            "status" => "Optimal Oxygenation",
            "urgency" => "NORMAL",
            "explanation" => "Your blood oxygen saturation of {$spo2}% reflects healthy pulmonary gas exchange and strong oxygen delivery to your organs. Red blood cells are efficiently carrying oxygen throughout your bloodstream.",
            "recommended_activity" => "Cleared for normal daily movement, steady walking, or your scheduled aerobic session.",
            "lifestyle_tip" => "Maintain upright posture and steady hydration to keep airway secretions clear and breathing effortless."
        ];
    }
    if ($spo2 >= 90) {
        return [
            "status" => "Mild Hypoxia",
            "urgency" => "NEEDS_IMPROVEMENT",
            "explanation" => "Your SpO2 reading of {$spo2}% falls below the optimal 95% threshold into the Mild Hypoxia band. Shallow breathing, stuffy indoor air, cold fingers, or respiratory fatigue can temporarily lower saturation.",
            "recommended_activity" => "Sit upright cleanly and practice 5 minutes of slow diaphragmatic breathing or gentle chest-opening stretches.",
            "lifestyle_tip" => "Open a window for fresh ventilation, warm your hands, and re-measure SpO2 after 5–10 minutes of deep nasal breathing."
        ];
    }
    return [
        "status" => "Low Oxygen / Consult Doctor",
        "urgency" => "MEDICAL_ATTENTION",
        "explanation" => "Your SpO2 reading of {$spo2}% is critically low (< 90%), indicating insufficient arterial oxygen saturation. Prompt rest and immediate clinical assessment are important to rule out acute respiratory or circulatory compromise.",
        "recommended_activity" => "Stop all exertion immediately, sit upright to maximize lung expansion, and breathe calmly.",
        "lifestyle_tip" => "Ensure room ventilation and seek prompt medical care immediately if repeat readings remain under 90% or you feel breathless."
    ];
}

function querySpo2Ollama(array $eval, $spo2, $recordedAt) {
    $prompt = <<<PROMPT
Analyze this single Blood Oxygen (SpO2) reading for a patient-facing health screen:
- SpO2: {$spo2}%
- Clinical Status: {$eval['status']}
- Urgency Tier: {$eval['urgency']}
- Recorded: {$recordedAt}

Return ONLY valid JSON with these EXACT keys:
{
  "explanation": "Exactly 2 clear second-person sentences explaining what {$spo2}% SpO2 ({$eval['status']}) indicates about respiratory oxygenation.",
  "recommended_activity": "1 specific safe breathing or posture activity appropriate for {$spo2}%.",
  "lifestyle_tip": "1 actionable tip covering posture, room ventilation, hydration, or clinical advisory matching {$eval['urgency']}."
}

Rules:
1. Always speak in second person ("Your SpO2...", "You..."). Never say "The patient" or "The individual".
2. Cite the exact value {$spo2}% accurately.
3. If Urgency Tier is MEDICAL_ATTENTION, clearly instruct immediate clinical evaluation and staying upright; never call it normal or optimal.
4. Never mention Bluetooth, smartwatches, or wearables. Output ONLY valid JSON.
PROMPT;

    $payload = json_encode([
        "model" => "qwen2.5:1.5b",
        "prompt" => $prompt,
        "format" => "json",
        "stream" => false,
        "keep_alive" => "15m",
        "options" => [
            "temperature" => 0.2,
            "num_predict" => 240
        ]
    ]);

    $rawResponse = false;
    $httpCode = 0;

    if (function_exists("curl_init")) {
        $ch = curl_init("http://127.0.0.1:11434/api/generate");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 18
        ]);
        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $errno = 0;
        $errstr = "";
        $sock = @fsockopen("127.0.0.1", 11434, $errno, $errstr, 3);
        if ($sock) {
            fclose($sock);
            $ctx = stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json\r\nConnection: close\r\n",
                    "content" => $payload,
                    "timeout" => 18,
                    "ignore_errors" => true
                ]
            ]);
            $rawResponse = @file_get_contents("http://127.0.0.1:11434/api/generate", false, $ctx);
            if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            } elseif ($rawResponse !== false) {
                $httpCode = 200;
            }
        }
    }

    if ($httpCode >= 200 && $httpCode < 300 && !empty($rawResponse)) {
        $decoded = json_decode($rawResponse, true);
        $content = trim((string)($decoded["response"] ?? $decoded["message"]["content"] ?? ""));
        if ($content !== "") {
            $clean = preg_replace('/^```(?:json)?|```$/im', '', $content);
            $parsed = json_decode(trim($clean), true);
            if (is_array($parsed) && !empty($parsed["explanation"]) && !empty($parsed["recommended_activity"]) && !empty($parsed["lifestyle_tip"])) {
                $combined = $parsed["explanation"] . " " . $parsed["recommended_activity"] . " " . $parsed["lifestyle_tip"];
                $invalid = stripos($combined, "the patient") !== false
                    || stripos($combined, "the individual") !== false
                    || stripos($combined, "bluetooth") !== false
                    || stripos($combined, "smartwatch") !== false
                    || stripos($combined, "wearable") !== false
                    || ($eval["urgency"] === "MEDICAL_ATTENTION" && preg_match('/\b(normal|optimal|vigorous|intense workout)\b/i', $combined));
                if (!$invalid) {
                    return [
                        "explanation" => trim((string)$parsed["explanation"]),
                        "recommended_activity" => trim((string)$parsed["recommended_activity"]),
                        "lifestyle_tip" => trim((string)$parsed["lifestyle_tip"]),
                        "model_used" => "qwen2.5:1.5b"
                    ];
                }
            }
        }
    }

    return [
        "explanation" => $eval["explanation"],
        "recommended_activity" => $eval["recommended_activity"],
        "lifestyle_tip" => $eval["lifestyle_tip"],
        "model_used" => "vitalsync:clinical-engine"
    ];
}

$rows = [];
$stmt = $conn->prepare(
    "SELECT id, spo2 AS pct, logged_at AS recorded_at
     FROM vitals_logs
     WHERE user_id = ? AND spo2 IS NOT NULL AND spo2 > 0
     ORDER BY logged_at DESC, id DESC
     LIMIT 10"
);
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
}

if (empty($rows)) {
    $stmt2 = $conn->prepare(
        "SELECT id, CAST(value_numeric AS UNSIGNED) AS pct, recorded_at
         FROM vitals_readings
         WHERE user_id = ? AND reading_type = 'spo2' AND value_numeric > 0
         ORDER BY recorded_at DESC, id DESC
         LIMIT 10"
    );
    if ($stmt2) {
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r2 = $res2->fetch_assoc()) {
            $rows[] = $r2;
        }
        $stmt2->close();
    }
}

$conn->close();

if (empty($rows)) {
    respondJson(200, [
        "success" => true,
        "has_data" => false,
        "current_display" => "--%",
        "current_spo2" => null,
        "status_title" => "No Readings Yet",
        "urgency" => "NORMAL",
        "recorded_at" => "",
        "explanation" => "No blood oxygen (SpO2) readings are logged yet. Tap '+ Log SpO2' while seated calmly with warm fingers for an accurate baseline.",
        "recommended_activity" => "Practice 3 slow deep nasal breaths before taking your first pulse oximeter reading.",
        "lifestyle_tip" => "Avoid nail polish and rest your hand flat at chest height during SpO2 measurement.",
        "recent_logs" => [],
        "model_used" => "vitalsync:clinical-engine"
    ]);
}

$recentLogs = [];
foreach ($rows as $row) {
    $pct = (int)$row["pct"];
    $eval = classifySpo2($pct);
    $recentLogs[] = [
        "id" => (int)$row["id"],
        "value" => "{$pct}%",
        "status" => $eval["status"],
        "urgency" => $eval["urgency"],
        "date" => formatSpo2Timestamp($row["recorded_at"])
    ];
}

$latestSpo2 = (int)$rows[0]["pct"];
$latestRecordedAt = formatSpo2Timestamp($rows[0]["recorded_at"]);
$latestEval = classifySpo2($latestSpo2);
$ai = querySpo2Ollama($latestEval, $latestSpo2, $latestRecordedAt);

respondJson(200, [
    "success" => true,
    "has_data" => true,
    "current_display" => "{$latestSpo2}%",
    "current_spo2" => $latestSpo2,
    "status_title" => $latestEval["status"],
    "urgency" => $latestEval["urgency"],
    "recorded_at" => $latestRecordedAt,
    "explanation" => $ai["explanation"],
    "recommended_activity" => $ai["recommended_activity"],
    "lifestyle_tip" => $ai["lifestyle_tip"],
    "recent_logs" => $recentLogs,
    "model_used" => $ai["model_used"]
]);

