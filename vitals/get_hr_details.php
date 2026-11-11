<?php

ini_set("display_errors", "0");
date_default_timezone_set("Asia/Kolkata");
require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

function formatHrTimestamp($datetimeStr) {
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

function classifyHeartRate($bpm) {
    if ($bpm < 50) {
        return [
            "status" => "Severe Bradycardia",
            "urgency" => "MEDICAL_ATTENTION",
            "explanation" => "Your heart rate of {$bpm} BPM is critically slow (Severe Bradycardia below 50 BPM). Unless you are a trained endurance athlete, a very low pulse can limit oxygen delivery to the brain and tissues.",
            "recommended_activity" => "Stop any physical exertion immediately, sit or lie down safely, and monitor how you feel.",
            "lifestyle_tip" => "If you feel dizzy, faint, confused, or notice chest tightness, seek urgent medical evaluation immediately."
        ];
    }
    if ($bpm < 60) {
        return [
            "status" => "Athletic / Low Resting",
            "urgency" => "NORMAL",
            "explanation" => "Your resting pulse of {$bpm} BPM sits in the Athletic / Low Resting zone (50–59 BPM). This commonly reflects strong vagal tone and efficient cardiovascular conditioning if you feel energetic.",
            "recommended_activity" => "Warm up gradually for 5–10 minutes before moderate exercise to let your pulse climb smoothly.",
            "lifestyle_tip" => "Stay well hydrated and track whether fatigue or lightheadedness ever accompanies lower readings."
        ];
    }
    if ($bpm <= 100) {
        return [
            "status" => "Optimal Resting",
            "urgency" => "NORMAL",
            "explanation" => "Your resting heart rate of {$bpm} BPM sits squarely inside the Optimal Resting range (60–100 BPM). This reflects steady autonomic balance and healthy cardiovascular recovery.",
            "recommended_activity" => "Ideal state for light to moderate aerobic training, strength work, or a brisk 20-minute walk.",
            "lifestyle_tip" => "Keep fluid intake steady and maintain consistent sleep hours to preserve resting heart rate stability."
        ];
    }
    if ($bpm <= 129) {
        return [
            "status" => "Elevated",
            "urgency" => "NEEDS_IMPROVEMENT",
            "explanation" => "Your heart rate of {$bpm} BPM is Elevated above normal resting bounds (101–129 BPM). Temporary elevation can follow physical effort, caffeine, dehydration, heat, or acute stress.",
            "recommended_activity" => "Avoid high-intensity workouts right now; perform 5 minutes of slow nasal or box breathing.",
            "lifestyle_tip" => "Drink a glass of water, step away from stimulants like caffeine, and recheck your pulse after 15 minutes of calm rest."
        ];
    }
    return [
        "status" => "Tachycardia / High",
        "urgency" => "MEDICAL_ATTENTION",
        "explanation" => "Your heart rate of {$bpm} BPM is markedly fast (Tachycardia at >= 130 BPM). Sustained resting pulses in this range place elevated workload on the heart and warrant prompt attention.",
        "recommended_activity" => "Pause all physical activity immediately, sit upright calmly, and focus on slow relaxed breathing.",
        "lifestyle_tip" => "Drink small sips of water and consult a healthcare provider urgently if your pulse stays elevated or causes chest pain or shortness of breath."
    ];
}

function queryHrOllama(array $eval, $bpm, $recordedAt) {
    $prompt = <<<PROMPT
Analyze this single Heart Rate reading for a patient-facing health screen:
- Heart Rate: {$bpm} BPM
- Clinical Status: {$eval['status']}
- Urgency Tier: {$eval['urgency']}
- Recorded: {$recordedAt}

Return ONLY valid JSON with these EXACT keys:
{
  "explanation": "Exactly 2 clear second-person sentences explaining what {$bpm} BPM ({$eval['status']}) means right now.",
  "recommended_activity": "1 specific safe activity guidance appropriate for {$bpm} BPM.",
  "lifestyle_tip": "1 actionable recovery, hydration, or medical advisory tip matching {$eval['urgency']}."
}

Rules:
1. Always speak in second person ("Your heart rate...", "You..."). Never say "The patient" or "The individual".
2. Cite the exact number {$bpm} BPM accurately.
3. If Urgency Tier is MEDICAL_ATTENTION, strongly advise stopping exertion and consulting a healthcare provider; never say it is normal.
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

// Fetch last 10 HR readings from vitals_logs, fallback to vitals_readings
$rows = [];
$stmt = $conn->prepare(
    "SELECT id, heart_rate AS bpm, logged_at AS recorded_at
     FROM vitals_logs
     WHERE user_id = ? AND heart_rate IS NOT NULL AND heart_rate > 0
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
        "SELECT id, CAST(value_numeric AS UNSIGNED) AS bpm, recorded_at
         FROM vitals_readings
         WHERE user_id = ? AND reading_type = 'heart_rate' AND value_numeric > 0
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
        "current_display" => "-- BPM",
        "current_bpm" => null,
        "status_title" => "No Readings Yet",
        "urgency" => "NORMAL",
        "recorded_at" => "",
        "explanation" => "No heart rate measurements are logged yet. Tap '+ Log Reading' after sitting quietly for 2–3 minutes to capture your baseline resting pulse.",
        "recommended_activity" => "Rest seated for 3 minutes before recording a calm resting heart rate baseline.",
        "lifestyle_tip" => "Morning readings before caffeine give the clearest view of overnight cardiovascular recovery.",
        "recent_logs" => [],
        "model_used" => "vitalsync:clinical-engine"
    ]);
}

$recentLogs = [];
foreach ($rows as $row) {
    $bpm = (int)$row["bpm"];
    $eval = classifyHeartRate($bpm);
    $recentLogs[] = [
        "id" => (int)$row["id"],
        "value" => "{$bpm} BPM",
        "status" => $eval["status"],
        "urgency" => $eval["urgency"],
        "date" => formatHrTimestamp($row["recorded_at"])
    ];
}

$latestBpm = (int)$rows[0]["bpm"];
$latestRecordedAt = formatHrTimestamp($rows[0]["recorded_at"]);
$latestEval = classifyHeartRate($latestBpm);
$ai = queryHrOllama($latestEval, $latestBpm, $latestRecordedAt);

respondJson(200, [
    "success" => true,
    "has_data" => true,
    "current_display" => "{$latestBpm} BPM",
    "current_bpm" => $latestBpm,
    "status_title" => $latestEval["status"],
    "urgency" => $latestEval["urgency"],
    "recorded_at" => $latestRecordedAt,
    "explanation" => $ai["explanation"],
    "recommended_activity" => $ai["recommended_activity"],
    "lifestyle_tip" => $ai["lifestyle_tip"],
    "recent_logs" => $recentLogs,
    "model_used" => $ai["model_used"]
]);

