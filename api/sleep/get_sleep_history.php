<?php

require_once __DIR__ . "/../../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    respondJson(405, [
        "status" => "error",
        "message" => "Method not allowed. Use GET.",
        "data" => null
    ]);
}

$limit = isset($_GET["limit"]) ? max(1, min(60, (int)$_GET["limit"])) : 14;
$todayDate = date("Y-m-d");

function resolveHistorySleepMeta(int $durationMinutes, int $explicitScore = 0): array {
    if ($durationMinutes <= 0) {
        return ["No Log", 0, "none"];
    }
    $hrs = $durationMinutes / 60.0;
    if ($hrs < 6.0) {
        $label = "Poor";
        $score = max(40, min(55, (int)round(40 + ($hrs / 6.0) * 15)));
        $statusColor = "critical";
    } elseif ($hrs < 7.0) {
        $label = "Fair";
        $score = max(60, min(74, (int)round(60 + ($hrs - 6.0) * 14)));
        $statusColor = "warning";
    } elseif ($hrs <= 8.5) {
        $label = "Optimal";
        $score = max(75, min(90, (int)round(75 + (($hrs - 7.0) / 1.5) * 15)));
        $statusColor = "optimal";
    } else {
        $label = "Excellent";
        $score = max(90, min(100, (int)round(90 + min(1.0, ($hrs - 8.5) / 1.5) * 10)));
        $statusColor = "optimal";
    }
    return [$label, ($explicitScore > 0 ? $explicitScore : $score), $statusColor];
}

// 1. Fetch today's record or latest
$stmtToday = $conn->prepare("SELECT id, bedtime, wake_time, duration_minutes, COALESCE(quality_score, 0) AS quality_score, status_color, COALESCE(logged_date, logged_at, DATE(start_time)) AS logged_date, created_at FROM sleep_logs WHERE user_id = ? AND (logged_date = ? OR logged_at = ?) ORDER BY id DESC LIMIT 1");
$stmtToday->bind_param("iss", $userId, $todayDate, $todayDate);
$stmtToday->execute();
$todayRow = $stmtToday->get_result()->fetch_assoc();
$stmtToday->close();

$todayData = null;
if ($todayRow) {
    $dur = (int)$todayRow["duration_minutes"];
    $h = intdiv($dur, 60);
    $m = $dur % 60;
    [$todayQuality, $todayScore, $todayColor] = resolveHistorySleepMeta($dur, (int)$todayRow["quality_score"]);
    $todayData = [
        "id" => (int)$todayRow["id"],
        "bedtime" => $todayRow["bedtime"],
        "wake_time" => $todayRow["wake_time"],
        "duration_minutes" => $dur,
        "duration_formatted" => "{$h}h {$m}m",
        "sleep_score" => $todayScore,
        "quality" => $todayQuality,
        "quality_label" => $todayQuality,
        "status_color" => $todayColor,
        "logged_date" => $todayRow["logged_date"],
        "created_at" => $todayRow["created_at"]
    ];
}

// 2. Fetch recent sleep logs
$stmtHistory = $conn->prepare("SELECT id, bedtime, wake_time, duration_minutes, COALESCE(quality_score, 0) AS quality_score, status_color, COALESCE(logged_date, logged_at, DATE(start_time)) AS logged_date, created_at FROM sleep_logs WHERE user_id = ? ORDER BY COALESCE(logged_date, logged_at, DATE(start_time)) DESC LIMIT ?");
$stmtHistory->bind_param("ii", $userId, $limit);
$stmtHistory->execute();
$result = $stmtHistory->get_result();

$history = [];
$totalDuration = 0;
$optimalCount = 0;

while ($row = $result->fetch_assoc()) {
    $dur = (int)$row["duration_minutes"];
    $h = intdiv($dur, 60);
    $m = $dur % 60;
    [$qLabel, $qScore, $status] = resolveHistorySleepMeta($dur, (int)$row["quality_score"]);
    if ($status === "optimal") {
        $optimalCount++;
    }
    $totalDuration += $dur;

    $history[] = [
        "id" => (int)$row["id"],
        "bedtime" => $row["bedtime"],
        "wake_time" => $row["wake_time"],
        "duration_minutes" => $dur,
        "duration_formatted" => "{$h}h {$m}m",
        "sleep_score" => $qScore,
        "quality" => $qLabel,
        "quality_label" => $qLabel,
        "status_color" => $status,
        "logged_date" => $row["logged_date"],
        "created_at" => $row["created_at"]
    ];
}
$stmtHistory->close();

$historyCount = count($history);
$avgDuration = $historyCount > 0 ? (int)round($totalDuration / $historyCount) : 0;
$avgH = intdiv($avgDuration, 60);
$avgM = $avgDuration % 60;
$optimalPct = $historyCount > 0 ? (int)round(($optimalCount / $historyCount) * 100) : 0;

respondJson(200, [
    "status" => "success",
    "message" => "Sleep history retrieved successfully",
    "data" => [
        "user_id" => $userId,
        "today" => $todayData,
        "history" => $history,
        "total_records" => $historyCount,
        "average_duration_minutes" => $avgDuration,
        "average_duration_formatted" => "{$avgH}h {$avgM}m",
        "optimal_sleep_percentage" => $optimalPct
    ]
]);

