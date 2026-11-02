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

$period = strtolower(trim((string)($_GET["period"] ?? "weekly")));
if (!in_array($period, ["daily", "weekly", "monthly"])) {
    $period = "weekly";
}

$today = date("Y-m-d");

switch ($period) {
    case "daily":
        $curStart = $today;
        $curEnd = $today;
        $prevStart = date("Y-m-d", strtotime("-1 day"));
        $prevEnd = $prevStart;
        break;
    case "monthly":
        $curStart = date("Y-m-d", strtotime("-29 days"));
        $curEnd = $today;
        $prevStart = date("Y-m-d", strtotime("-59 days"));
        $prevEnd = date("Y-m-d", strtotime("-30 days"));
        break;
    case "weekly":
    default:
        $curStart = date("Y-m-d", strtotime("-6 days"));
        $curEnd = $today;
        $prevStart = date("Y-m-d", strtotime("-13 days"));
        $prevEnd = date("Y-m-d", strtotime("-7 days"));
        break;
}

function calculateMetricDelta($current, $previous) {
    if ($current === null && $previous === null) {
        return ["delta_percent" => "0.0%", "direction" => "STABLE"];
    }
    if ($previous === null || $previous <= 0) {
        return ["delta_percent" => ($current > 0 ? "+100%" : "0.0%"), "direction" => ($current > 0 ? "UP" : "STABLE")];
    }
    if ($current === null || $current <= 0) {
        return ["delta_percent" => "-100%", "direction" => "DOWN"];
    }

    $diff = $current - $previous;
    $pct = round(($diff / $previous) * 100, 1);
    $sign = $pct > 0 ? "+" : "";
    $dir = abs($pct) < 1.0 ? "STABLE" : ($pct > 0 ? "UP" : "DOWN");

    return [
        "delta_percent" => "{$sign}{$pct}%",
        "direction" => $dir
    ];
}

// 1. Heart Rate Averages
function getHrAvg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(heart_rate) as avg_hr FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND heart_rate > 0");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_hr"] !== null) {
        return round((float)$row["avg_hr"], 1);
    }
    // Fallback to vitals_readings
    $stmt2 = $conn->prepare("SELECT AVG(value_numeric) as avg_hr FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $stmt2->bind_param("iss", $userId, $start, $end);
    $stmt2->execute();
    $row2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    return ($row2 && $row2["avg_hr"] !== null) ? round((float)$row2["avg_hr"], 1) : null;
}

$curHr = getHrAvg($conn, $userId, $curStart, $curEnd);
$prevHr = getHrAvg($conn, $userId, $prevStart, $prevEnd);
$hrDelta = calculateMetricDelta($curHr, $prevHr);

// 2. SpO2 Averages
function getSpo2Avg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(spo2) as avg_spo2 FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND spo2 > 0");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_spo2"] !== null) {
        return round((float)$row["avg_spo2"], 1);
    }
    $stmt2 = $conn->prepare("SELECT AVG(value_numeric) as avg_spo2 FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $stmt2->bind_param("iss", $userId, $start, $end);
    $stmt2->execute();
    $row2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    return ($row2 && $row2["avg_spo2"] !== null) ? round((float)$row2["avg_spo2"], 1) : null;
}

$curSpo2 = getSpo2Avg($conn, $userId, $curStart, $curEnd);
$prevSpo2 = getSpo2Avg($conn, $userId, $prevStart, $prevEnd);
$spo2Delta = calculateMetricDelta($curSpo2, $prevSpo2);

// 3. Blood Pressure Averages
function getBpAvg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) BETWEEN ? AND ? AND systolic_bp > 0");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_sys"] !== null && $row["avg_dia"] !== null) {
        return [
            "sys" => round((float)$row["avg_sys"], 1),
            "dia" => round((float)$row["avg_dia"], 1)
        ];
    }
    $stmt2 = $conn->prepare("SELECT AVG(value_numeric) as avg_sys, AVG(value_secondary) as avg_dia FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' AND DATE(recorded_at) BETWEEN ? AND ? AND value_numeric > 0");
    $stmt2->bind_param("iss", $userId, $start, $end);
    $stmt2->execute();
    $row2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if ($row2 && $row2["avg_sys"] !== null && $row2["avg_dia"] !== null) {
        return [
            "sys" => round((float)$row2["avg_sys"], 1),
            "dia" => round((float)$row2["avg_dia"], 1)
        ];
    }
    return ["sys" => null, "dia" => null];
}

$curBp = getBpAvg($conn, $userId, $curStart, $curEnd);
$prevBp = getBpAvg($conn, $userId, $prevStart, $prevEnd);
$sysDelta = calculateMetricDelta($curBp["sys"], $prevBp["sys"]);
$diaDelta = calculateMetricDelta($curBp["dia"], $prevBp["dia"]);

// 4. Steps Averages
function getStepsAvg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(step_count) as avg_steps, SUM(step_count) as total_steps FROM steps_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_steps"] !== null) {
        return round((float)$row["avg_steps"]);
    }
    return null;
}

$curSteps = getStepsAvg($conn, $userId, $curStart, $curEnd);
$prevSteps = getStepsAvg($conn, $userId, $prevStart, $prevEnd);
$stepsDelta = calculateMetricDelta($curSteps, $prevSteps);

// 5. Sleep Averages
function getSleepAvg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(duration_minutes) as avg_sleep FROM sleep_logs WHERE user_id = ? AND logged_date BETWEEN ? AND ? AND duration_minutes > 0");
    $stmt->bind_param("iss", $userId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_sleep"] !== null) {
        return (int)round((float)$row["avg_sleep"]);
    }
    return null;
}

$curSleep = getSleepAvg($conn, $userId, $curStart, $curEnd);
$prevSleep = getSleepAvg($conn, $userId, $prevStart, $prevEnd);
$sleepDelta = calculateMetricDelta($curSleep, $prevSleep);

// 6. Hydration Averages
function getHydrationAvg($conn, $userId, $start, $end) {
    $stmt = $conn->prepare("SELECT AVG(COALESCE(intake_ml, amount_ml)) as avg_intake FROM hydration_logs WHERE user_id = ? AND (logged_date BETWEEN ? AND ? OR date_string BETWEEN ? AND ?)");
    $stmt->bind_param("issss", $userId, $start, $end, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && $row["avg_intake"] !== null) {
        return (int)round((float)$row["avg_intake"]);
    }
    return null;
}

$curHyd = getHydrationAvg($conn, $userId, $curStart, $curEnd);
$prevHyd = getHydrationAvg($conn, $userId, $prevStart, $prevEnd);
$hydDelta = calculateMetricDelta($curHyd, $prevHyd);

respondJson(200, [
    "status" => "success",
    "message" => "Vitals trends retrieved successfully",
    "data" => [
        "user_id" => $userId,
        "period" => $period,
        "current_period" => [
            "start_date" => $curStart,
            "end_date" => $curEnd
        ],
        "previous_period" => [
            "start_date" => $prevStart,
            "end_date" => $prevEnd
        ],
        "trends" => [
            "heart_rate" => [
                "current_avg" => $curHr,
                "previous_avg" => $prevHr,
                "delta_percent" => $hrDelta["delta_percent"],
                "direction" => $hrDelta["direction"],
                "status" => ($curHr !== null && $curHr >= 60 && $curHr <= 100) ? "Optimal" : "Review Needed"
            ],
            "spo2" => [
                "current_avg" => $curSpo2,
                "previous_avg" => $prevSpo2,
                "delta_percent" => $spo2Delta["delta_percent"],
                "direction" => $spo2Delta["direction"],
                "status" => ($curSpo2 !== null && $curSpo2 >= 95) ? "Optimal" : "Attention Needed"
            ],
            "blood_pressure" => [
                "systolic" => [
                    "current_avg" => $curBp["sys"],
                    "previous_avg" => $prevBp["sys"],
                    "delta_percent" => $sysDelta["delta_percent"],
                    "direction" => $sysDelta["direction"]
                ],
                "diastolic" => [
                    "current_avg" => $curBp["dia"],
                    "previous_avg" => $prevBp["dia"],
                    "delta_percent" => $diaDelta["delta_percent"],
                    "direction" => $diaDelta["direction"]
                ],
                "status" => ($curBp["sys"] !== null && $curBp["sys"] <= 120 && $curBp["dia"] <= 80) ? "Optimal" : "Elevated"
            ],
            "steps" => [
                "current_avg" => $curSteps,
                "previous_avg" => $prevSteps,
                "delta_percent" => $stepsDelta["delta_percent"],
                "direction" => $stepsDelta["direction"],
                "status" => ($curSteps !== null && $curSteps >= 8000) ? "Active" : "Light Activity"
            ],
            "sleep" => [
                "current_avg_minutes" => $curSleep,
                "previous_avg_minutes" => $prevSleep,
                "delta_percent" => $sleepDelta["delta_percent"],
                "direction" => $sleepDelta["direction"],
                "status" => ($curSleep !== null && $curSleep >= 480) ? "Restful" : "Suboptimal"
            ],
            "hydration" => [
                "current_avg_ml" => $curHyd,
                "previous_avg_ml" => $prevHyd,
                "delta_percent" => $hydDelta["delta_percent"],
                "direction" => $hydDelta["direction"],
                "status" => ($curHyd !== null && $curHyd >= 2000) ? "Target Met" : "Low Intake"
            ]
        ]
    ]
]);

