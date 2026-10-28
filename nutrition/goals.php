<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT * FROM nutrition_goals WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    $goal = [
        "age" => $row ? (int)$row["age"] : 28,
        "weightKg" => $row ? (float)$row["weight_kg"] : 68.5,
        "heightCm" => $row ? (float)$row["height_cm"] : 175.0,
        "activityLevel" => $row ? $row["activity_level"] : "Moderately Active",
        "fitnessGoal" => $row ? $row["fitness_goal"] : "MUSCLE_GAIN",
        "dietaryPreference" => $row ? $row["dietary_preference"] : "NON_VEGETARIAN",
        "dailyCalorieTarget" => $row ? (int)$row["daily_calorie_target"] : 2200,
        "dailyProteinTarget" => $row ? (float)$row["daily_protein_target"] : 130.0,
        "dailyCarbsTarget" => $row ? (float)$row["daily_carbs_target"] : 250.0,
        "dailyFatTarget" => $row ? (float)$row["daily_fat_target"] : 65.0
    ];

    respondJson(200, [
        "success" => true,
        "nutrition_goal" => $goal
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $age = isset($data["age"]) ? (int)$data["age"] : 28;
    $weight = isset($data["weightKg"]) ? (float)$data["weightKg"] : (isset($data["weight_kg"]) ? (float)$data["weight_kg"] : 68.5);
    $height = isset($data["heightCm"]) ? (float)$data["heightCm"] : (isset($data["height_cm"]) ? (float)$data["height_cm"] : 175.0);
    $activity = trim($data["activityLevel"] ?? $data["activity_level"] ?? "Moderately Active");
    $fitnessGoal = strtoupper(trim((string)($data["fitnessGoal"] ?? $data["fitness_goal"] ?? "MUSCLE_GAIN")));
    $dietPref = strtoupper(trim((string)($data["dietaryPreference"] ?? $data["dietary_preference"] ?? "NON_VEGETARIAN")));
    $calories = isset($data["dailyCalorieTarget"]) ? (int)$data["dailyCalorieTarget"] : (isset($data["daily_calorie_target"]) ? (int)$data["daily_calorie_target"] : 2200);
    $protein = isset($data["dailyProteinTarget"]) ? (float)$data["dailyProteinTarget"] : (isset($data["daily_protein_target"]) ? (float)$data["daily_protein_target"] : 130.0);
    $carbs = isset($data["dailyCarbsTarget"]) ? (float)$data["dailyCarbsTarget"] : (isset($data["daily_carbs_target"]) ? (float)$data["daily_carbs_target"] : 250.0);
    $fat = isset($data["dailyFatTarget"]) ? (float)$data["dailyFatTarget"] : (isset($data["daily_fat_target"]) ? (float)$data["daily_fat_target"] : 65.0);

    $stmt = $conn->prepare(
        "INSERT INTO nutrition_goals (user_id, age, weight_kg, height_cm, activity_level, fitness_goal, dietary_preference, daily_calorie_target, daily_protein_target, daily_carbs_target, daily_fat_target)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            age = VALUES(age),
            weight_kg = VALUES(weight_kg),
            height_cm = VALUES(height_cm),
            activity_level = VALUES(activity_level),
            fitness_goal = VALUES(fitness_goal),
            dietary_preference = VALUES(dietary_preference),
            daily_calorie_target = VALUES(daily_calorie_target),
            daily_protein_target = VALUES(daily_protein_target),
            daily_carbs_target = VALUES(daily_carbs_target),
            daily_fat_target = VALUES(daily_fat_target)"
    );

    $stmt->bind_param("iidssssiddd", $userId, $age, $weight, $height, $activity, $fitnessGoal, $dietPref, $calories, $protein, $carbs, $fat);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Nutrition goals updated successfully",
            "nutrition_goal" => [
                "dailyCalorieTarget" => $calories,
                "dailyProteinTarget" => $protein,
                "dailyCarbsTarget" => $carbs,
                "dailyFatTarget" => $fat,
                "fitnessGoal" => $fitnessGoal,
                "dietaryPreference" => $dietPref
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to update nutrition goals: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

