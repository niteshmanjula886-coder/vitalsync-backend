<?php

ini_set("display_errors", "0");
date_default_timezone_set("Asia/Kolkata");
require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

function formatBpTimestamp($datetimeStr) {
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

function classifyBloodPressure($sys, $dia) {
    $triage = evaluateBloodPressure($sys, $dia);
    $status = $triage["status_title"];
    $urgency = $triage["urgency"];
    $tier = $triage["tier"];
    $badgeColor = $triage["badge_color"];

    switch ($status) {
        case "Low Blood Pressure (Hypotension)":
            $explanation = "Your blood pressure of {$sys}/{$dia} mmHg is below normal bounds (Hypotension: systolic < 90 or diastolic < 60 mmHg). Low arterial pressure can reduce blood flow to the brain and trigger dizziness or fatigue.";
            $activity = "Avoid standing up abruptly; sit or lie down safely with your legs slightly elevated until steadied.";
            $tip = ($urgency === "MEDICAL_ATTENTION")
                ? "Seek urgent medical evaluation now—especially if experiencing dizziness, fainting, blurred vision, or cold sweats."
                : "Drink 300–500 ml of water or an electrolyte beverage slowly and recheck your BP after resting.";
            break;
        case "Hypertensive Crisis":
            $explanation = "Your blood pressure of {$sys}/{$dia} mmHg sits in the Hypertensive Crisis threshold (>= 180 systolic or >= 120 diastolic). Extremely elevated arterial pressure requires immediate calm rest and medical assessment.";
            $activity = "Stop all physical effort immediately, sit calmly in a supported chair with uncrossed legs, and re-test after 5 minutes.";
            $tip = "If repeat readings stay near {$sys}/{$dia} mmHg or you notice chest pain, vision changes, or shortness of breath, seek emergency medical care.";
            break;
        case "Stage 2 Hypertension":
            $explanation = "Your blood pressure of {$sys}/{$dia} mmHg falls in Stage 2 Hypertension (>= 140 systolic OR >= 90 diastolic). Sustained readings in this zone increase vascular strain and should be reviewed with a doctor.";
            $activity = "Opt for gentle restorative stretching and slow breathing; avoid heavy straining or isometric lifting today.";
            $tip = "Limit dietary sodium under 1,500 mg, avoid excess caffeine, stay hydrated, and log a morning follow-up reading.";
            break;
        case "Stage 1 Hypertension":
            $explanation = "Your blood pressure of {$sys}/{$dia} mmHg sits in Stage 1 Hypertension (130–139 systolic OR 80–89 diastolic). Mild arterial tension often improves quickly with consistent movement, stress relief, and lower sodium.";
            $activity = "Enjoy a relaxed 20-minute walk or gentle mobility flow to encourage healthy vascular relaxation.";
            $tip = "Swap processed salty foods for potassium-rich fruits and vegetables and keep water pacing steady today.";
            break;
        case "Elevated Blood Pressure":
            $explanation = "Your reading of {$sys}/{$dia} mmHg is in the Elevated Blood Pressure band (120–129 systolic AND diastolic below 80). Catching mild systolic drift early makes lifestyle tuning especially effective.";
            $activity = "Take a brisk 20–30 minute walk to support arterial elasticity and steady circulation.";
            $tip = "Prioritize low-sodium meals, stress unwinding, and consistent hydration to guide BP back under 120/80 mmHg.";
            break;
        default:
            $explanation = "Your blood pressure of {$sys}/{$dia} mmHg sits in the Optimal range (90–119 systolic AND 60–79 diastolic). Arterial workload and resting vascular resistance look healthy right now.";
            $activity = "Great cardiovascular baseline for regular cardio, strength training, or active outdoor steps.";
            $tip = "Keep balanced sodium intake, good sleep hygiene, and steady hydration to maintain this healthy BP range.";
            break;
    }

    return [
        "status" => $status,
        "urgency" => $urgency,
        "tier" => $tier,
        "badge_color" => $badgeColor,
        "explanation" => $explanation,
        "recommended_activity" => $activity,
        "lifestyle_tip" => $tip
    ];
}

function queryBpOllama(array $eval, $sys, $dia, $recordedAt) {
    $bpDisplay = "{$sys}/{$dia} mmHg";
    $prompt = <<<PROMPT
Analyze this single Blood Pressure reading for a patient-facing health screen:
- Blood Pressure: {$bpDisplay}
- Clinical Status: {$eval['status']}
- Urgency Tier: {$eval['urgency']}
- Recorded: {$recordedAt}

Return ONLY valid JSON with these EXACT keys:
{
  "explanation": "Exactly 2 clear second-person sentences explaining what {$bpDisplay} ({$eval['status']}) means for arterial pressure and vascular workload.",
  "recommended_activity": "1 specific safe physical activity guidance appropriate for {$bpDisplay}.",
  "lifestyle_tip": "1 actionable tip on sodium, hydration, stress recovery, or emergency evaluation matching {$eval['urgency']}."
}

Rules:
1. Always speak in second person ("Your blood pressure...", "You..."). Never say "The patient" or "The individual".
2. Cite the exact numbers {$bpDisplay} accurately.
3. If Urgency Tier is MEDICAL_ATTENTION, advise immediate 5-minute rest + re-test and urgent medical care if still crisis-level; never encourage heavy exertion or call it normal.
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
                    || ($eval["tier"] === "Alert" && preg_match('/\b(normal|optimal|vigorous|intense workout|heavy lifting|great baseline)\b/i', $combined));
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
    "SELECT id, systolic_bp AS sys, diastolic_bp AS dia, logged_at AS recorded_at
     FROM vitals_logs
     WHERE user_id = ? AND systolic_bp IS NOT NULL AND systolic_bp > 0 AND diastolic_bp IS NOT NULL AND diastolic_bp > 0
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
        "SELECT id, CAST(value_numeric AS UNSIGNED) AS sys, CAST(value_secondary AS UNSIGNED) AS dia, recorded_at
         FROM vitals_readings
         WHERE user_id = ? AND reading_type = 'blood_pressure' AND value_numeric > 0 AND value_secondary > 0
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
        "current_display" => "-- / -- mmHg",
        "systolic" => null,
        "diastolic" => null,
        "status_title" => "No Readings Yet",
        "urgency" => "NORMAL",
        "recorded_at" => "",
        "explanation" => "No blood pressure readings are logged yet. Tap '+ Log BP' while seated upright with your arm supported at heart height to record a reliable baseline.",
        "recommended_activity" => "Sit quietly with feet flat on the floor for 5 minutes before inflating your cuff.",
        "lifestyle_tip" => "Avoid coffee or exercise for 30 minutes prior to cuff measurement for true resting values.",
        "recent_logs" => [],
        "model_used" => "vitalsync:clinical-engine"
    ]);
}

$recentLogs = [];
foreach ($rows as $row) {
    $sys = (int)$row["sys"];
    $dia = (int)$row["dia"];
    $eval = classifyBloodPressure($sys, $dia);
    $recentLogs[] = [
        "id" => (int)$row["id"],
        "value" => "{$sys}/{$dia} mmHg",
        "status" => $eval["status"],
        "urgency" => $eval["urgency"],
        "date" => formatBpTimestamp($row["recorded_at"])
    ];
}

$latestSys = (int)$rows[0]["sys"];
$latestDia = (int)$rows[0]["dia"];
$latestRecordedAt = formatBpTimestamp($rows[0]["recorded_at"]);
$latestEval = classifyBloodPressure($latestSys, $latestDia);
$ai = queryBpOllama($latestEval, $latestSys, $latestDia, $latestRecordedAt);

respondJson(200, [
    "success" => true,
    "has_data" => true,
    "current_display" => "{$latestSys} / {$latestDia} mmHg",
    "systolic" => $latestSys,
    "diastolic" => $latestDia,
    "status_title" => $latestEval["status"],
    "urgency" => $latestEval["urgency"],
    "tier" => $latestEval["tier"],
    "badge_color" => $latestEval["badge_color"],
    "recorded_at" => $latestRecordedAt,
    "explanation" => $ai["explanation"],
    "recommended_activity" => $ai["recommended_activity"],
    "lifestyle_tip" => $ai["lifestyle_tip"],
    "recent_logs" => $recentLogs,
    "model_used" => $ai["model_used"]
]);

