<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $q = trim($_GET["q"] ?? $_GET["search"] ?? "");
    $category = strtoupper(trim($_GET["category"] ?? ""));

    $sql = "SELECT id, code, name, category, serving_size_amount, serving_unit, calories, protein_grams, carbs_grams, fat_grams, is_custom 
            FROM food_items 
            WHERE (user_id IS NULL OR user_id = ?)";
    $types = "i";
    $params = [$userId];

    if ($q !== "") {
        $sql .= " AND name LIKE ?";
        $types .= "s";
        $params[] = "%$q%";
    }

    if ($category !== "" && in_array($category, ["VEG", "NON_VEG", "EGG", "VEGAN"])) {
        $sql .= " AND category = ?";
        $types .= "s";
        $params[] = $category;
    }

    $sql .= " ORDER BY is_custom ASC, id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $foods = [];
    while ($r = $result->fetch_assoc()) {
        $foods[] = [
            "id" => (string)($r["code"] ?: $r["id"]),
            "db_id" => (int)$r["id"],
            "name" => $r["name"],
            "category" => $r["category"],
            "servingSizeAmount" => (float)$r["serving_size_amount"],
            "serving_size_amount" => (float)$r["serving_size_amount"],
            "servingUnit" => $r["serving_unit"],
            "serving_unit" => $r["serving_unit"],
            "calories" => (int)$r["calories"],
            "proteinGrams" => (float)$r["protein_grams"],
            "protein_grams" => (float)$r["protein_grams"],
            "carbsGrams" => (float)$r["carbs_grams"],
            "carbs_grams" => (float)$r["carbs_grams"],
            "fatGrams" => (float)$r["fat_grams"],
            "fat_grams" => (float)$r["fat_grams"],
            "isCustom" => (bool)$r["is_custom"]
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($foods),
        "foods" => $foods
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $name = trim($data["name"] ?? "");
    $category = strtoupper(trim((string)($data["category"] ?? "VEG")));
    if (!in_array($category, ["VEG", "NON_VEG", "EGG", "VEGAN"])) {
        $category = "VEG";
    }

    $servingAmount = isset($data["servingSizeAmount"]) ? (float)$data["servingSizeAmount"] : (isset($data["serving_size_amount"]) ? (float)$data["serving_size_amount"] : 100.0);
    $servingUnit = trim((string)($data["servingUnit"] ?? $data["serving_unit"] ?? "100g"));
    $calories = isset($data["calories"]) ? (int)$data["calories"] : 0;
    $protein = isset($data["proteinGrams"]) ? (float)$data["proteinGrams"] : (isset($data["protein_grams"]) ? (float)$data["protein_grams"] : 0.0);
    $carbs = isset($data["carbsGrams"]) ? (float)$data["carbsGrams"] : (isset($data["carbs_grams"]) ? (float)$data["carbs_grams"] : 0.0);
    $fat = isset($data["fatGrams"]) ? (float)$data["fatGrams"] : (isset($data["fat_grams"]) ? (float)$data["fat_grams"] : 0.0);

    if ($name === "" || $calories <= 0) {
        respondJson(400, [
            "success" => false,
            "message" => "Food name and valid calorie count are required"
        ]);
    }

    $code = "cf_" . bin2hex(random_bytes(4));

    $stmt = $conn->prepare(
        "INSERT INTO food_items (user_id, code, name, category, serving_size_amount, serving_unit, calories, protein_grams, carbs_grams, fat_grams, is_custom)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("isssdsiddd", $userId, $code, $name, $category, $servingAmount, $servingUnit, $calories, $protein, $carbs, $fat);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Custom food item added successfully",
            "food" => [
                "id" => $code,
                "db_id" => $newId,
                "name" => $name,
                "category" => $category,
                "servingSizeAmount" => $servingAmount,
                "serving_size_amount" => $servingAmount,
                "servingUnit" => $servingUnit,
                "serving_unit" => $servingUnit,
                "calories" => $calories,
                "proteinGrams" => $protein,
                "protein_grams" => $protein,
                "carbsGrams" => $carbs,
                "carbs_grams" => $carbs,
                "fatGrams" => $fat,
                "fat_grams" => $fat,
                "isCustom" => true
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to add food item: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

