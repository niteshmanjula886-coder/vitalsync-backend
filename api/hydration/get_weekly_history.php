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

    // 2. Query Rolling 7-Day Blocks Relative to CURDATE() for the Past 8 Rolling Weeks
    $query = "
        SELECT 
            FLOOR(DATEDIFF(CURDATE(), log_date) / 7) AS week_bucket,
            COUNT(DISTINCT log_date) AS logged_days,
            SUM(volume_ml) AS total_volume_ml,
            ROUND(AVG(daily_total)) AS avg_daily_ml,
            MAX(daily_total) AS max_single_day_ml
        FROM (
            SELECT 
                COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at)) AS log_date, 
                SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)) AS daily_total,
                SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)) AS volume_ml
            FROM hydration_logs
            WHERE user_id = :uid 
              AND COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at)) BETWEEN DATE_SUB(CURDATE(), INTERVAL 55 DAY) AND CURDATE()
            GROUP BY COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at))
        ) AS daily_summary
        WHERE log_date IS NOT NULL
        GROUP BY week_bucket
        ORDER BY week_bucket ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':uid' => $userId]);
    $weeksRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Process Rolling 7-Day Aggregations
    $weeklyHistory = [];
    $totalVolume8Weeks = 0;
    $totalDaysLogged8Weeks = 0;

    foreach ($weeksRaw as $row) {
        $bucket = max(0, (int)$row['week_bucket']);
        $endOffset = $bucket * 7;
        $startOffset = $endOffset + 6;

        $startTs = strtotime("-{$startOffset} days");
        $endTs = strtotime("-{$endOffset} days");

        $weekStart = date('Y-m-d', $startTs);
        $weekEnd = date('Y-m-d', $endTs);
        $weekLabel = ($bucket === 0)
            ? 'Last 7 Days (' . date('M d', $startTs) . ' - ' . date('M d', $endTs) . ')'
            : date('M d', $startTs) . ' - ' . date('M d', $endTs);

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
            'week_key' => (int)date('oW', $endTs),
            'week_number' => (int)date('W', $endTs),
            'year' => (int)date('o', $endTs),
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
            'is_current_week' => ($bucket === 0)
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

    // 5. Rolling 7-Day Daily Breakdown (CURDATE()-6 .. CURDATE(), 0 ml for days with 0 intake, direct sum)
    $rolling7Stmt = $pdo->prepare("
        SELECT DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) AS log_day,
               COALESCE(SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)), 0) AS day_ml
        FROM hydration_logs
        WHERE user_id = :uid
          AND DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at))
        ORDER BY log_day ASC
    ");
    $rolling7Stmt->execute([':uid' => $userId]);
    $rollingRows = $rolling7Stmt->fetchAll(PDO::FETCH_ASSOC);
    $rollingMap = [];
    foreach ($rollingRows as $rr) {
        if (!empty($rr['log_day'])) {
            $rollingMap[$rr['log_day']] = (int)$rr['day_ml'];
        }
    }

    $rolling7Days = [];
    $rolling7SumMl = 0;
    $rollingLoggedDays = 0;
    for ($offset = 6; $offset >= 0; $offset--) {
        $dDate = date('Y-m-d', strtotime("-{$offset} days"));
        $dMl = isset($rollingMap[$dDate]) ? max(0, (int)$rollingMap[$dDate]) : 0;
        if ($dMl > 0) $rollingLoggedDays++;
        $rolling7SumMl += $dMl;
        $rolling7Days[] = [
            'date' => $dDate,
            'day' => date('D', strtotime($dDate)),
            'intake_ml' => $dMl,
            'consumed_ml' => $dMl
        ];
    }

    // Keep weeklyHistory[0] total_volume_ml synchronized as direct sum of past 7 rolling days
    if (!empty($weeklyHistory) && !empty($weeklyHistory[0]['is_current_week'])) {
        $weeklyHistory[0]['total_volume_ml'] = $rolling7SumMl;
        $weeklyHistory[0]['logged_days'] = $rollingLoggedDays;
        $weeklyHistory[0]['avg_daily_ml'] = $rollingLoggedDays > 0 ? (int)round($rolling7SumMl / $rollingLoggedDays) : 0;
        $wkTarget = $userGoal * 7;
        $weeklyHistory[0]['completion_rate_percent'] = $wkTarget > 0 ? round(($rolling7SumMl / $wkTarget) * 100, 1) : 0;
    }

    // 6. Dynamic Hydration AI Summary Based on Actual Today's Intake vs Target (with Physiological Consequences)
    $todayIso = date('Y-m-d');
    $todayIntakeMl = isset($rollingMap[$todayIso]) ? (int)$rollingMap[$todayIso] : 0;
    $todayPct = ($userGoal > 0) ? (($todayIntakeMl / $userGoal) * 100.0) : 0.0;
    $remainingMl = max(0, $userGoal - $todayIntakeMl);
    $hydrationScore = min(100, max(0, (int)round($todayPct)));
    $loggedFmt = number_format($todayIntakeMl);
    $targetFmt = number_format($userGoal);
    $remFmt = number_format($remainingMl);

    if ($todayPct < 40.0) {
        $fallbackTitle = "Critically Low Hydration";
        $fallbackSummary = "Water intake is critically low ({$loggedFmt} / {$targetFmt} ml, {$remFmt} ml remaining). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Drink 1\u{2013}2 glasses immediately to restore fluid balance.";
        $fallbackAdvice = "Drink 1\u{2013}2 glasses (250\u{2013}500 ml) now; {$remFmt} ml left to hit your {$targetFmt} ml target.";
    } elseif ($todayPct < 75.0) {
        $fallbackTitle = "Hydration Lagging";
        $fallbackSummary = "Intake is lagging behind your daily goal ({$loggedFmt} / {$targetFmt} ml, {$remFmt} ml remaining). Insufficient hydration can trigger false hunger cues, reduce physical endurance, and elevate resting heart rate. Try keeping a water bottle nearby to sip consistently.";
        $fallbackAdvice = "Sip consistently over the next few hours to clear the remaining {$remFmt} ml.";
    } elseif ($todayPct < 100.0) {
        $fallbackTitle = "Near Hydration Target";
        $fallbackSummary = "You're close to your hydration target ({$loggedFmt} / {$targetFmt} ml)! Just {$remFmt} ml remaining to ensure optimal electrolyte balance and physical performance.";
        $fallbackAdvice = "Only {$remFmt} ml remaining today to hit 100% of your {$targetFmt} ml target.";
    } else {
        $fallbackTitle = "Daily Goal Achieved";
        $fallbackSummary = "Daily hydration goal achieved ({$loggedFmt} / {$targetFmt} ml)! Optimal fluid balance supports muscle recovery and cognitive clarity. Maintain steady intake if working out.";
        $fallbackAdvice = "Maintain steady hydration if exercising or in warm weather.";
    }

    $aiInsight = [
        'title' => $fallbackTitle,
        'summary' => $fallbackSummary,
        'actionable_advice' => $fallbackAdvice,
        'hydration_score' => $hydrationScore,
        'model_used' => 'vitalsync:clinical-engine'
    ];

    // 7. Return standard JSON response
    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' => 'Weekly hydration history retrieved successfully',
        'data' => [
            'user_id' => $userId,
            'daily_target_ml' => $userGoal,
            'weekly_target_ml' => $userGoal * 7,
            'rolling_7_day_total_ml' => $rolling7SumMl,
            'rolling_7_days' => $rolling7Days,
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

