<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];
$today = date("Y-m-d");

if ($method === "GET") {
    $date = trim($_GET["date"] ?? $today);

    // Get user's nutrition goal
    $gStmt = $conn->prepare("SELECT daily_calorie_target, daily_protein_target, daily_carbs_target, daily_fat_target FROM nutrition_goals WHERE user_id = ? LIMIT 1");
    $gStmt->bind_param("i", $userId);
    $gStmt->execute();
    $gRow = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();

    $goalCalories = $gRow ? (int)$gRow["daily_calorie_target"] : 2200;
    $goalProtein = $gRow ? (float)$gRow["daily_protein_target"] : 130.0;
    $goalCarbs = $gRow ? (float)$gRow["daily_carbs_target"] : 250.0;
    $goalFat = $gRow ? (float)$gRow["daily_fat_target"] : 65.0;

    // Fetch meal entries for date joined with food_items
    $mStmt = $conn->prepare(
        "SELECT 
            m.id, m.category, m.quantity, m.calculated_calories, m.calculated_protein, m.calculated_carbs, m.calculated_fat, m.date_string,
            f.id AS food_id, f.code AS food_code, f.name AS food_name, f.category AS food_category, f.serving_size_amount, f.serving_unit, f.calories AS base_cal, f.protein_grams AS base_protein, f.carbs_grams AS base_carbs, f.fat_grams AS base_fat, f.is_custom
         FROM meal_entries m
         LEFT JOIN food_items f ON m.food_item_id = f.id
         WHERE m.user_id = ? AND m.date_string = ?
         ORDER BY m.id ASC"
    );
    $mStmt->bind_param("is", $userId, $date);
    $mStmt->execute();
    $mRes = $mStmt->get_result();

    $entries = [];
    $totalCalories = 0;
    $totalProtein = 0.0;
    $totalCarbs = 0.0;
    $totalFat = 0.0;

    while ($r = $mRes->fetch_assoc()) {
        $entry = [
            "id" => (string)$r["id"],
            "category" => $r["category"],
            "quantity" => (float)$r["quantity"],
            "calculatedCalories" => (int)$r["calculated_calories"],
            "calculated_calories" => (int)$r["calculated_calories"],
            "calculatedProtein" => (float)$r["calculated_protein"],
            "calculated_protein" => (float)$r["calculated_protein"],
            "calculatedCarbs" => (float)$r["calculated_carbs"],
            "calculated_carbs" => (float)$r["calculated_carbs"],
            "calculatedFat" => (float)$r["calculated_fat"],
            "calculated_fat" => (float)$r["calculated_fat"],
            "dateString" => $r["date_string"],
            "foodItem" => [
                "id" => (string)($r["food_code"] ?: ($r["food_id"] ?: "f_custom")),
                "name" => $r["food_name"] ?: "Custom Food",
                "category" => $r["food_category"] ?: "VEG",
                "servingSizeAmount" => (float)($r["serving_size_amount"] ?: 100.0),
                "servingUnit" => $r["serving_unit"] ?: "serving",
                "calories" => (int)($r["base_cal"] ?: $r["calculated_calories"]),
                "proteinGrams" => (float)($r["base_protein"] ?: $r["calculated_protein"]),
                "carbsGrams" => (float)($r["base_carbs"] ?: $r["calculated_carbs"]),
                "fatGrams" => (float)($r["base_fat"] ?: $r["calculated_fat"]),
                "isCustom" => (bool)$r["is_custom"]
            ]
        ];

        $entries[] = $entry;
        $totalCalories += (int)$r["calculated_calories"];
        $totalProtein += (float)$r["calculated_protein"];
        $totalCarbs += (float)$r["calculated_carbs"];
        $totalFat += (float)$r["calculated_fat"];
    }
    $mStmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "date" => $date,
        "totals" => [
            "calories" => $totalCalories,
            "calorie_goal" => $goalCalories,
            "protein" => round($totalProtein, 1),
            "protein_goal" => $goalProtein,
            "carbs" => round($totalCarbs, 1),
            "carbs_goal" => $goalCarbs,
            "fat" => round($totalFat, 1),
            "fat_goal" => $goalFat
        ],
        "meals_count" => count($entries),
        "meals" => $entries
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $category = strtoupper(trim((string)($data["category"] ?? "BREAKFAST")));
    if (!in_array($category, ["BREAKFAST", "LUNCH", "DINNER", "SNACKS"])) {
        $category = "BREAKFAST";
    }

    $quantity = isset($data["quantity"]) ? (float)$data["quantity"] : 1.0;
    if ($quantity <= 0) $quantity = 1.0;

    $dateString = !empty($data["dateString"]) ? trim((string)$data["dateString"]) : (!empty($data["date_string"]) ? trim((string)$data["date_string"]) : $today);

    $foodId = null;
    $cal = 0;
    $protein = 0.0;
    $carbs = 0.0;
    $fat = 0.0;

    // Check if food_id is provided
    if (isset($data["food_item_id"]) || isset($data["foodId"])) {
        $fIdentifier = trim((string)($data["food_item_id"] ?? $data["foodId"]));
        $fStmt = $conn->prepare("SELECT id, calories, protein_grams, carbs_grams, fat_grams FROM food_items WHERE code = ? OR id = ? LIMIT 1");
        $fStmt->bind_param("ss", $fIdentifier, $fIdentifier);
        $fStmt->execute();
        $fRow = $fStmt->get_result()->fetch_assoc();
        $fStmt->close();

        if ($fRow) {
            $foodId = (int)$fRow["id"];
            $cal = (int)round($fRow["calories"] * $quantity);
            $protein = round($fRow["protein_grams"] * $quantity, 1);
            $carbs = round($fRow["carbs_grams"] * $quantity, 1);
            $fat = round($fRow["fat_grams"] * $quantity, 1);
        }
    }

    // Direct calculated values override if supplied
    if (isset($data["calculatedCalories"])) $cal = (int)$data["calculatedCalories"];
    elseif (isset($data["calculated_calories"])) $cal = (int)$data["calculated_calories"];

    if (isset($data["calculatedProtein"])) $protein = (float)$data["calculatedProtein"];
    elseif (isset($data["calculated_protein"])) $protein = (float)$data["calculated_protein"];

    if (isset($data["calculatedCarbs"])) $carbs = (float)$data["calculatedCarbs"];
    elseif (isset($data["calculated_carbs"])) $carbs = (float)$data["calculated_carbs"];

    if (isset($data["calculatedFat"])) $fat = (float)$data["calculatedFat"];
    elseif (isset($data["calculated_fat"])) $fat = (float)$data["calculated_fat"];

    if ($cal <= 0) {
        respondJson(400, [
            "success" => false,
            "message" => "Valid food item or calories required to log a meal entry"
        ]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO meal_entries (user_id, food_item_id, category, quantity, calculated_calories, calculated_protein, calculated_carbs, calculated_fat, date_string)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iisdiddds", $userId, $foodId, $category, $quantity, $cal, $protein, $carbs, $fat, $dateString);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Meal logged successfully",
            "entry" => [
                "id" => (string)$newId,
                "category" => $category,
                "quantity" => $quantity,
                "calculatedCalories" => $cal,
                "calculatedProtein" => $protein,
                "calculatedCarbs" => $carbs,
                "calculatedFat" => $fat,
                "dateString" => $dateString
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to log meal entry: " . $stmt->error
    ]);
}

if ($method === "DELETE") {
    $rawId = isset($_GET["id"]) ? trim((string)$_GET["id"]) : "";
    if ($rawId === "") {
        $data = getInputData();
        $rawId = isset($data["id"]) ? trim((string)$data["id"]) : "";
    }

    if ($rawId === "") {
        respondJson(400, [
            "success" => false,
            "message" => "Meal entry id is required to delete"
        ]);
    }

    // If it's a non-numeric local mock id (e.g. 'm1', 'm2'), acknowledge as removed
    if (!is_numeric($rawId)) {
        $conn->close();
        respondJson(200, [
            "success" => true,
            "message" => "Meal entry removed successfully"
        ]);
    }

    $id = (int)$rawId;
    $stmt = $conn->prepare("DELETE FROM meal_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Idempotent delete: return 200 whether 1 row was removed or already deleted
    respondJson(200, [
        "success" => true,
        "message" => "Meal entry removed successfully"
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

