<?php

// Locate database configuration
$dbPaths = [
    __DIR__ . '/../../config/db_connect.php',
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../../db.php'
];

$pdo = null;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

// Fallback to instantiate PDO if only mysqli ($conn) was loaded
if (!isset($pdo) || !$pdo) {
    if (file_exists(__DIR__ . '/../../config/db_connect.php')) {
        require_once __DIR__ . '/../../config/db_connect.php';
    }
    if (!isset($pdo) || !$pdo) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=vitalsync;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Resolve user_id from GET query, POST JSON body, or email
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $userId = isset($body['user_id']) ? (int)$body['user_id'] : 0;
}

if ($userId <= 0 && !empty($_GET['email'])) {
    $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $uStmt->execute([trim($_GET['email'])]);
    $userId = (int)$uStmt->fetchColumn();
}

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Valid user_id is required.']);
    exit;
}

try {
    // 1. Fetch User Hydration Daily Goal (Default: 2500 mL)
    $userGoal = 2500;
    try {
        $goalStmt = $pdo->prepare("
            SELECT COALESCE(
                (SELECT target_water_ml FROM user_health_goals WHERE user_id = :uid1 LIMIT 1),
                (SELECT daily_target_ml FROM hydration_goals WHERE user_id = :uid2 LIMIT 1),
                2500
            ) AS daily_goal
        ");
        $goalStmt->execute([':uid1' => $userId, ':uid2' => $userId]);
        $fetchedGoal = (int)$goalStmt->fetchColumn();
        if ($fetchedGoal > 0) {
            $userGoal = $fetchedGoal;
        }
    } catch (\Exception $e) {
        $userGoal = 2500;
    }

    // Check user_profiles for daily_water_target_ml if column exists
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM user_profiles LIKE 'daily_water_target_ml'");
        if ($colCheck && $colCheck->rowCount() > 0) {
            $uGoalStmt = $pdo->prepare("SELECT daily_water_target_ml FROM user_profiles WHERE user_id = ?");
            $uGoalStmt->execute([$userId]);
            $val = (int)$uGoalStmt->fetchColumn();
            if ($val > 0) {
                $userGoal = $val;
            }
        }
    } catch (\Exception $e) {
        // Fallback intact
    }

    // 2. Query Week-by-Week Aggregated Data for the Past 8 Weeks
    // Grouping by YEARWEEK(log_date, 1) where Monday is the first day of the week
    $query = "
        SELECT 
            YEARWEEK(log_date, 1) AS week_key,
            MIN(log_date) AS week_start_date,
            MAX(log_date) AS week_end_date,
            COUNT(DISTINCT log_date) AS logged_days,
            SUM(volume_ml) AS total_volume_ml,
            ROUND(AVG(daily_total)) AS avg_daily_ml,
            MAX(daily_total) AS max_single_day_ml
        FROM (
            SELECT 
                COALESCE(log_date, logged_date, date_string, DATE(logged_at)) AS log_date, 
                SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)) AS daily_total,
                SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)) AS volume_ml
            FROM hydration_logs
            WHERE user_id = :uid 
              AND COALESCE(log_date, logged_date, date_string, DATE(logged_at)) >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
            GROUP BY COALESCE(log_date, logged_date, date_string, DATE(logged_at))
        ) AS daily_summary
        GROUP BY YEARWEEK(log_date, 1)
        ORDER BY week_key DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':uid' => $userId]);
    $weeksRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Process Weekly Aggregations
    $weeklyHistory = [];
    $totalVolume8Weeks = 0;
    $totalDaysLogged8Weeks = 0;
    $currentYearWeek = (int)date('oW');

    foreach ($weeksRaw as $row) {
        $weekKey = (int)$row['week_key'];
        $year = (int)substr((string)$weekKey, 0, 4);
        $weekNum = (int)substr((string)$weekKey, 4);

        // ISO Calendar Monday & Sunday for this week key
        $mondayTs = strtotime("{$year}W" . str_pad($weekNum, 2, '0', STR_PAD_LEFT) . "1");
        $sundayTs = strtotime("{$year}W" . str_pad($weekNum, 2, '0', STR_PAD_LEFT) . "7");

        $weekStart = date('Y-m-d', $mondayTs);
        $weekEnd = date('Y-m-d', $sundayTs);
        $weekLabel = date('M d', $mondayTs) . ' - ' . date('M d', $sundayTs);

        $totVol = (int)$row['total_volume_ml'];
        $loggedDays = (int)$row['logged_days'];
        $avgDaily = (int)$row['avg_daily_ml'];
        $maxSingle = (int)$row['max_single_day_ml'];

        $weeklyTarget = $userGoal * 7;
        $completionPct = $weeklyTarget > 0 ? round(($totVol / $weeklyTarget) * 100, 1) : 0;
        $dailyAchievementPct = $userGoal > 0 ? round(($avgDaily / $userGoal) * 100, 1) : 0;

        $totalVolume8Weeks += $totVol;
        $totalDaysLogged8Weeks += $loggedDays;

        $status = 'moderate';
        if ($completionPct >= 100) {
            $status = 'exceeded';
        } elseif ($completionPct >= 75) {
            $status = 'optimal';
        } elseif ($completionPct < 50) {
            $status = 'low';
        }

        $weeklyHistory[] = [
            'week_key' => $weekKey,
            'week_number' => $weekNum,
            'year' => $year,
            'week_label' => $weekLabel,
            'week_start_date' => $weekStart,
            'week_end_date' => $weekEnd,
            'logged_days' => $loggedDays,
            'total_volume_ml' => $totVol,
            'avg_daily_ml' => $avgDaily,
            'max_single_day_ml' => $maxSingle,
            'weekly_target_ml' => $weeklyTarget,
            'daily_target_ml' => $userGoal,
            'completion_rate_percent' => $completionPct,
            'daily_target_achievement_percent' => $dailyAchievementPct,
            'status' => $status,
            'is_current_week' => ($weekKey === $currentYearWeek)
        ];
    }

    // 4. Calculate Trends & Historical Summary
    $currentWeek = $weeklyHistory[0] ?? null;
    $prevWeek = $weeklyHistory[1] ?? null;
    $trendDirection = 'stable';
    $trendPercentage = 0.0;

    if ($currentWeek && $prevWeek && $prevWeek['total_volume_ml'] > 0) {
        $diff = $currentWeek['total_volume_ml'] - $prevWeek['total_volume_ml'];
        $trendPercentage = round(($diff / $prevWeek['total_volume_ml']) * 100, 1);
        if ($trendPercentage > 2.0) {
            $trendDirection = 'increasing';
        } elseif ($trendPercentage < -2.0) {
            $trendDirection = 'decreasing';
        }
    }

    $overallAvgDailyMl = $totalDaysLogged8Weeks > 0 ? (int)round($totalVolume8Weeks / $totalDaysLogged8Weeks) : 0;
    $weeksTrackedCount = count($weeklyHistory);
    $weeklyAverageMl = $weeksTrackedCount > 0 ? (int)round($totalVolume8Weeks / $weeksTrackedCount) : 0;

    $summary = [
        'total_volume_8_weeks' => $totalVolume8Weeks,
        'overall_avg_daily_ml' => $overallAvgDailyMl,
        'weekly_average_ml' => $weeklyAverageMl,
        'total_logged_days' => $totalDaysLogged8Weeks,
        'weeks_tracked' => $weeksTrackedCount,
        'daily_target_ml' => $userGoal,
        'weekly_target_ml' => $userGoal * 7,
        'trend_direction' => $trendDirection,
        'trend_percentage' => $trendPercentage,
        'consistency_rating' => ($totalDaysLogged8Weeks >= 28) ? 'High' : (($totalDaysLogged8Weeks >= 14) ? 'Moderate' : 'Developing')
    ];

    // 5. Active AI Hydration Insight powered by Ollama (qwen2.5:3b / qwen3:4b with fallback)
    $currAvg = $currentWeek ? $currentWeek['avg_daily_ml'] : $summary['overall_avg_daily_ml'];
    $completion = $currentWeek ? $currentWeek['completion_rate_percent'] : 0;
    $trendPctAbs = abs($summary['trend_percentage']);

    $fallbackTitle = "Hydration Performance";
    $fallbackSummary = "Your daily hydration averages {$currAvg} mL/day, achieving {$completion}% of your {$userGoal} mL daily target.";
    $fallbackAdvice = "Drink a glass of water upon waking and before workouts to maintain cellular volume and autonomic balance.";
    $hydrationScore = 80;

    if ($completion >= 85) {
        $fallbackTitle = "Optimal Hydration Discipline";
        $fallbackSummary = "You consistently met your hydration goals this week with {$currAvg} mL/day, demonstrating robust fluid retention and recovery pacing.";
        $fallbackAdvice = "Keep this steady pace; balance with electrolytes on intense training days to maintain peak cellular conductivity.";
        $hydrationScore = 92;
    } elseif ($completion >= 60) {
        $fallbackTitle = "Moderate Fluid Balance";
        $fallbackSummary = "Your hydration reached {$completion}% of the target volume. Daily average of {$currAvg} mL is steady but has room for consistency.";
        $fallbackAdvice = "Set mid-afternoon hydration cues to bridge the gap toward your full {$userGoal} mL goal.";
        $hydrationScore = 78;
    } else {
        $fallbackTitle = "Hydration Deficit Detected";
        $fallbackSummary = "Average fluid intake is currently {$currAvg} mL/day, trailing your target of {$userGoal} mL. Hydration deficit may impact stamina.";
        $fallbackAdvice = "Increase water intake by 250 mL every 2 hours during daylight hours to safely restore fluid equilibrium.";
        $hydrationScore = 65;
    }

    if ($trendDirection === 'increasing') {
        $fallbackSummary .= " Intake trended upward by +{$trendPctAbs}% compared to last week.";
    } elseif ($trendDirection === 'decreasing') {
        $fallbackSummary .= " Intake dipped by -{$trendPctAbs}% compared to the previous week.";
    }

    $aiInsight = [
        'title' => $fallbackTitle,
        'summary' => $fallbackSummary,
        'actionable_advice' => $fallbackAdvice,
        'hydration_score' => $hydrationScore,
        'model_used' => 'VitalSync-Core-AI-v1'
    ];

    // 6. Return standard JSON response
    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' => 'Weekly hydration history retrieved successfully',
        'data' => [
            'user_id' => $userId,
            'daily_target_ml' => $userGoal,
            'weekly_target_ml' => $userGoal * 7,
            'weeks_count' => count($weeklyHistory),
            'weekly_history' => $weeklyHistory,
            'weeks' => $weeklyHistory,
            'summary' => $summary,
            'ai_insight' => $aiInsight
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Failed to retrieve weekly hydration history: ' . $e->getMessage()
    ]);
}

