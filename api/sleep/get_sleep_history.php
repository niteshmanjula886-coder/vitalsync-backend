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

// 1. Fetch today's record or latest
$stmtToday = $conn->prepare("SELECT id, bedtime, wake_time, duration_minutes, status_color, logged_date, created_at FROM sleep_logs WHERE user_id = ? AND logged_date = ? LIMIT 1");
$stmtToday->bind_param("is", $userId, $todayDate);
$stmtToday->execute();
$todayRow = $stmtToday->get_result()->fetch_assoc();
$stmtToday->close();

$todayData = null;
if ($todayRow) {
    $dur = (int)$todayRow["duration_minutes"];
    $h = intdiv($dur, 60);
    $m = $dur % 60;
    $todayData = [
        "id" => (int)$todayRow["id"],
        "bedtime" => $todayRow["bedtime"],
        "wake_time" => $todayRow["wake_time"],
        "duration_minutes" => $dur,
        "duration_formatted" => "{$h}h {$m}m",
        "status_color" => $todayRow["status_color"],
        "logged_date" => $todayRow["logged_date"],
        "created_at" => $todayRow["created_at"]
    ];
}

// 2. Fetch recent sleep logs
$stmtHistory = $conn->prepare("SELECT id, bedtime, wake_time, duration_minutes, status_color, logged_date, created_at FROM sleep_logs WHERE user_id = ? ORDER BY logged_date DESC LIMIT ?");
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
    $status = $row["status_color"];
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

