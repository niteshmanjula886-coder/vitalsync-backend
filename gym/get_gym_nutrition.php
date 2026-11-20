<?php

header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

$conn->query("
    CREATE TABLE IF NOT EXISTS gym_nutrition_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        meal_type VARCHAR(50) NOT NULL DEFAULT 'Breakfast',
        food_description TEXT NOT NULL,
        calories INT NOT NULL DEFAULT 0,
        protein_g FLOAT NOT NULL DEFAULT 0,
        carbs_g FLOAT NOT NULL DEFAULT 0,
        fats_g FLOAT NOT NULL DEFAULT 0,
        ai_feedback TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at),
        INDEX idx_user_meal (user_id, meal_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$colCheck = $conn->query("SHOW COLUMNS FROM gym_nutrition_logs LIKE 'meal_type'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE gym_nutrition_logs ADD COLUMN meal_type VARCHAR(50) NOT NULL DEFAULT 'Breakfast' AFTER user_id");
}

$userId = resolveUserIdOrDie($conn, true);
$date = trim($_GET["date"] ?? date("Y-m-d"));

$stmt = $conn->prepare("
    SELECT id, meal_type, food_description, calories, protein_g, carbs_g, fats_g, ai_feedback, created_at
    FROM gym_nutrition_logs
    WHERE user_id = ? AND DATE(created_at) = ?
    ORDER BY created_at DESC, id DESC
");

if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
}

$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$res = $stmt->get_result();

$meals = [];
$totalCal = 0;
$totalProtein = 0.0;
$totalCarbs = 0.0;
$totalFats = 0.0;

// Initialize the 4 standard meal categories
$categoryMap = [
    "Breakfast" => [
        "category" => "Breakfast",
        "calories" => 0,
        "protein_g" => 0.0,
        "carbs_g" => 0.0,
        "fats_g" => 0.0,
        "count" => 0,
        "meals" => []
    ],
    "Lunch" => [
        "category" => "Lunch",
        "calories" => 0,
        "protein_g" => 0.0,
        "carbs_g" => 0.0,
        "fats_g" => 0.0,
        "count" => 0,
        "meals" => []
    ],
    "Dinner" => [
        "category" => "Dinner",
        "calories" => 0,
        "protein_g" => 0.0,
        "carbs_g" => 0.0,
        "fats_g" => 0.0,
        "count" => 0,
        "meals" => []
    ],
    "Snacks" => [
        "category" => "Snacks",
        "calories" => 0,
        "protein_g" => 0.0,
        "carbs_g" => 0.0,
        "fats_g" => 0.0,
        "count" => 0,
        "meals" => []
    ]
];

while ($row = $res->fetch_assoc()) {
    $cal = (int)$row["calories"];
    $p = (float)$row["protein_g"];
    $c = (float)$row["carbs_g"];
    $f = (float)$row["fats_g"];

    $totalCal += $cal;
    $totalProtein += $p;
    $totalCarbs += $c;
    $totalFats += $f;

    // Normalize meal category to one of the 4 standard slots
    $rawType = trim($row["meal_type"] ?? "Breakfast");
    $lowerType = strtolower($rawType);
    if (strpos($lowerType, 'breakfast') !== false) {
        $slot = "Breakfast";
    } elseif (strpos($lowerType, 'lunch') !== false) {
        $slot = "Lunch";
    } elseif (strpos($lowerType, 'dinner') !== false) {
        $slot = "Dinner";
    } else {
        // Encompasses 'Snacks', 'Snack', 'Pre-Workout', 'Post-Workout', etc.
        $slot = "Snacks";
    }

    $mealItem = [
        "id" => (int)$row["id"],
        "meal_type" => $slot,
        "food_description" => $row["food_description"],
        "calories" => $cal,
        "protein_g" => $p,
        "carbs_g" => $c,
        "fats_g" => $f,
        "ai_feedback" => $row["ai_feedback"] ?? "",
        "created_at" => $row["created_at"],
        "time_formatted" => date("h:i A", strtotime($row["created_at"]))
    ];

    $meals[] = $mealItem;

    $categoryMap[$slot]["calories"] += $cal;
    $categoryMap[$slot]["protein_g"] += $p;
    $categoryMap[$slot]["carbs_g"] += $c;
    $categoryMap[$slot]["fats_g"] += $f;
    $categoryMap[$slot]["count"] += 1;
    $categoryMap[$slot]["meals"][] = $mealItem;
}
$stmt->close();

// Round floats in category subtotals
foreach ($categoryMap as $k => $v) {
    $categoryMap[$k]["protein_g"] = round($categoryMap[$k]["protein_g"], 1);
    $categoryMap[$k]["carbs_g"] = round($categoryMap[$k]["carbs_g"], 1);
    $categoryMap[$k]["fats_g"] = round($categoryMap[$k]["fats_g"], 1);
}

// User Nutrition Targets (Dynamic from query params with database / default fallback)
$targetCalories = isset($_GET["target_calories"]) && (int)$_GET["target_calories"] > 0
    ? (int)$_GET["target_calories"]
    : 2200;
$targetProtein = isset($_GET["target_protein"]) && (float)$_GET["target_protein"] > 0
    ? round((float)$_GET["target_protein"], 1)
    : 140.0;
$targetCarbs = isset($_GET["target_carbs"]) && (float)$_GET["target_carbs"] > 0
    ? round((float)$_GET["target_carbs"], 1)
    : round($targetCalories * 0.45 / 4.0, 1);
$targetFats = isset($_GET["target_fats"]) && (float)$_GET["target_fats"] > 0
    ? round((float)$_GET["target_fats"], 1)
    : round($targetCalories * 0.25 / 9.0, 1);

// If client did not explicitly pass targets, fetch from nutrition_goals table
if (!isset($_GET["target_calories"]) || !isset($_GET["target_protein"])) {
    $gStmt = $conn->prepare("SELECT daily_calorie_target, daily_protein_target, daily_carbs_target, daily_fat_target FROM nutrition_goals WHERE user_id = ? LIMIT 1");
    if ($gStmt) {
        $gStmt->bind_param("i", $userId);
        $gStmt->execute();
        $gRow = $gStmt->get_result()->fetch_assoc();
        $gStmt->close();
        if ($gRow) {
            if (!isset($_GET["target_calories"]) && !empty($gRow["daily_calorie_target"]) && (int)$gRow["daily_calorie_target"] > 0) {
                $targetCalories = (int)$gRow["daily_calorie_target"];
            }
            if (!isset($_GET["target_protein"]) && !empty($gRow["daily_protein_target"]) && (float)$gRow["daily_protein_target"] > 0) {
                $targetProtein = round((float)$gRow["daily_protein_target"], 1);
            }
            if (!isset($_GET["target_carbs"]) && !empty($gRow["daily_carbs_target"]) && (float)$gRow["daily_carbs_target"] > 0) {
                $targetCarbs = round((float)$gRow["daily_carbs_target"], 1);
            }
            if (!isset($_GET["target_fats"]) && !empty($gRow["daily_fat_target"]) && (float)$gRow["daily_fat_target"] > 0) {
                $targetFats = round((float)$gRow["daily_fat_target"], 1);
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Pre-calculate Exact Verified Figures & Percentages
// -----------------------------------------------------------------------------
$totalProteinRounded = round($totalProtein, 1);
$totalCarbsRounded = round($totalCarbs, 1);
$totalFatsRounded = round($totalFats, 1);

$proteinPct = $targetProtein > 0 ? round(($totalProteinRounded / $targetProtein) * 100) : 0;
$calPct = $targetCalories > 0 ? round(($totalCal / $targetCalories) * 100) : 0;
$carbsPct = $targetCarbs > 0 ? round(($totalCarbsRounded / $targetCarbs) * 100) : 0;
$fatsPct = $targetFats > 0 ? round(($totalFatsRounded / $targetFats) * 100) : 0;

$proteinRemaining = max(0.0, round($targetProtein - $totalProteinRounded, 1));
$calRemaining = max(0, $targetCalories - $totalCal);
$carbsRemaining = max(0.0, round($targetCarbs - $totalCarbsRounded, 1));
$fatsRemaining = max(0.0, round($targetFats - $totalFatsRounded, 1));

// Deterministic verified rule-based AI coach guidance
if ($totalCal <= 0) {
    $aiCoachTip = "No meals logged yet today. Log your breakfast, lunch, dinner, or snacks for instant AI macro breakdown!";
} elseif ($proteinPct >= 100) {
    $aiCoachTip = "Fantastic! You've reached 100% of your daily protein target ({$totalProteinRounded}g / {$targetProtein}g). Optimal for muscle hypertrophy and recovery.";
} elseif ($proteinPct >= 70) {
    $aiCoachTip = "On track with {$totalProteinRounded}g of {$targetProtein}g protein ({$proteinPct}% of goal) and {$proteinRemaining}g remaining. Prioritize your remaining macros to hit your daily targets.";
} elseif ($proteinPct >= 40) {
    $aiCoachTip = "Logged {$totalProteinRounded}g of {$targetProtein}g protein ({$proteinPct}%). You still need {$proteinRemaining}g protein and {$calRemaining} kcal to reach your goal today.";
} else {
    $aiCoachTip = "Logged {$totalCal} kcal and {$totalProteinRounded}g protein so far ({$proteinPct}% of {$targetProtein}g goal). Prioritize lean protein in your next meal to support recovery.";
}

// Optional Ollama (qwen2.5:1.5b) query with strict verified number instructions
if (function_exists("curl_init") && $totalCal > 0) {
    $coachPrompt = "You are the VitalSync AI Nutrition Coach. Write 1 concise, clinical and encouraging sentence (under 30 words) for the user's daily progress.\n" .
                   "VERIFIED STATS (USE ONLY THESE EXACT NUMBERS - DO NOT RECALCULATE OR INVENT NEW PERCENTAGES):\n" .
                   "- Calories: {$totalCal} / {$targetCalories} kcal ({$calPct}%, {$calRemaining} kcal remaining)\n" .
                   "- Protein: {$totalProteinRounded}g / {$targetProtein}g ({$proteinPct}%, {$proteinRemaining}g remaining)\n" .
                   "- Carbs: {$totalCarbsRounded}g / {$targetCarbs}g ({$carbsPct}%, {$carbsRemaining}g remaining)\n" .
                   "- Fats: {$totalFatsRounded}g / {$targetFats}g ({$fatsPct}%, {$fatsRemaining}g remaining)\n" .
                   "Instructions: Never claim a target is exceeded if percentage is under 100%. If protein is under target ({$proteinPct}%), guide on reaching the remaining {$proteinRemaining}g.\n" .
                   "Return ONLY valid JSON: {\"ai_coach_tip\": \"<1 concise sentence under 30 words>\"}";

    $ollamaPayload = json_encode([
        "model" => "qwen2.5:1.5b",
        "prompt" => $coachPrompt,
        "stream" => false,
        "format" => "json",
        "options" => [
            "num_gpu" => 0,
            "temperature" => 0.2,
            "num_predict" => 75
        ]
    ]);

    $ch = curl_init("http://127.0.0.1:11434/api/generate");
    if ($ch) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ollamaPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $parsedResp = json_decode($resp, true);
            $contentStr = $parsedResp["response"] ?? "";
            $feedbackData = json_decode($contentStr, true);
            if (is_array($feedbackData) && !empty($feedbackData["ai_coach_tip"])) {
                $candidate = trim($feedbackData["ai_coach_tip"]);
                if (strlen($candidate) > 10 && ($proteinPct >= 100 || strpos($candidate, "achieved 1") === false)) {
                    $aiCoachTip = $candidate;
                }
            }
        }
    }
}

respondJson(200, [
    "success" => true,
    "date" => $date,
    "total_calories" => $totalCal,
    "total_protein" => $totalProteinRounded,
    "total_carbs" => $totalCarbsRounded,
    "total_fats" => $totalFatsRounded,
    "target_calories" => $targetCalories,
    "target_protein" => $targetProtein,
    "target_carbs" => $targetCarbs,
    "target_fats" => $targetFats,
    "ai_coach_tip" => $aiCoachTip,
    "meals" => $meals,
    "categories" => array_values($categoryMap)
]);
