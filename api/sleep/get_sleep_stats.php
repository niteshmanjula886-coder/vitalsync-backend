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

// Function to determine standardized color metadata based on duration (minutes) and quality
function getSleepQualityMetadata($durationMinutes, $qualityScore = null) {
    $hours = $durationMinutes / 60.0;

    if ($durationMinutes <= 0) {
        return [
            'status' => 'No Log',
            'color_hex' => '#9CA3AF',
            'badge_bg_hex' => '#F3F4F6',
            'quality_label' => 'No Log',
            'medical_meaning' => 'No sleep session logged for this date'
        ];
    } elseif ($hours > 8.5) {
        return [
            'status' => 'Excellent',
            'color_hex' => '#7E57C2',
            'badge_bg_hex' => '#EDE7F6',
            'quality_label' => 'Excellent',
            'medical_meaning' => 'Excellent deep restorative sleep recovery'
        ];
    } elseif ($hours >= 7.0) {
        return [
            'status' => 'Optimal',
            'color_hex' => '#4CAF50',
            'badge_bg_hex' => '#E8F5E9',
            'quality_label' => 'Optimal',
            'medical_meaning' => 'Optimal recovery, restorative sleep'
        ];
    } elseif ($hours >= 6.0) {
        return [
            'status' => 'Fair',
            'color_hex' => '#FFC107',
            'badge_bg_hex' => '#FFF8E1',
            'quality_label' => 'Fair',
            'medical_meaning' => 'Mild deficit, acceptable baseline'
        ];
    } else {
        return [
            'status' => 'Poor',
            'color_hex' => '#FF5252',
            'badge_bg_hex' => '#FFEBEE',
            'quality_label' => 'Poor',
            'medical_meaning' => 'Critical sleep debt, elevated cortisol'
        ];
    }
}

// Stage breakdown palette map
$sleepStageColors = [
    'deep'   => ['label' => 'Deep Sleep',   'color_hex' => '#3F51B5', 'compose_color' => '0xFF3F51B5', 'medical_meaning' => 'Physical recovery, tissue repair, GH release'],
    'rem'    => ['label' => 'REM Sleep',    'color_hex' => '#7E57C2', 'compose_color' => '0xFF7E57C2', 'medical_meaning' => 'Memory consolidation, cognitive restoration'],
    'light'  => ['label' => 'Light Sleep',  'color_hex' => '#03A9F4', 'compose_color' => '0xFF03A9F4', 'medical_meaning' => 'Baseline physiological rest'],
    'awake'  => ['label' => 'Awake Periods', 'color_hex' => '#FF7043', 'compose_color' => '0xFFFF7043', 'medical_meaning' => 'Micro-arousals, fragmented sleep']
];

// Resolve user_id from query, body, or email
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

$dateInput = trim($_GET['date'] ?? date('Y-m-d'));

try {
    // 1. Fetch Target Sleep Minutes
    $targetSleepMinutes = 480; // 8.0 hrs default
    try {
        $goalStmt = $pdo->prepare("
            SELECT COALESCE(
                (SELECT target_sleep_hours * 60 FROM user_health_goals WHERE user_id = :u1 LIMIT 1),
                (SELECT target_minutes FROM sleep_goals WHERE user_id = :u2 LIMIT 1),
                480
            ) as target_mins
        ");
        $goalStmt->execute([':u1' => $userId, ':u2' => $userId]);
        $val = (int)$goalStmt->fetchColumn();
        if ($val > 0) $targetSleepMinutes = $val;
    } catch (\Exception $e) {
        $targetSleepMinutes = 480;
    }

    // 2. Fetch Sleep Record for requested date or latest
    $stmt = $pdo->prepare("
        SELECT id, date_string, duration_minutes, sleep_score, quality_label, bedtime, wake_time, 
               deep_sleep_minutes, light_sleep_minutes, rem_sleep_minutes, awake_minutes, created_at 
        FROM sleep_records 
        WHERE user_id = :uid AND (date_string = :d OR date_string <= :d2)
        ORDER BY date_string DESC LIMIT 1
    ");
    $stmt->execute([':uid' => $userId, ':d' => $dateInput, ':d2' => $dateInput]);
    $rec = $stmt->fetch();

    $durationMin = $rec ? (int)$rec['duration_minutes'] : 0;
    $qualityScore = $rec ? (int)$rec['sleep_score'] : 0;
    $bedtime = $rec ? $rec['bedtime'] : '';
    $wakeTime = $rec ? $rec['wake_time'] : '';
    $deepMin = $rec ? (int)$rec['deep_sleep_minutes'] : 0;
    $lightMin = $rec ? (int)$rec['light_sleep_minutes'] : 0;
    $remMin = $rec ? (int)$rec['rem_sleep_minutes'] : 0;
    $awakeMin = $rec ? (int)$rec['awake_minutes'] : 0;
    $recordDate = $rec ? $rec['date_string'] : $dateInput;

    // Fallback to sleep_logs if sleep_records empty
    if ($durationMin <= 0) {
        $lStmt = $pdo->prepare("
            SELECT id, duration_minutes, quality_score, bedtime, wake_time, start_time, end_time, logged_at 
            FROM sleep_logs 
            WHERE user_id = :uid AND (logged_at = :d OR logged_at <= :d2 OR logged_date = :d3 OR logged_date <= :d4)
            ORDER BY COALESCE(logged_at, logged_date, DATE(start_time)) DESC LIMIT 1
        ");
        $lStmt->execute([':uid' => $userId, ':d' => $dateInput, ':d2' => $dateInput, ':d3' => $dateInput, ':d4' => $dateInput]);
        $lRec = $lStmt->fetch();
        if ($lRec && (int)$lRec['duration_minutes'] > 0) {
            $durationMin = (int)$lRec['duration_minutes'];
            $hoursVal = $durationMin / 60.0;
            $derivedScore = ($hoursVal < 6.0)
                ? max(40, min(55, (int)round(40 + ($hoursVal / 6.0) * 15)))
                : (($hoursVal < 7.0)
                    ? max(60, min(74, (int)round(60 + ($hoursVal - 6.0) * 14)))
                    : (($hoursVal <= 8.5)
                        ? max(75, min(90, (int)round(75 + (($hoursVal - 7.0) / 1.5) * 15)))
                        : max(90, min(100, (int)round(90 + min(1.0, ($hoursVal - 8.5) / 1.5) * 10)))));
            $qualityScore = ($lRec['quality_score'] !== null && (int)$lRec['quality_score'] > 0) ? (int)$lRec['quality_score'] : $derivedScore;
            $bedtime = !empty($lRec['bedtime']) ? $lRec['bedtime'] : ($lRec['start_time'] ? date('h:i A', strtotime($lRec['start_time'])) : '10:30 PM');
            $wakeTime = !empty($lRec['wake_time']) ? $lRec['wake_time'] : ($lRec['end_time'] ? date('h:i A', strtotime($lRec['end_time'])) : '06:30 AM');
            $recordDate = $lRec['logged_at'] ?: date('Y-m-d');
            $deepMin = (int)round($durationMin * 0.22);
            $lightMin = (int)round($durationMin * 0.52);
            $remMin = (int)round($durationMin * 0.21);
            $awakeMin = (int)round($durationMin * 0.05);
        }
    }

    if ($durationMin > 0 && $qualityScore <= 0) {
        $hoursVal = $durationMin / 60.0;
        $qualityScore = ($hoursVal < 6.0)
            ? max(40, min(55, (int)round(40 + ($hoursVal / 6.0) * 15)))
            : (($hoursVal < 7.0)
                ? max(60, min(74, (int)round(60 + ($hoursVal - 6.0) * 14)))
                : (($hoursVal <= 8.5)
                    ? max(75, min(90, (int)round(75 + (($hoursVal - 7.0) / 1.5) * 15)))
                    : max(90, min(100, (int)round(90 + min(1.0, ($hoursVal - 8.5) / 1.5) * 10)))));
    }

    // Determine color metadata for current session
    $qualityMeta = getSleepQualityMetadata($durationMin, $qualityScore);

    $hours = intdiv($durationMin, 60);
    $mins = $durationMin % 60;
    $formattedDuration = "{$hours}h {$mins}m";
    $progressPercent = $targetSleepMinutes > 0 ? min(100, (int)round(($durationMin / $targetSleepMinutes) * 100)) : 0;

    // 3. Structure Stages Breakdown with Color Coding
    $stages = [
        'deep' => [
            'minutes' => $deepMin,
            'percentage' => $durationMin > 0 ? round(($deepMin / $durationMin) * 100, 1) : 0,
            'label' => $sleepStageColors['deep']['label'],
            'color_hex' => $sleepStageColors['deep']['color_hex'],
            'compose_color' => $sleepStageColors['deep']['compose_color'],
            'medical_meaning' => $sleepStageColors['deep']['medical_meaning']
        ],
        'rem' => [
            'minutes' => $remMin,
            'percentage' => $durationMin > 0 ? round(($remMin / $durationMin) * 100, 1) : 0,
            'label' => $sleepStageColors['rem']['label'],
            'color_hex' => $sleepStageColors['rem']['color_hex'],
            'compose_color' => $sleepStageColors['rem']['compose_color'],
            'medical_meaning' => $sleepStageColors['rem']['medical_meaning']
        ],
        'light' => [
            'minutes' => $lightMin,
            'percentage' => $durationMin > 0 ? round(($lightMin / $durationMin) * 100, 1) : 0,
            'label' => $sleepStageColors['light']['label'],
            'color_hex' => $sleepStageColors['light']['color_hex'],
            'compose_color' => $sleepStageColors['light']['compose_color'],
            'medical_meaning' => $sleepStageColors['light']['medical_meaning']
        ],
        'awake' => [
            'minutes' => $awakeMin,
            'percentage' => $durationMin > 0 ? round(($awakeMin / $durationMin) * 100, 1) : 0,
            'label' => $sleepStageColors['awake']['label'],
            'color_hex' => $sleepStageColors['awake']['color_hex'],
            'compose_color' => $sleepStageColors['awake']['compose_color'],
            'medical_meaning' => $sleepStageColors['awake']['medical_meaning']
        ]
    ];

    // 4. Fetch 7-Day History with Dynamic Color Coding per Session
    $hStmt = $pdo->prepare("
        SELECT date_string, duration_minutes, sleep_score, quality_label, bedtime, wake_time,
               deep_sleep_minutes, light_sleep_minutes, rem_sleep_minutes, awake_minutes
        FROM sleep_records 
        WHERE user_id = :uid 
        ORDER BY date_string DESC LIMIT 7
    ");
    $hStmt->execute([':uid' => $userId]);
    $historyRows = $hStmt->fetchAll();

    $history = [];
    $totalHistoryDuration = 0;
    $totalHistoryScore = 0;
    $optimalCount = 0;

    foreach ($historyRows as $hr) {
        $hDur = (int)$hr['duration_minutes'];
        $hScore = (int)$hr['sleep_score'];
        $hMeta = getSleepQualityMetadata($hDur, $hScore);

        if ($hMeta['status'] === 'Optimal' || $hMeta['status'] === 'Excellent') {
            $optimalCount++;
        }
        $totalHistoryDuration += $hDur;
        $totalHistoryScore += $hScore;

        $hHours = intdiv($hDur, 60);
        $hMins = $hDur % 60;

        $history[] = [
            'date' => $hr['date_string'],
            'date_formatted' => date('M d', strtotime($hr['date_string'])),
            'day_name' => date('D', strtotime($hr['date_string'])),
            'duration_minutes' => $hDur,
            'duration_formatted' => "{$hHours}h {$hMins}m",
            'sleep_score' => $hScore,
            'quality_label' => $hMeta['quality_label'],
            'status' => $hMeta['status'],
            'color_hex' => $hMeta['color_hex'],
            'badge_bg_hex' => $hMeta['badge_bg_hex'],
            'medical_meaning' => $hMeta['medical_meaning'],
            'bedtime' => $hr['bedtime'],
            'wake_time' => $hr['wake_time'],
            'deep_sleep_minutes' => (int)$hr['deep_sleep_minutes'],
            'light_sleep_minutes' => (int)$hr['light_sleep_minutes'],
            'rem_sleep_minutes' => (int)$hr['rem_sleep_minutes'],
            'awake_minutes' => (int)$hr['awake_minutes']
        ];
    }

    $historyCount = count($history);
    $avgDuration = $historyCount > 0 ? (int)round($totalHistoryDuration / $historyCount) : 0;
    $avgScore = $historyCount > 0 ? (int)round($totalHistoryScore / $historyCount) : 0;
    $avgH = intdiv($avgDuration, 60);
    $avgM = $avgDuration % 60;
    $overallMeta = getSleepQualityMetadata($avgDuration, $avgScore);

    $summary = [
        'average_duration_minutes' => $avgDuration,
        'average_duration_formatted' => "{$avgH}h {$avgM}m",
        'average_quality_score' => $avgScore,
        'total_logged_days' => $historyCount,
        'optimal_sleep_percentage' => $historyCount > 0 ? (int)round(($optimalCount / $historyCount) * 100) : 0,
        'overall_status' => $overallMeta['status'],
        'overall_color_hex' => $overallMeta['color_hex'],
        'overall_badge_bg_hex' => $overallMeta['badge_bg_hex'],
        'overall_quality_label' => $overallMeta['quality_label'],
        'target_sleep_minutes' => $targetSleepMinutes,
        'target_sleep_formatted' => (intdiv($targetSleepMinutes, 60)) . 'h ' . ($targetSleepMinutes % 60) . 'm'
    ];

    // Thresholds matrix definition for frontend rendering
    $thresholds = [
        'optimal' => ['min_hours' => 7.5, 'max_hours' => 9.5, 'min_quality' => 85, 'color_hex' => '#4CAF50', 'compose_color' => '0xFF4CAF50', 'label' => 'Optimal / Excellent'],
        'moderate' => ['min_hours' => 6.0, 'max_hours' => 7.4, 'min_quality' => 70, 'color_hex' => '#FFC107', 'compose_color' => '0xFFFFC107', 'label' => 'Moderate / Fair'],
        'deficit' => ['max_hours' => 6.0, 'max_quality' => 70, 'color_hex' => '#FF5252', 'compose_color' => '0xFFFF5252', 'label' => 'Deficit / Poor'],
        'excessive' => ['min_hours' => 9.5, 'color_hex' => '#7E57C2', 'compose_color' => '0xFF7E57C2', 'label' => 'Extended / Excessive']
    ];

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' => 'Sleep statistics and color metadata retrieved successfully',
        'data' => [
            'user_id' => $userId,
            'date' => $recordDate,
            'duration_minutes' => $durationMin,
            'duration_formatted' => $formattedDuration,
            'sleep_score' => $qualityScore,
            'quality_label' => $qualityMeta['quality_label'],
            'status' => $qualityMeta['status'],
            'color_hex' => $qualityMeta['color_hex'],
            'badge_bg_hex' => $qualityMeta['badge_bg_hex'],
            'medical_meaning' => $qualityMeta['medical_meaning'],
            'target_sleep_minutes' => $targetSleepMinutes,
            'progress_percent' => $progressPercent,
            'bedtime' => $bedtime,
            'wake_time' => $wakeTime,
            'stages' => $stages,
            'stage_colors' => $sleepStageColors,
            'thresholds' => $thresholds,
            'history' => $history,
            'summary' => $summary
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Failed to fetch sleep stats: ' . $e->getMessage()
    ]);
}

