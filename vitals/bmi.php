<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT height, weight, age, gender FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $height = ($user && $user["height"] !== null) ? (float)$user["height"] : 0.0;
    $weight = ($user && $user["weight"] !== null) ? (float)$user["weight"] : 0.0;
    $age = ($user && $user["age"] !== null) ? (int)$user["age"] : 0;
    $gender = ($user && !empty($user["gender"])) ? $user["gender"] : "";

    $hasValidBmi = ($height > 0 && $weight > 0);
    $bmi = $hasValidBmi ? round($weight / (($height / 100) * ($height / 100)), 1) : 0.0;
    $category = $hasValidBmi ? (($bmi < 18.5) ? "Underweight" : (($bmi <= 24.9) ? "Healthy Range" : (($bmi <= 29.9) ? "Overweight" : "Obese"))) : "Not configured";

    $conn->close();

    respondJson(200, [
        "success" => true,
        "bmi" => [
            "value" => $bmi,
            "category" => $category,
            "height_cm" => $height,
            "weight_kg" => $weight,
            "age" => $age,
            "gender" => $gender,
            "healthy_weight_range_kg" => [
                "min" => $height > 0 ? round(18.5 * (($height / 100) * ($height / 100)), 1) : 0.0,
                "max" => $height > 0 ? round(24.9 * (($height / 100) * ($height / 100)), 1) : 0.0
            ]
        ]
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $height = isset($data["height"]) ? (float)$data["height"] : (isset($data["height_cm"]) ? (float)$data["height_cm"] : 0);
    $weight = isset($data["weight"]) ? (float)$data["weight"] : (isset($data["weight_kg"]) ? (float)$data["weight_kg"] : 0);

    if ($height < 50 || $height > 260 || $weight < 20 || $weight > 350) {
        respondJson(400, [
            "success" => false,
            "message" => "Please provide realistic height (50-260 cm) and weight (20-350 kg)"
        ]);
    }

    $bmi = round($weight / (($height / 100) * ($height / 100)), 1);
    $category = ($bmi < 18.5) ? "Underweight" : (($bmi <= 24.9) ? "Healthy Range" : (($bmi <= 29.9) ? "Overweight" : "Obese"));

    // Update user profile
    $uStmt = $conn->prepare("UPDATE users SET height = ?, weight = ? WHERE id = ?");
    $uStmt->bind_param("ddi", $height, $weight, $userId);
    $uStmt->execute();
    $uStmt->close();

    // Log vital reading
    $vText = "$bmi ($category)";
    $stmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_text, unit, status_label, source)
         VALUES (?, 'bmi', ?, ?, 'kg/m²', ?, 'VitalSync Calculator')"
    );
    $stmt->bind_param("idss", $userId, $bmi, $vText, $category);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "message" => "Body metrics and BMI updated successfully",
        "bmi" => [
            "id" => $newId,
            "value" => $bmi,
            "category" => $category,
            "height_cm" => $height,
            "weight_kg" => $weight
        ]
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

