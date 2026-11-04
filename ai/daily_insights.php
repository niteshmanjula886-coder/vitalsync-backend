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

$hr = $healthCtx["heart_rate"] ?: 72;
$steps = $healthCtx["today_steps"] ?: 6420;
$sleep = $healthCtx["last_sleep_hours"] ?: "7h 25m";
$sleepScore = $healthCtx["last_sleep_score"] ?: 82;
$water = $healthCtx["today_water_ml"] ?: 1500;
$targetWater = $healthCtx["water_target_ml"] ?: 2000;
$goal = $healthCtx["primary_goal"] ?: "Build Muscle";

$insightPrompt = <<<EOT
You are VitalSync's Jr. Doctor AI. Generate a concise daily wellness insight for a patient with:
- Primary Goal: $goal
- Resting Heart Rate: $hr BPM
- Daily Steps: $steps steps
- Last Night's Sleep: $sleep (Quality: $sleepScore/100)
- Hydration: $water / $targetWater ml

Respond in strict JSON with keys:
{
  "title": "Short catchy title (under 6 words)",
  "summary": "1-2 sentence medical summary of today's stats",
  "actionable_advice": "1 specific recommendation for the next 6 hours",
  "wellness_score": integer between 60 and 98
}
EOT;

$messages = [
    ["role" => "user", "content" => $insightPrompt]
];

$aiRes = callAiChat($messages, "You are a clinical AI that outputs ONLY strict valid JSON with no markdown wrapping.");

$title = "Activity & Recovery Harmony";
$summary = "Your resting heart rate is stable at {$hr} BPM, and activity is progressing well with " . number_format($steps) . " steps logged today.";
$advice = "Hydration is at {$water} ml; drinking an additional glass of water will help maintain optimal cardiac output.";
$wellnessScore = 86;
$modelUsed = "vitalsync:clinical-engine";

if ($aiRes !== null && !empty($aiRes["text"])) {
    $raw = trim($aiRes["text"]);
    // Strip markdown code fences if wrapped
    $raw = preg_replace('/^```(?:json)?/i', '', $raw);
    $raw = preg_replace('/```$/', '', $raw);
    $raw = trim($raw);

    $parsed = json_decode($raw, true);
    if (is_array($parsed) && !empty($parsed["title"])) {
        $title = trim($parsed["title"]);
        if (!empty($parsed["summary"])) $summary = trim($parsed["summary"]);
        if (!empty($parsed["actionable_advice"])) $advice = trim($parsed["actionable_advice"]);
        if (!empty($parsed["wellness_score"])) $wellnessScore = (int)$parsed["wellness_score"];
        $modelUsed = $aiRes["model"];
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
