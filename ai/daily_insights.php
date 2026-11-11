<?php

require_once __DIR__ . "/../config/auth_middleware.php";
require_once __DIR__ . "/ai_service.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];
$today = date("Y-m-d");

$forceRefresh = isset($_GET["refresh"]) && ($_GET["refresh"] === "1" || $_GET["refresh"] === "true");

// 1. Check if cached insight exists for today
if ($method === "GET" && !$forceRefresh) {
    $stmt = $conn->prepare(
        "SELECT id, category, title, summary, actionable_advice, wellness_score, is_read, model_used, created_at 
         FROM ai_daily_insights 
         WHERE user_id = ? AND insight_date = ? 
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $cached = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cached) {
        $conn->close();
        respondJson(200, [
            "success" => true,
            "cached" => true,
            "insight" => [
                "id" => (int)$cached["id"],
                "date" => $today,
                "category" => $cached["category"],
                "title" => $cached["title"],
                "summary" => $cached["summary"],
                "actionableAdvice" => $cached["actionable_advice"],
                "wellnessScore" => (int)$cached["wellness_score"],
                "modelUsed" => $cached["model_used"]
            ]
        ]);
    }
}

// 2. Synthesize Daily Insight using Patient Context & AI Service
$healthCtx = fetchUserHealthContext($conn, $userId);

$rawHr = $healthCtx["hr_current"] ?? null;
$hr = is_numeric($rawHr) && (int)$rawHr > 0 ? (int)$rawHr : null;
$rawSpo2 = $healthCtx["spo2"] ?? null;
$spo2Val = (is_string($rawSpo2) && $rawSpo2 !== "NOT_RECORDED" && preg_match('/(\d+)/', $rawSpo2, $mSpo2)) ? (int)$mSpo2[1] : null;
$steps = (int)($healthCtx["steps_current"] ?? 0);
$stepGoal = max(1, (int)($healthCtx["steps_target"] ?? 8000));
$sleep = ($healthCtx["last_sleep_hours"] ?? "NOT_RECORDED") !== "NOT_RECORDED" ? $healthCtx["last_sleep_hours"] : null;
$sleepScore = $healthCtx["last_sleep_score"] ?? null;
$water = (int)($healthCtx["water_current_ml"] ?? 0);
$targetWater = max(1, (int)($healthCtx["water_target_ml"] ?? 2500));
$goal = $healthCtx["primary_goal"] ?: "General Wellness";

$sleepMins = 0;
if ($sleep !== null && preg_match('/(\d+)\s*h\s*(?:(\d+)\s*m)?/i', $sleep, $ms)) {
    $sleepMins = ((int)$ms[1] * 60) + (isset($ms[2]) ? (int)$ms[2] : 0);
}

$computedScore = max(0, min(100, (int)round(
    min(35.0, ($steps / (float)$stepGoal) * 35.0) +
    min(35.0, ($water / (float)$targetWater) * 35.0) +
    min(30.0, ($sleepMins / 480.0) * 30.0)
)));

$telemetryLines = [
    "- Primary Goal: $goal",
    "- Daily Steps: " . number_format($steps) . " / " . number_format($stepGoal) . " steps",
    "- Hydration: " . number_format($water) . " / " . number_format($targetWater) . " mL",
    "- Sleep: " . ($sleep !== null ? $sleep : "Not logged yet")
];
if ($hr !== null) $telemetryLines[] = "- Heart Rate: $hr BPM";
if ($spo2Val !== null) $telemetryLines[] = "- SpO2: $spo2Val%";
if ($hr === null && $spo2Val === null) {
    $telemetryLines[] = "- Manual Vitals: No readings logged today (omit HR, SpO2, BP; gently suggest tapping '+ Log Vitals').";
}
$telemetryBlock = implode("\n", $telemetryLines);

$insightPrompt = <<<EOT
You are VitalSync's Jr. Doctor AI. Generate a concise 2-3 sentence daily wellness insight for a patient with ONLY these verified metrics:
$telemetryBlock

Rules:
- Omit any unmeasured biometric values (if HR/SpO2 are absent, never invent 72 bpm or 98%).
- Classify sleep strictly as <6.0h Poor, 6.0-7.0h Fair, 7.0-8.5h Optimal, >8.5h Deep / Restorative. Never use "Adequate".
- Respond in strict JSON with keys:
{
  "title": "Short catchy title (under 6 words)",
  "summary": "2-3 concise sentences of today's real stats",
  "actionable_advice": "1 specific recommendation for the next 6 hours",
  "wellness_score": $computedScore
}
EOT;

$messages = [
    ["role" => "user", "content" => $insightPrompt]
];

$aiRes = callAiChat($messages, "You are a clinical AI that outputs ONLY strict valid JSON with no markdown wrapping.");

// Build dynamic objective fallback summary
$summaryClauses = [];
if ($hr !== null) {
    if ($hr < 60) $summaryClauses[] = "Resting heart rate is low/athletic at {$hr} BPM.";
    elseif ($hr <= 100) $summaryClauses[] = "Resting heart rate of {$hr} BPM is in the normal recovery range.";
    else $summaryClauses[] = "Heart rate is elevated at {$hr} BPM, suggesting strain or fatigue.";
}
if ($spo2Val !== null) {
    $summaryClauses[] = ($spo2Val >= 95) ? "SpO2 is normal at {$spo2Val}%." : "SpO2 is sub-optimal at {$spo2Val}%.";
}
$stepsPctRaw = ($steps / (float)$stepGoal) * 100.0;
$stepsFmt = number_format($steps);
$stepGoalFmt = number_format($stepGoal);
if ($stepsPctRaw < 50.0) {
    $summaryClauses[] = "You have completed {$stepsFmt} of your {$stepGoalFmt} daily step goal. A 20-minute brisk walk will help you close the gap.";
} elseif ($stepsPctRaw < 100.0) {
    $remStepsFmt = number_format(max(0, $stepGoal - $steps));
    $summaryClauses[] = "Good progress! You're only {$remStepsFmt} steps away from hitting your personalized {$stepGoalFmt}-step target.";
} else {
    $excessStepsFmt = number_format(max(0, $steps - $stepGoal));
    $summaryClauses[] = "Target unlocked! You exceeded your {$stepGoalFmt}-step goal by {$excessStepsFmt} steps today.";
}

if ($sleepMins > 0) {
    $sleepHrs = $sleepMins / 60.0;
    if ($sleepHrs < 6.0) $summaryClauses[] = "Sleep quality is Poor at {$sleep}.";
    elseif ($sleepHrs < 7.0) $summaryClauses[] = "Sleep quality is Fair at {$sleep}.";
    elseif ($sleepHrs <= 8.5) $summaryClauses[] = "Sleep quality is Optimal at {$sleep}.";
    else $summaryClauses[] = "Sleep quality is Deep / Restorative at {$sleep}.";
}

$hydPctRaw = ($water / (float)$targetWater) * 100.0;
$remWater = max(0, $targetWater - $water);
$waterFmt = number_format($water);
$targetWaterFmt = number_format($targetWater);
$remWaterFmt = number_format($remWater);
if ($hydPctRaw < 40.0) {
    $summaryClauses[] = "Water intake is critically low ({$waterFmt} / {$targetWaterFmt} ml). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism.";
} elseif ($hydPctRaw < 75.0) {
    $summaryClauses[] = "Intake is lagging behind your daily goal ({$waterFmt} / {$targetWaterFmt} ml). Insufficient hydration can trigger false hunger cues, reduce physical endurance, and elevate resting heart rate.";
} elseif ($hydPctRaw < 100.0) {
    $summaryClauses[] = "You're close to your hydration target ({$waterFmt} / {$targetWaterFmt} ml)! Just {$remWaterFmt} ml remaining to ensure optimal electrolyte balance and physical performance.";
} else {
    $summaryClauses[] = "Daily hydration goal achieved ({$waterFmt} / {$targetWaterFmt} ml)! Optimal fluid balance supports muscle recovery and cognitive clarity.";
}
if ($hr === null && $spo2Val === null) {
    $summaryClauses[] = "Tap '+ Log Vitals' on your home dashboard to record Heart Rate, SpO2, or Blood Pressure.";
}

$title = ($computedScore >= 80) ? "Strong Daily Momentum" : (($hr === null) ? "Activity & Hydration Summary" : "Vitals & Recovery Overview");
$summary = implode(" ", $summaryClauses);
$advice = ($hr === null && $spo2Val === null)
    ? "Tap '+ Log Vitals' on Home to record Heart Rate, SpO2, and Blood Pressure for complete insights."
    : (($hydPctRaw < 40.0) ? "Drink 1–2 glasses immediately to restore fluid balance." : (($hydPctRaw < 75.0) ? "Keep a water bottle nearby to sip consistently." : "Keep steady movement and water intake going through the evening."));
$wellnessScore = $computedScore;
$modelUsed = "vitalsync:clinical-engine";

if ($aiRes !== null && !empty($aiRes["text"])) {
    $raw = trim($aiRes["text"]);
    // Strip markdown code fences if wrapped
    $raw = preg_replace('/^```(?:json)?/i', '', $raw);
    $raw = preg_replace('/```$/', '', $raw);
    $raw = trim($raw);

    $fallbackSummaryCopy = $summary;
    $parsed = json_decode($raw, true);
    if (is_array($parsed) && !empty($parsed["title"])) {
        $title = trim($parsed["title"]);
        if (!empty($parsed["summary"])) $summary = trim($parsed["summary"]);
        if (!empty($parsed["actionable_advice"])) $advice = trim($parsed["actionable_advice"]);
        $modelUsed = $aiRes["model"];
    }
    if (stripos($summary, "adequate") !== false ||
        stripos($summary, "bluetooth") !== false ||
        stripos($summary, "smartwatch") !== false ||
        stripos($summary, "wearable") !== false ||
        ($sleepMins > 0 && $sleepMins < 420 && stripos($summary, "optimal") !== false) ||
        ($sleepMins >= 420 && $sleepMins <= 540 && (stripos($summary, "insufficient") !== false || stripos($summary, "poor") !== false)) ||
        ($hr === null && (stripos($summary, "bpm") !== false || strpos($summary, "72") !== false)) ||
        ($spo2Val === null && (stripos($summary, "spo2") !== false || strpos($summary, "98%") !== false))) {
        $summary = $fallbackSummaryCopy;
    }
}

// 3. Cache into ai_daily_insights (Upsert)
$category = "DAILY_OVERVIEW";
$insStmt = $conn->prepare(
    "INSERT INTO ai_daily_insights 
        (user_id, insight_date, category, title, summary, actionable_advice, wellness_score, model_used)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE 
        title = VALUES(title),
        summary = VALUES(summary),
        actionable_advice = VALUES(actionable_advice),
        wellness_score = VALUES(wellness_score),
        model_used = VALUES(model_used),
        created_at = CURRENT_TIMESTAMP"
);
$insStmt->bind_param("isssssis", $userId, $today, $category, $title, $summary, $advice, $wellnessScore, $modelUsed);
$insStmt->execute();
$insightId = $insStmt->insert_id ?: 1;
$insStmt->close();
$conn->close();

respondJson(200, [
    "success" => true,
    "cached" => false,
    "insight" => [
        "id" => (int)$insightId,
        "date" => $today,
        "category" => $category,
        "title" => $title,
        "summary" => $summary,
        "actionableAdvice" => $advice,
        "wellnessScore" => $wellnessScore,
        "modelUsed" => $modelUsed
    ]
]);
