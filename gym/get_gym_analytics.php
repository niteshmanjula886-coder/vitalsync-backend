<?php

header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

$conn->query("
    CREATE TABLE IF NOT EXISTS gym_workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        split_name VARCHAR(100) NOT NULL,
        duration_minutes INT NOT NULL DEFAULT 0,
        total_volume_kg FLOAT NOT NULL DEFAULT 0,
        total_sets INT NOT NULL DEFAULT 0,
        calories_burned INT NOT NULL DEFAULT 0,
        exercises_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

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
// 1. Resolve Target Date (Day-to-Day View)
// -----------------------------------------------------------------------------
$todayStr = date("Y-m-d");
$selectedDate = isset($_GET["date"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET["date"]))
    ? trim($_GET["date"])
    : $todayStr;

$isToday = ($selectedDate === $todayStr);
$selectedDateFormatted = $isToday
    ? "Today (" . date("M d, Y") . ")"
    : date("D, M d, Y", strtotime($selectedDate));

// -----------------------------------------------------------------------------
// 2. Selected Day Workouts Telemetry
// -----------------------------------------------------------------------------
$dayWStmt = $conn->prepare("
    SELECT 
        COUNT(id) AS workout_count,
        COALESCE(SUM(total_volume_kg), 0) AS volume_kg,
        COALESCE(SUM(total_sets), 0) AS total_sets,
        COALESCE(SUM(duration_minutes), 0) AS total_duration,
        COALESCE(SUM(calories_burned), 0) AS calories_burned
    FROM gym_workouts
    WHERE user_id = ? AND DATE(created_at) = ?
");
$dayWStmt->bind_param("is", $userId, $selectedDate);
$dayWStmt->execute();
$dayWRow = $dayWStmt->get_result()->fetch_assoc();
$dayWStmt->close();

$dailyWorkoutsCount = (int)($dayWRow["workout_count"] ?? 0);
$dailyVolumeKg = (float)($dayWRow["volume_kg"] ?? 0.0);
$dailySets = (int)($dayWRow["total_sets"] ?? 0);
$dailyDurationMins = (int)($dayWRow["total_duration"] ?? 0);
$dailyCaloriesBurned = (int)($dayWRow["calories_burned"] ?? 0);

// Fetch workouts list and distinct splits for selected date
$dayListStmt = $conn->prepare("
    SELECT id, split_name, duration_minutes, total_volume_kg, total_sets, calories_burned, exercises_json, created_at
    FROM gym_workouts
    WHERE user_id = ? AND DATE(created_at) = ?
    ORDER BY created_at DESC, id DESC
");
$dayListStmt->bind_param("is", $userId, $selectedDate);
$dayListStmt->execute();
$dayListRes = $dayListStmt->get_result();

$dailyWorkoutsList = [];
$splitsTrainedMap = [];
while ($dr = $dayListRes->fetch_assoc()) {
    $sName = trim($dr["split_name"]);
    $splitsTrainedMap[$sName] = true;
    $exercises = json_decode($dr["exercises_json"] ?? "[]", true) ?: [];

    $dailyWorkoutsList[] = [
        "id" => (string)$dr["id"],
        "name" => $sName . " Session",
        "split" => $sName,
        "durationMinutes" => (int)$dr["duration_minutes"],
        "totalVolumeKg" => (float)$dr["total_volume_kg"],
        "totalSets" => (int)$dr["total_sets"],
        "estimatedCaloriesBurned" => (int)$dr["calories_burned"],
        "dateString" => $selectedDate,
        "timestampFormatted" => date("h:i A", strtotime($dr["created_at"])),
        "exercises" => $exercises,
        "created_at" => $dr["created_at"]
    ];
}
$dayListStmt->close();

$dailySplitsTrained = !empty($splitsTrainedMap) ? implode(", ", array_keys($splitsTrainedMap)) : "Rest Day";

// -----------------------------------------------------------------------------
// 3. Selected Day Nutrition Telemetry
// -----------------------------------------------------------------------------
$dayNStmt = $conn->prepare("
    SELECT 
        COUNT(id) AS meals_count,
        COALESCE(SUM(calories), 0) AS total_cal,
        COALESCE(SUM(protein_g), 0) AS total_prot,
        COALESCE(SUM(carbs_g), 0) AS total_carbs,
        COALESCE(SUM(fats_g), 0) AS total_fats
    FROM gym_nutrition_logs
    WHERE user_id = ? AND DATE(created_at) = ?
");
$dayNStmt->bind_param("is", $userId, $selectedDate);
$dayNStmt->execute();
$dayNRow = $dayNStmt->get_result()->fetch_assoc();
$dayNStmt->close();

$dailyCaloriesIn = (int)($dayNRow["total_cal"] ?? 0);
$dailyProteinG = (float)($dayNRow["total_prot"] ?? 0.0);
$dailyCarbsG = (float)($dayNRow["total_carbs"] ?? 0.0);
$dailyFatsG = (float)($dayNRow["total_fats"] ?? 0.0);
$dailyMealsCount = (int)($dayNRow["meals_count"] ?? 0);

// Net Energy Balance for selected day: Intake kcal - Burned kcal
$dailyNetEnergy = $dailyCaloriesIn - $dailyCaloriesBurned;

// -----------------------------------------------------------------------------
// 4. Calculate Gym Streak (consecutive active workout days)
// -----------------------------------------------------------------------------
$streakStmt = $conn->prepare("
    SELECT DISTINCT DATE(created_at) AS workout_date
    FROM gym_workouts
    WHERE user_id = ? AND created_at >= CURDATE() - INTERVAL 30 DAY
    ORDER BY workout_date DESC
");
$streakStmt->bind_param("i", $userId);
$streakStmt->execute();
$sRes = $streakStmt->get_result();
$streakDates = [];
while ($sr = $sRes->fetch_assoc()) {
    $streakDates[] = $sr["workout_date"];
}
$streakStmt->close();

$currentStreak = 0;
$checkDate = new DateTime();
$yesterdayStr = (clone $checkDate)->modify("-1 day")->format("Y-m-d");

$hasToday = in_array($todayStr, $streakDates);
$hasYesterday = in_array($yesterdayStr, $streakDates);

if ($hasToday || $hasYesterday) {
    $cur = $hasToday ? $checkDate : (clone $checkDate)->modify("-1 day");
    while (true) {
        $curStr = $cur->format("Y-m-d");
        if (in_array($curStr, $streakDates)) {
            $currentStreak++;
            $cur->modify("-1 day");
        } else {
            break;
        }
    }
}

// -----------------------------------------------------------------------------
// 5. Day-by-Day Comparison (Last 7 Calendar Days)
// -----------------------------------------------------------------------------
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = new DateTime();
    $d->modify("-{$i} days");
    $dStr = $d->format("Y-m-d");
    $dayLabel = $d->format("D");
    $dateLabel = $d->format("M d");

    // Workouts on day
    $dW = $conn->prepare("
        SELECT 
            COUNT(id) AS cnt,
            COALESCE(SUM(total_volume_kg), 0) AS vol,
            COALESCE(SUM(calories_burned), 0) AS cal_burn,
            GROUP_CONCAT(DISTINCT split_name SEPARATOR ', ') AS splits
        FROM gym_workouts
        WHERE user_id = ? AND DATE(created_at) = ?
    ");
    $dW->bind_param("is", $userId, $dStr);
    $dW->execute();
    $rw = $dW->get_result()->fetch_assoc();
    $dW->close();

    // Nutrition on day
    $dN = $conn->prepare("
        SELECT 
            COALESCE(SUM(calories), 0) AS cal_in,
            COALESCE(SUM(protein_g), 0) AS prot
        FROM gym_nutrition_logs
        WHERE user_id = ? AND DATE(created_at) = ?
    ");
    $dN->bind_param("is", $userId, $dStr);
    $dN->execute();
    $rn = $dN->get_result()->fetch_assoc();
    $dN->close();

    $dayCnt = (int)($rw["cnt"] ?? 0);
    $dayVol = (float)($rw["vol"] ?? 0.0);
    $dayBurn = (int)($rw["cal_burn"] ?? 0);
    $daySplits = !empty($rw["splits"]) ? $rw["splits"] : ($dayCnt > 0 ? "Workout" : "Rest");
    $dayCalIn = (int)($rn["cal_in"] ?? 0);
    $dayProt = (float)($rn["prot"] ?? 0.0);

    $last7Days[] = [
        "date" => $dStr,
        "day_label" => $dayLabel,
        "date_formatted" => $dateLabel,
        "workouts_count" => $dayCnt,
        "splits_trained" => $daySplits,
        "volume_kg" => round($dayVol, 1),
        "calories_burned" => $dayBurn,
        "calories_in" => $dayCalIn,
        "protein_g" => round($dayProt, 1),
        "net_calories" => ($dayCalIn - $dayBurn),
        "is_selected" => ($dStr === $selectedDate),
        "is_today" => ($dStr === $todayStr)
    ];
}

// -----------------------------------------------------------------------------
// 6. Selected Day Split Breakdown
// -----------------------------------------------------------------------------
$splitBreakdownStmt = $conn->prepare("
    SELECT 
        split_name, 
        COUNT(id) AS session_count, 
        COALESCE(SUM(total_volume_kg), 0) AS split_volume,
        COALESCE(SUM(total_sets), 0) AS split_sets,
        COALESCE(SUM(calories_burned), 0) AS split_calories
    FROM gym_workouts
    WHERE user_id = ? AND DATE(created_at) = ?
    GROUP BY split_name
    ORDER BY split_volume DESC
");
$splitBreakdownStmt->bind_param("is", $userId, $selectedDate);
$splitBreakdownStmt->execute();
$sbRes = $splitBreakdownStmt->get_result();

$dailySplitBreakdown = [];
while ($sbr = $sbRes->fetch_assoc()) {
    $dailySplitBreakdown[] = [
        "split_name" => $sbr["split_name"],
        "count" => (int)$sbr["session_count"],
        "volume_kg" => round((float)$sbr["split_volume"], 1),
        "total_sets" => (int)$sbr["split_sets"],
        "calories_burned" => (int)$sbr["split_calories"]
    ];
}
$splitBreakdownStmt->close();

respondJson(200, [
    "success" => true,
    "data" => [
        // Day-to-Day Analytics Fields (Issue 7)
        "selected_date" => $selectedDate,
        "selected_date_formatted" => $selectedDateFormatted,
        "is_today" => $isToday,
        "daily_workouts_count" => $dailyWorkoutsCount,
        "daily_splits_trained" => $dailySplitsTrained,
        "daily_volume_kg" => round($dailyVolumeKg, 1),
        "daily_sets" => $dailySets,
        "daily_duration_mins" => $dailyDurationMins,
        "daily_calories_burned" => $dailyCaloriesBurned,
        "daily_calories_in" => $dailyCaloriesIn,
        "daily_protein_g" => round($dailyProteinG, 1),
        "daily_carbs_g" => round($dailyCarbsG, 1),
        "daily_fats_g" => round($dailyFatsG, 1),
        "daily_net_energy" => $dailyNetEnergy,
        "current_streak_days" => $currentStreak,
        "daily_workouts_list" => $dailyWorkoutsList,
        "daily_split_breakdown" => $dailySplitBreakdown,
        "last_7_days_comparison" => $last7Days,

        // Backward compatibility fields
        "weekly_workouts_completed" => $dailyWorkoutsCount,
        "total_weekly_volume_kg" => round($dailyVolumeKg, 1),
        "avg_session_duration_mins" => $dailyDurationMins,
        "total_weekly_calories_burned" => $dailyCaloriesBurned,
        "avg_daily_calories" => $dailyCaloriesIn,
        "avg_daily_protein" => round($dailyProteinG, 1),
        "net_energy_balance" => $dailyNetEnergy,
        "daily_trends" => $last7Days,
        "recent_workouts" => $dailyWorkoutsList
    ]
]);
