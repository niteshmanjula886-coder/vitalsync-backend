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
        meal_type VARCHAR(50) NOT NULL,
        food_description TEXT NOT NULL,
        calories INT NOT NULL DEFAULT 0,
        protein_g FLOAT NOT NULL DEFAULT 0,
        carbs_g FLOAT NOT NULL DEFAULT 0,
        fats_g FLOAT NOT NULL DEFAULT 0,
        ai_feedback TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

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

while ($row = $res->fetch_assoc()) {
    $cal = (int)$row["calories"];
    $p = (float)$row["protein_g"];
    $c = (float)$row["carbs_g"];
    $f = (float)$row["fats_g"];

    $totalCal += $cal;
    $totalProtein += $p;
    $totalCarbs += $c;
    $totalFats += $f;

    $meals[] = [
        "id" => (int)$row["id"],
        "meal_type" => $row["meal_type"],
        "food_description" => $row["food_description"],
        "calories" => $cal,
        "protein_g" => $p,
        "carbs_g" => $c,
        "fats_g" => $f,
        "ai_feedback" => $row["ai_feedback"] ?? "",
        "created_at" => $row["created_at"],
        "time_formatted" => date("h:i A", strtotime($row["created_at"]))
    ];
}
$stmt->close();

// Standard Daily Nutritional Targets (aligned with active lifestyle)
$targetCalories = 2200;
$targetProtein = 140.0;
$targetCarbs = 240.0;
$targetFats = 65.0;

// Dynamic AI coach tip
$proteinPct = round(($totalProtein / max(1.0, $targetProtein)) * 100);
$calPct = round(($totalCal / max(1, $targetCalories)) * 100);

if ($totalCal <= 0) {
    $aiCoachTip = "No meals logged yet today. Log your pre-workout, post-workout, or daily meals for instant AI macro breakdown!";
} elseif ($proteinPct >= 90) {
    $aiCoachTip = "Fantastic! You've achieved {$proteinPct}% of your daily protein target ({$totalProtein}g / {$targetProtein}g). Optimal for muscle hypertrophy and recovery.";
} elseif ($proteinPct >= 50) {
    $aiCoachTip = "On track with {$totalProtein}g protein ({$proteinPct}% of goal). Consider a high-protein post-workout snack or shake to finish the day strong.";
} else {
    $aiCoachTip = "Logged {$totalCal} kcal and {$totalProtein}g protein so far. Prioritize lean protein in your next meal to support recovery.";
}

respondJson(200, [
    "success" => true,
    "date" => $date,
    "total_calories" => $totalCal,
    "total_protein" => round($totalProtein, 1),
    "total_carbs" => round($totalCarbs, 1),
    "total_fats" => round($totalFats, 1),
    "target_calories" => $targetCalories,
    "target_protein" => $targetProtein,
    "target_carbs" => $targetCarbs,
    "target_fats" => $targetFats,
    "ai_coach_tip" => $aiCoachTip,
    "meals" => $meals
]);
