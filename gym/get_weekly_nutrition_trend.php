<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(200);
    exit;
}

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

// -----------------------------------------------------------------------------
// 1. Resolve Target Date Range (Past 7 Days ending on selected/today date)
// -----------------------------------------------------------------------------
$todayStr = date("Y-m-d");
$endDateStr = isset($_GET["date"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET["date"]))
    ? trim($_GET["date"])
    : (isset($_GET["end_date"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET["end_date"]))
        ? trim($_GET["end_date"])
        : $todayStr);

$endDateObj = new DateTime($endDateStr);
$startDateObj = (clone $endDateObj)->modify("-6 days");
$startDateStr = $startDateObj->format("Y-m-d");

// -----------------------------------------------------------------------------
// 2. Fetch User's Nutrition Targets (Dynamic from query params with DB / fallback)
// -----------------------------------------------------------------------------
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

// Check if meal_entries table exists for unified meal aggregation
$hasMealEntriesTable = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'meal_entries'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $hasMealEntriesTable = true;
}

// -----------------------------------------------------------------------------
// 3. Aggregate Daily Logs for Each of the 7 Days
// -----------------------------------------------------------------------------
$days = [];
$weeklyTotalCal = 0;
$weeklyTotalProt = 0.0;
$weeklyTotalCarbs = 0.0;
$weeklyTotalFats = 0.0;
$daysLogged = 0;
$daysOnTarget = 0;

for ($i = 6; $i >= 0; $i--) {
    $currDate = (clone $endDateObj)->modify("-{$i} days");
    $dStr = $currDate->format("Y-m-d");
    $dayOfWeek = $currDate->format("D");
    $dateFormatted = $currDate->format("M d");
    $isToday = ($dStr === $todayStr);

    // 1) From gym_nutrition_logs
    $gymCal = 0;
    $gymProt = 0.0;
    $gymCarbs = 0.0;
    $gymFats = 0.0;
    $gymCount = 0;

    $stmtG = $conn->prepare("
        SELECT 
            COUNT(id) AS cnt,
            COALESCE(SUM(calories), 0) AS cal,
            COALESCE(SUM(protein_g), 0) AS prot,
            COALESCE(SUM(carbs_g), 0) AS carbs,
            COALESCE(SUM(fats_g), 0) AS fats
        FROM gym_nutrition_logs
        WHERE user_id = ? AND DATE(created_at) = ?
    ");
    if ($stmtG) {
        $stmtG->bind_param("is", $userId, $dStr);
        $stmtG->execute();
        $rowG = $stmtG->get_result()->fetch_assoc();
        $stmtG->close();
        if ($rowG) {
            $gymCal = (int)($rowG["cal"] ?? 0);
            $gymProt = (float)($rowG["prot"] ?? 0.0);
            $gymCarbs = (float)($rowG["carbs"] ?? 0.0);
            $gymFats = (float)($rowG["fats"] ?? 0.0);
            $gymCount = (int)($rowG["cnt"] ?? 0);
        }
    }

    // 2) From meal_entries (if table exists)
    $mealCal = 0;
    $mealProt = 0.0;
    $mealCarbs = 0.0;
    $mealFats = 0.0;
    $mealCount = 0;

    if ($hasMealEntriesTable) {
        $stmtM = $conn->prepare("
            SELECT 
                COUNT(id) AS cnt,
                COALESCE(SUM(calculated_calories), 0) AS cal,
                COALESCE(SUM(calculated_protein), 0) AS prot,
                COALESCE(SUM(calculated_carbs), 0) AS carbs,
                COALESCE(SUM(calculated_fat), 0) AS fats
            FROM meal_entries
            WHERE user_id = ? AND date_string = ?
        ");
        if ($stmtM) {
            $stmtM->bind_param("is", $userId, $dStr);
            $stmtM->execute();
            $rowM = $stmtM->get_result()->fetch_assoc();
            $stmtM->close();
            if ($rowM) {
                $mealCal = (int)($rowM["cal"] ?? 0);
                $mealProt = (float)($rowM["prot"] ?? 0.0);
                $mealCarbs = (float)($rowM["carbs"] ?? 0.0);
                $mealFats = (float)($rowM["fats"] ?? 0.0);
                $mealCount = (int)($rowM["cnt"] ?? 0);
            }
        }
    }

    $dayCal = $gymCal + $mealCal;
    $dayProt = round($gymProt + $mealProt, 1);
    $dayCarbs = round($gymCarbs + $mealCarbs, 1);
    $dayFats = round($gymFats + $mealFats, 1);
    $totalCount = $gymCount + $mealCount;

    // Determine status (within 10% of target calorie is considered on target)
    if ($dayCal <= 0) {
        $status = "No logs";
    } elseif (abs($dayCal - $targetCalories) <= ($targetCalories * 0.12)) {
        $status = "On Target";
        $daysOnTarget++;
    } elseif ($dayCal < $targetCalories) {
        $status = "Under Target";
    } else {
        $status = "Over Target";
    }

    if ($dayCal > 0) {
        $daysLogged++;
        $weeklyTotalCal += $dayCal;
        $weeklyTotalProt += $dayProt;
        $weeklyTotalCarbs += $dayCarbs;
        $weeklyTotalFats += $dayFats;
    }

    $days[] = [
        "date" => $dStr,
        "day_of_week" => $dayOfWeek,
        "day_short" => $dayOfWeek,
        "date_formatted" => $dateFormatted,
        "is_today" => $isToday,
        "calories" => $dayCal,
        "target_calories" => $targetCalories,
        "protein_g" => $dayProt,
        "target_protein_g" => $targetProtein,
        "carbs_g" => $dayCarbs,
        "target_carbs_g" => $targetCarbs,
        "fats_g" => $dayFats,
        "target_fats_g" => $targetFats,
        "meals_count" => $totalCount,
        "status" => $status
    ];
}

// -----------------------------------------------------------------------------
// 4. Compute Weekly Summary & Intelligent AI Trend Insight
// -----------------------------------------------------------------------------
$activeDivisor = max(1, $daysLogged);
$weeklyAvgCal = $daysLogged > 0 ? (int)round($weeklyTotalCal / $activeDivisor) : 0;
$weeklyAvgProt = $daysLogged > 0 ? round($weeklyTotalProt / $activeDivisor, 1) : 0.0;
$weeklyAvgCarbs = $daysLogged > 0 ? round($weeklyTotalCarbs / $activeDivisor, 1) : 0.0;
$weeklyAvgFats = $daysLogged > 0 ? round($weeklyTotalFats / $activeDivisor, 1) : 0.0;
$adherencePct = (int)round(($daysOnTarget / 7.0) * 100);

if ($daysLogged <= 0) {
    $aiInsight = "No meals logged yet across the past 7 days. Start logging breakfast, lunch, or post-workout meals to analyze your weekly macro adherence!";
} elseif ($daysOnTarget >= 5) {
    $aiInsight = "Phenomenal discipline! You hit your nutrition target on {$daysOnTarget} of 7 days with an average intake of {$weeklyAvgCal} kcal/day. Perfect macro consistency.";
} elseif ($daysOnTarget >= 3) {
    $aiInsight = "Strong week! {$daysOnTarget}/7 days on target with {$weeklyAvgProt}g avg daily protein. Keep evening snacks balanced to stay right in your optimal calorie zone.";
} elseif ($weeklyAvgCal < $targetCalories) {
    $aiInsight = "Logged {$daysLogged} days this week averaging {$weeklyAvgCal} kcal/day (below your {$targetCalories} kcal target). Add a nutrient-dense post-workout shake or complex carbs.";
} else {
    $aiInsight = "Logged {$daysLogged} days this week averaging {$weeklyAvgCal} kcal/day. Aim for lean proteins and high-fiber veggies to align smoothly with your daily goal.";
}

$summary = [
    "weekly_total_calories" => $weeklyTotalCal,
    "weekly_avg_calories" => $weeklyAvgCal,
    "weekly_total_protein" => round($weeklyTotalProt, 1),
    "weekly_avg_protein" => $weeklyAvgProt,
    "weekly_avg_carbs" => $weeklyAvgCarbs,
    "weekly_avg_fats" => $weeklyAvgFats,
    "days_logged" => $daysLogged,
    "days_on_target" => $daysOnTarget,
    "adherence_percentage" => $adherencePct,
    "target_calories" => $targetCalories,
    "target_protein_g" => $targetProtein,
    "target_carbs_g" => $targetCarbs,
    "target_fats_g" => $targetFats,
    "ai_trend_insight" => $aiInsight
];

respondJson(200, [
    "success" => true,
    "start_date" => $startDateStr,
    "end_date" => $endDateStr,
    "summary" => $summary,
    "days" => $days
]);
