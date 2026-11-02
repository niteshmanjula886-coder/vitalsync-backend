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

    if ($row) {
        $goal = [
            "age" => $row["age"] !== null ? (int)$row["age"] : null,
            "weightKg" => $row["weight_kg"] !== null ? (float)$row["weight_kg"] : null,
            "heightCm" => $row["height_cm"] !== null ? (float)$row["height_cm"] : null,
            "activityLevel" => $row["activity_level"],
            "fitnessGoal" => $row["fitness_goal"],
            "dietaryPreference" => $row["dietary_preference"],
            "dailyCalorieTarget" => $row["daily_calorie_target"] !== null ? (int)$row["daily_calorie_target"] : null,
            "dailyProteinTarget" => $row["daily_protein_target"] !== null ? (float)$row["daily_protein_target"] : null,
            "dailyCarbsTarget" => $row["daily_carbs_target"] !== null ? (float)$row["daily_carbs_target"] : null,
            "dailyFatTarget" => $row["daily_fat_target"] !== null ? (float)$row["daily_fat_target"] : null
        ];
    } else {
        // Check if user has personal profile details
        $pStmt = $conn->prepare("SELECT age, height_cm, weight_kg FROM user_profiles WHERE user_id = ? LIMIT 1");
        $pStmt->bind_param("i", $userId);
        $pStmt->execute();
        $pRow = $pStmt->get_result()->fetch_assoc();
        $pStmt->close();

        $goal = [
            "age" => ($pRow && $pRow["age"] !== null) ? (int)$pRow["age"] : null,
            "weightKg" => ($pRow && $pRow["weight_kg"] !== null) ? (float)$pRow["weight_kg"] : null,
            "heightCm" => ($pRow && $pRow["height_cm"] !== null) ? (float)$pRow["height_cm"] : null,
            "activityLevel" => null,
            "fitnessGoal" => null,
            "dietaryPreference" => null,
            "dailyCalorieTarget" => null,
            "dailyProteinTarget" => null,
            "dailyCarbsTarget" => null,
            "dailyFatTarget" => null
        ];
    }
    $conn->close();

    respondJson(200, [
        "success" => true,
        "nutrition_goal" => $goal
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $age = isset($data["age"]) && is_numeric($data["age"]) ? (int)$data["age"] : null;
    $weight = isset($data["weightKg"]) && is_numeric($data["weightKg"]) ? (float)$data["weightKg"] : (isset($data["weight_kg"]) && is_numeric($data["weight_kg"]) ? (float)$data["weight_kg"] : null);
    $height = isset($data["heightCm"]) && is_numeric($data["heightCm"]) ? (float)$data["heightCm"] : (isset($data["height_cm"]) && is_numeric($data["height_cm"]) ? (float)$data["height_cm"] : null);
    $activity = !empty($data["activityLevel"]) ? trim((string)$data["activityLevel"]) : (!empty($data["activity_level"]) ? trim((string)$data["activity_level"]) : null);
    $fitnessGoal = !empty($data["fitnessGoal"]) ? strtoupper(trim((string)$data["fitnessGoal"])) : (!empty($data["fitness_goal"]) ? strtoupper(trim((string)$data["fitness_goal"])) : null);
    $dietPref = !empty($data["dietaryPreference"]) ? strtoupper(trim((string)$data["dietaryPreference"])) : (!empty($data["dietary_preference"]) ? strtoupper(trim((string)$data["dietary_preference"])) : null);
    $calories = isset($data["dailyCalorieTarget"]) && is_numeric($data["dailyCalorieTarget"]) ? (int)$data["dailyCalorieTarget"] : (isset($data["daily_calorie_target"]) && is_numeric($data["daily_calorie_target"]) ? (int)$data["daily_calorie_target"] : null);
    $protein = isset($data["dailyProteinTarget"]) && is_numeric($data["dailyProteinTarget"]) ? (float)$data["dailyProteinTarget"] : (isset($data["daily_protein_target"]) && is_numeric($data["daily_protein_target"]) ? (float)$data["daily_protein_target"] : null);
    $carbs = isset($data["dailyCarbsTarget"]) && is_numeric($data["dailyCarbsTarget"]) ? (float)$data["dailyCarbsTarget"] : (isset($data["daily_carbs_target"]) && is_numeric($data["daily_carbs_target"]) ? (float)$data["daily_carbs_target"] : null);
    $fat = isset($data["dailyFatTarget"]) && is_numeric($data["dailyFatTarget"]) ? (float)$data["dailyFatTarget"] : (isset($data["daily_fat_target"]) && is_numeric($data["daily_fat_target"]) ? (float)$data["daily_fat_target"] : null);

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
                "age" => $age,
                "weightKg" => $weight,
                "heightCm" => $height,
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
