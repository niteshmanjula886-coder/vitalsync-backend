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

$startDateInput = trim($_GET["start_date"] ?? "");
$endDateInput = trim($_GET["end_date"] ?? "");
$today = date("Y-m-d");

if (empty($startDateInput) || empty($endDateInput)) {
    // Rolling 7 days ending today (relative to CURDATE())
    $endDateInput = !empty($endDateInput) ? $endDateInput : $today;
    $startDateInput = date("Y-m-d", strtotime($endDateInput . " - 6 days"));
}

// Fetch user's target ml
$targetMl = 2000;
$tStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
if ($tStmt) {
    $tStmt->bind_param("i", $userId);
    $tStmt->execute();
    $tRow = $tStmt->get_result()->fetch_assoc();
    if ($tRow && !empty($tRow["daily_target_ml"])) {
        $targetMl = (int)$tRow["daily_target_ml"];
    }
    $tStmt->close();
}

// Query logged hydration across rolling 7 days
$stmt = $conn->prepare(
    "SELECT COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at)) as l_date, 
            SUM(COALESCE(volume_ml, intake_ml, amount_ml, 0)) as total_intake,
            MAX(target_ml) as day_target
     FROM hydration_logs 
     WHERE user_id = ?
       AND COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at)) BETWEEN ? AND ?
     GROUP BY COALESCE(log_date, logged_date, date_string, DATE(logged_at), DATE(created_at))"
);
$stmt->bind_param("iss", $userId, $startDateInput, $endDateInput);
$stmt->execute();
$result = $stmt->get_result();

$loggedByDate = [];
while ($row = $result->fetch_assoc()) {
    $d = $row["l_date"];
    if (!empty($d)) {
        $loggedByDate[$d] = [
            "intake_ml" => (int)$row["total_intake"],
            "target_ml" => !empty($row["day_target"]) ? (int)$row["day_target"] : $targetMl
        ];
    }
}
$stmt->close();

// Build continuous rolling 7-day array ending today
$days = [];
$totalRecordedMl = 0;
$loggedDaysCount = 0;

for ($i = 0; $i < 7; $i++) {
    $currentTs = strtotime($startDateInput . " + $i days");
    $dStr = date("Y-m-d", $currentTs);
    $dayName = date("D", $currentTs);
    $dayLabel = substr($dayName, 0, 1);
    $isToday = ($dStr === $today);

    if (isset($loggedByDate[$dStr]) && $loggedByDate[$dStr]["intake_ml"] > 0) {
        $intake = $loggedByDate[$dStr]["intake_ml"];
        $dayTarget = $loggedByDate[$dStr]["target_ml"];
        $progress = $dayTarget > 0 ? min(100, (int)round(($intake / $dayTarget) * 100)) : 0;
        $ratio = $dayTarget > 0 ? min(1.0, round($intake / $dayTarget, 2)) : 0.0;

        $days[] = [
            "date" => $dStr,
            "date_formatted" => date("M d", $currentTs),
            "day" => $dayLabel,
            "day_name" => $dayName,
            "intake_ml" => $intake,
            "consumed_ml" => $intake,
            "target_ml" => $dayTarget,
            "ratio" => $ratio,
            "progress_percent" => $progress,
            "has_data" => true,
            "is_today" => $isToday
        ];
        $totalRecordedMl += $intake;
        $loggedDaysCount++;
    } else {
        // Return 0 ml explicitly for unlogged days so rolling volume charts stay complete
        $days[] = [
            "date" => $dStr,
            "date_formatted" => date("M d", $currentTs),
            "day" => $dayLabel,
            "day_name" => $dayName,
            "intake_ml" => 0,
            "consumed_ml" => 0,
            "target_ml" => $targetMl,
            "ratio" => 0.0,
            "progress_percent" => 0,
            "has_data" => false,
            "is_today" => $isToday
        ];
    }
}

$avgRecordedMl = $loggedDaysCount > 0 ? (int)round($totalRecordedMl / $loggedDaysCount) : 0;

respondJson(200, [
    "status" => "success",
    "message" => "Weekly hydration retrieved successfully",
    "data" => [
        "user_id" => $userId,
        "start_date" => $startDateInput,
        "end_date" => $endDateInput,
        "target_ml" => $targetMl,
        "days" => $days,
        "total_recorded_ml" => $totalRecordedMl,
        "average_recorded_ml" => $avgRecordedMl,
        "logged_days_count" => $loggedDaysCount
    ]
]);

