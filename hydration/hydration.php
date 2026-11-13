<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];
$today = date("Y-m-d");

if ($method === "GET") {
    $date = trim($_GET["date"] ?? $today);

    // Get goal & reminder settings
    $gStmt = $conn->prepare("SELECT daily_target_ml, reminders_enabled FROM hydration_goals WHERE user_id = ? LIMIT 1");
    $gStmt->bind_param("i", $userId);
    $gStmt->execute();
    $goalRow = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();

    $targetMl = $goalRow ? (int)$goalRow["daily_target_ml"] : 2000;
    $remindersEnabled = $goalRow ? (bool)$goalRow["reminders_enabled"] : true;

    // Get logs for date
    $lStmt = $conn->prepare("SELECT id, COALESCE(volume_ml, amount_ml, intake_ml, 0) as amount_ml, logged_at FROM hydration_logs WHERE user_id = ? AND (date_string = ? OR log_date = ?) ORDER BY logged_at ASC");
    $lStmt->bind_param("iss", $userId, $date, $date);
    $lStmt->execute();
    $lRes = $lStmt->get_result();

    $logs = [];
    $totalConsumed = 0;
    $hasEntries = false;
    while ($r = $lRes->fetch_assoc()) {
        $hasEntries = true;
        $amt = (int)$r["amount_ml"];
        if ($amt > 0) {
            $logs[] = [
                "id" => (int)$r["id"],
                "amount_ml" => $amt,
                "logged_at" => $r["logged_at"],
                "time_formatted" => date("h:i A", strtotime($r["logged_at"]))
            ];
            $totalConsumed += $amt;
        }
    }
    $lStmt->close();

    $glassEquivalent = round($totalConsumed / 250, 1);
    $progressPercent = $targetMl > 0 ? min(100, (int)(($totalConsumed / $targetMl) * 100)) : 0;

    $rawHydPct = ($targetMl > 0) ? (($totalConsumed / $targetMl) * 100.0) : 0.0;
    $remMl = max(0, $targetMl - $totalConsumed);
    if ($rawHydPct < 40.0) {
        $aiSummary = "Water intake is critically low ({$totalConsumed} / {$targetMl} ml, {$remMl} ml remaining). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Drink 1–2 glasses immediately to restore fluid balance.";
    } elseif ($rawHydPct < 75.0) {
        $aiSummary = "Intake is lagging behind your daily goal ({$totalConsumed} / {$targetMl} ml, {$remMl} ml remaining). Insufficient hydration can trigger false hunger cues, reduce physical endurance, and elevate resting heart rate. Try keeping a water bottle nearby to sip consistently.";
    } elseif ($rawHydPct < 100.0) {
        $aiSummary = "You're close to your hydration target ({$totalConsumed} / {$targetMl} ml)! Just {$remMl} ml remaining to ensure optimal electrolyte balance and physical performance.";
    } else {
        $aiSummary = "Daily hydration goal achieved ({$totalConsumed} / {$targetMl} ml)! Optimal fluid balance supports muscle recovery and cognitive clarity. Maintain steady intake if working out.";
    }

    // Rolling 7-day history ending on $date (relative to CURDATE())
    $rollingStart = date("Y-m-d", strtotime($date . " - 6 days"));

    $wStmt = $conn->prepare(
        "SELECT DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) as date_string,
                COALESCE(SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)), 0) as total_ml 
         FROM hydration_logs 
         WHERE user_id = ?
           AND DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at)) BETWEEN ? AND ?
         GROUP BY DATE(COALESCE(logged_at, log_date, logged_date, date_string, created_at))
         ORDER BY date_string ASC"
    );
    $wStmt->bind_param("iss", $userId, $rollingStart, $date);
    $wStmt->execute();
    $wRes = $wStmt->get_result();
    $dailyTotals = [];
    while ($row = $wRes->fetch_assoc()) {
        $dailyTotals[$row["date_string"]] = (int)$row["total_ml"];
    }
    $wStmt->close();
    $conn->close();

    $weeklyHistory = [];
    $weeklyTotalMl = 0;
    for ($i = 0; $i < 7; $i++) {
        $dTs = strtotime($rollingStart . " + $i days");
        $dStr = date("Y-m-d", $dTs);
        $dayLabel = substr(date("D", $dTs), 0, 1);
        $dConsumed = 0;
        if (isset($dailyTotals[$dStr]) && $dailyTotals[$dStr] > 0) {
            $dConsumed = $dailyTotals[$dStr];
        } elseif ($dStr === $date && $totalConsumed > 0) {
            $dConsumed = $totalConsumed;
        }
        $weeklyTotalMl += $dConsumed;

        $ratio = ($dConsumed > 0 && $targetMl > 0) ? min(1.0, round($dConsumed / $targetMl, 2)) : 0.0;
        $weeklyHistory[] = [
            "day" => $dayLabel,
            "date" => date("M d", $dTs),
            "consumed_ml" => $dConsumed,
            "target_ml" => $targetMl,
            "ratio" => $ratio,
            "is_today" => ($dStr === $today)
        ];
    }

    respondJson(200, [
        "success" => true,
        "date" => $date,
        "ai_summary" => $aiSummary,
        "hydration" => [
            "consumed_ml" => $totalConsumed,
            "target_ml" => $targetMl,
            "glass_equivalent" => $glassEquivalent,
            "progress_percent" => $progressPercent,
            "weekly_total_ml" => $weeklyTotalMl,
            "ai_summary" => $aiSummary,
            "reminders_enabled" => $remindersEnabled,
            "logs" => $logs,
            "weekly_history" => $weeklyHistory
        ]
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    // Option 1: Reset water consumption to 0 ml
    if (isset($data["reset"]) && $data["reset"] === true || (isset($data["action"]) && strtolower($data["action"]) === "reset")) {
        $date = !empty($data["date_string"]) ? trim((string)$data["date_string"]) : (!empty($data["date"]) ? trim((string)$data["date"]) : $today);

        // Delete previous logs for this date
        $delStmt = $conn->prepare("DELETE FROM hydration_logs WHERE user_id = ? AND (date_string = ? OR log_date = ?)");
        $delStmt->bind_param("iss", $userId, $date, $date);
        $delStmt->execute();
        $delStmt->close();

        // Insert explicit 0 ml marker so empty fallback doesn't trigger
        $zeroStmt = $conn->prepare("INSERT INTO hydration_logs (user_id, date_string, log_date, amount_ml, volume_ml, logged_at) VALUES (?, ?, ?, 0, 0, NOW())");
        $zeroStmt->bind_param("iss", $userId, $date, $date);
        $zeroStmt->execute();
        $zeroStmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Hydration log reset successfully",
            "total_consumed_ml" => 0,
            "glass_equivalent" => 0.0,
            "progress_percent" => 0
        ]);
    }

    // Option 2: Update Goal or Reminders
    if (isset($data["target_ml"]) || isset($data["reminders_enabled"])) {
        $targetMl = isset($data["target_ml"]) ? (int)$data["target_ml"] : 2000;
        $reminders = isset($data["reminders_enabled"]) ? ($data["reminders_enabled"] ? 1 : 0) : 1;

        $gStmt = $conn->prepare(
            "INSERT INTO hydration_goals (user_id, daily_target_ml, reminders_enabled)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE daily_target_ml = VALUES(daily_target_ml), reminders_enabled = VALUES(reminders_enabled)"
        );
        $gStmt->bind_param("iii", $userId, $targetMl, $reminders);
        $gStmt->execute();
        $gStmt->close();

        // Also update user_health_goals
        $hgStmt = $conn->prepare("INSERT INTO user_health_goals (user_id, target_water_ml) VALUES (?, ?) ON DUPLICATE KEY UPDATE target_water_ml = VALUES(target_water_ml)");
        if ($hgStmt) {
            $hgStmt->bind_param("ii", $userId, $targetMl);
            $hgStmt->execute();
            $hgStmt->close();
        }

        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Hydration settings updated successfully",
            "daily_target_ml" => $targetMl,
            "reminders_enabled" => (bool)$reminders
        ]);
    }

    // Option 3: Log water consumption (+250 ml, custom ml)
    $amountMl = isset($data["amount_ml"]) ? (int)$data["amount_ml"] : (isset($data["volume_ml"]) ? (int)$data["volume_ml"] : (isset($data["amount"]) ? (int)$data["amount"] : 0));
    $date = !empty($data["date_string"]) ? trim((string)$data["date_string"]) : (!empty($data["date"]) ? trim((string)$data["date"]) : $today);

    if ($amountMl < 0) {
        respondJson(400, [
            "success" => false,
            "message" => "Please provide a valid water amount greater than or equal to 0 ml"
        ]);
    }

    if ($amountMl === 0) {
        // Explicit 0 ml reset
        $delStmt = $conn->prepare("DELETE FROM hydration_logs WHERE user_id = ? AND (date_string = ? OR log_date = ?)");
        $delStmt->bind_param("iss", $userId, $date, $date);
        $delStmt->execute();
        $delStmt->close();

        $zeroStmt = $conn->prepare("INSERT INTO hydration_logs (user_id, date_string, log_date, amount_ml, volume_ml, logged_at) VALUES (?, ?, ?, 0, 0, NOW())");
        $zeroStmt->bind_param("iss", $userId, $date, $date);
        $zeroStmt->execute();
        $zeroStmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Hydration log reset to 0 ml",
            "total_consumed_ml" => 0,
            "glass_equivalent" => 0.0,
            "progress_percent" => 0
        ]);
    }

    $stmt = $conn->prepare("INSERT INTO hydration_logs (user_id, date_string, log_date, amount_ml, volume_ml, logged_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issii", $userId, $date, $date, $amountMl, $amountMl);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();

        // Calculate new daily total
        $totStmt = $conn->prepare("SELECT SUM(COALESCE(volume_ml, amount_ml, intake_ml, 0)) as total FROM hydration_logs WHERE user_id = ? AND (date_string = ? OR log_date = ?)");
        $totStmt->bind_param("iss", $userId, $date, $date);
        $totStmt->execute();
        $newTotal = (int)$totStmt->get_result()->fetch_assoc()["total"];
        $totStmt->close();

        // Fetch target for percentage
        $tgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
        $tgStmt->bind_param("i", $userId);
        $tgStmt->execute();
        $tRow = $tgStmt->get_result()->fetch_assoc();
        $tgStmt->close();
        $conn->close();

        $targetMl = $tRow ? (int)$tRow["daily_target_ml"] : 2000;
        $progressPercent = $targetMl > 0 ? min(100, (int)(($newTotal / $targetMl) * 100)) : 0;
        $glassEquivalent = round($newTotal / 250, 1);

        $rawPostPct = ($targetMl > 0) ? (($newTotal / $targetMl) * 100.0) : 0.0;
        $remPostMl = max(0, $targetMl - $newTotal);
        if ($rawPostPct < 40.0) {
            $postAiSummary = "Water intake is critically low ({$newTotal} / {$targetMl} ml, {$remPostMl} ml remaining). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Drink 1–2 glasses immediately to restore fluid balance.";
        } elseif ($rawPostPct < 75.0) {
            $postAiSummary = "Intake is lagging behind your daily goal ({$newTotal} / {$targetMl} ml, {$remPostMl} ml remaining). Insufficient hydration can trigger false hunger cues, reduce physical endurance, and elevate resting heart rate. Try keeping a water bottle nearby to sip consistently.";
        } elseif ($rawPostPct < 100.0) {
            $postAiSummary = "You're close to your hydration target ({$newTotal} / {$targetMl} ml)! Just {$remPostMl} ml remaining to ensure optimal electrolyte balance and physical performance.";
        } else {
            $postAiSummary = "Daily hydration goal achieved ({$newTotal} / {$targetMl} ml)! Optimal fluid balance supports muscle recovery and cognitive clarity. Maintain steady intake if working out.";
        }

        respondJson(201, [
            "success" => true,
            "message" => "+{$amountMl} ml water logged successfully",
            "logged_entry" => [
                "id" => $newId,
                "amount_ml" => $amountMl,
                "date" => $date
            ],
            "total_consumed_ml" => $newTotal,
            "target_ml" => $targetMl,
            "glass_equivalent" => $glassEquivalent,
            "progress_percent" => $progressPercent,
            "ai_summary" => $postAiSummary
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to log hydration: " . $stmt->error
    ]);
}

if ($method === "DELETE") {
    $date = trim($_GET["date"] ?? $today);
    $logId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    if ($logId > 0) {
        $dStmt = $conn->prepare("DELETE FROM hydration_logs WHERE id = ? AND user_id = ?");
        $dStmt->bind_param("ii", $logId, $userId);
        $dStmt->execute();
        $dStmt->close();
    } else {
        // Reset day's hydration
        $dStmt = $conn->prepare("DELETE FROM hydration_logs WHERE user_id = ? AND date_string = ?");
        $dStmt->bind_param("is", $userId, $date);
        $dStmt->execute();
        $dStmt->close();

        // Insert 0 marker
        $zeroStmt = $conn->prepare("INSERT INTO hydration_logs (user_id, date_string, amount_ml) VALUES (?, ?, 0)");
        $zeroStmt->bind_param("is", $userId, $date);
        $zeroStmt->execute();
        $zeroStmt->close();
    }

    // Recompute total
    $totStmt = $conn->prepare("SELECT SUM(amount_ml) as total FROM hydration_logs WHERE user_id = ? AND date_string = ?");
    $totStmt->bind_param("is", $userId, $date);
    $totStmt->execute();
    $newTotal = (int)($totStmt->get_result()->fetch_assoc()["total"] ?? 0);
    $totStmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "message" => $logId > 0 ? "Hydration log removed" : "Hydration log reset successfully",
        "total_consumed_ml" => $newTotal,
        "glass_equivalent" => round($newTotal / 250, 1),
        "progress_percent" => 0
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
