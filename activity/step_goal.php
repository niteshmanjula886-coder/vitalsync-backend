<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

function resolveUserStepGoal(mysqli $conn, int $userId): int {
    $goal = 0;
    try {
        $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'step_goal'");
        if ($chk && $chk->num_rows > 0) {
            $uStmt = $conn->prepare("SELECT step_goal FROM users WHERE id = ? LIMIT 1");
            if ($uStmt) {
                $uStmt->bind_param("i", $userId);
                $uStmt->execute();
                $uRow = $uStmt->get_result()->fetch_assoc();
                $uStmt->close();
                if (!empty($uRow["step_goal"]) && (int)$uRow["step_goal"] > 0) {
                    $goal = (int)$uRow["step_goal"];
                }
            }
        }
    } catch (\Throwable $e) {}

    $agStmt = $conn->prepare("SELECT daily_step_goal, updated_at FROM activity_goals WHERE user_id = ? LIMIT 1");
    $agGoal = 0;
    $agUpdated = null;
    if ($agStmt) {
        $agStmt->bind_param("i", $userId);
        $agStmt->execute();
        $agRow = $agStmt->get_result()->fetch_assoc();
        $agStmt->close();
        if ($agRow && (int)($agRow["daily_step_goal"] ?? 0) > 0) {
            $agGoal = (int)$agRow["daily_step_goal"];
            $agUpdated = $agRow["updated_at"] ?? null;
        }
    }

    $hgGoal = 0;
    $hgUpdated = null;
    $hgStmt = $conn->prepare("SELECT target_steps, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
    if ($hgStmt) {
        $hgStmt->bind_param("i", $userId);
        $hgStmt->execute();
        $hgRow = $hgStmt->get_result()->fetch_assoc();
        $hgStmt->close();
        if ($hgRow && (int)($hgRow["target_steps"] ?? 0) > 0) {
            $hgGoal = (int)$hgRow["target_steps"];
            $hgUpdated = $hgRow["updated_at"] ?? null;
        }
    }

    if ($agGoal > 0 && $hgGoal > 0) {
        return ($hgUpdated !== null && $agUpdated !== null && strtotime($hgUpdated) > strtotime($agUpdated))
            ? $hgGoal : $agGoal;
    }
    if ($agGoal > 0) return $agGoal;
    if ($hgGoal > 0) return $hgGoal;
    if ($goal > 0) return $goal;
    return 8000;
}

if ($method === "GET") {
    $goal = resolveUserStepGoal($conn, $userId);
    $conn->close();

    $estimatedKm = round($goal * 0.00075, 1);
    $estimatedCalories = (int)($goal * 0.04);

    respondJson(200, [
        "success" => true,
        "step_goal" => $goal,
        "daily_step_goal" => $goal,
        "target_steps" => $goal,
        "estimated_distance_km" => $estimatedKm,
        "estimated_calories_kcal" => $estimatedCalories
    ]);
}

if ($method === "POST" || $method === "PUT") {
    $data = getInputData();
    $newGoal = isset($data["step_goal"]) ? (int)$data["step_goal"] : (isset($data["daily_step_goal"]) ? (int)$data["daily_step_goal"] : (isset($data["target_steps"]) ? (int)$data["target_steps"] : 0));

    if ($newGoal < 1000 || $newGoal > 100000) {
        respondJson(400, [
            "success" => false,
            "message" => "Please enter a realistic step goal between 1,000 and 100,000 steps"
        ]);
    }

    // 1. Update activity_goals
    $stmt = $conn->prepare(
        "INSERT INTO activity_goals (user_id, daily_step_goal)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE daily_step_goal = VALUES(daily_step_goal)"
    );
    $stmt->bind_param("ii", $userId, $newGoal);
    $stmt->execute();
    $stmt->close();

    // 2. Sync user_health_goals.target_steps so AI insights, login & profile read the same saved goal
    $hgStmt = $conn->prepare(
        "INSERT INTO user_health_goals (user_id, target_steps)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE target_steps = VALUES(target_steps)"
    );
    if ($hgStmt) {
        $hgStmt->bind_param("ii", $userId, $newGoal);
        $hgStmt->execute();
        $hgStmt->close();
    }

    // 3. Also update users.step_goal if column exists in MySQL schema
    try {
        $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'step_goal'");
        if ($chk && $chk->num_rows > 0) {
            $uUp = $conn->prepare("UPDATE users SET step_goal = ? WHERE id = ?");
            if ($uUp) {
                $uUp->bind_param("ii", $newGoal, $userId);
                $uUp->execute();
                $uUp->close();
            }
        }
    } catch (\Throwable $e) {}

    $conn->close();

    $estimatedKm = round($newGoal * 0.00075, 1);
    $estimatedCalories = (int)($newGoal * 0.04);

    respondJson(200, [
        "success" => true,
        "message" => "Step goal updated successfully",
        "step_goal" => $newGoal,
        "daily_step_goal" => $newGoal,
        "target_steps" => $newGoal,
        "estimated_distance_km" => $estimatedKm,
        "estimated_calories_kcal" => $estimatedCalories
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

