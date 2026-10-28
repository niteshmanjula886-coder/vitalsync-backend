<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT daily_step_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    $goal = $row ? (int)$row["daily_step_goal"] : 8000;
    $estimatedKm = round($goal * 0.00075, 1);
    $estimatedCalories = (int)($goal * 0.04);

    respondJson(200, [
        "success" => true,
        "daily_step_goal" => $goal,
        "estimated_distance_km" => $estimatedKm,
        "estimated_calories_kcal" => $estimatedCalories
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $newGoal = isset($data["step_goal"]) ? (int)$data["step_goal"] : (isset($data["daily_step_goal"]) ? (int)$data["daily_step_goal"] : 0);

    if ($newGoal < 1000 || $newGoal > 100000) {
        respondJson(400, [
            "success" => false,
            "message" => "Please enter a realistic step goal between 1,000 and 100,000 steps"
        ]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO activity_goals (user_id, daily_step_goal)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE daily_step_goal = VALUES(daily_step_goal)"
    );
    $stmt->bind_param("ii", $userId, $newGoal);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $estimatedKm = round($newGoal * 0.00075, 1);
    $estimatedCalories = (int)($newGoal * 0.04);

    respondJson(200, [
        "success" => true,
        "message" => "Step goal updated successfully",
        "daily_step_goal" => $newGoal,
        "estimated_distance_km" => $estimatedKm,
        "estimated_calories_kcal" => $estimatedCalories
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

