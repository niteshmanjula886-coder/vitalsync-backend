<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // 1. Fetch user height and weight from users and user_profiles
    $stmt = $conn->prepare(
        "SELECT u.height, u.weight, u.age, u.gender,
                p.height_cm, p.weight_kg, p.age as p_age, p.gender as p_gender
         FROM users u
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE u.id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $height = 0.0;
    if ($user && $user["height"] !== null && (float)$user["height"] > 0) {
        $height = (float)$user["height"];
    } elseif ($user && $user["height_cm"] !== null && (float)$user["height_cm"] > 0) {
        $height = (float)$user["height_cm"];
    }

    $weight = 0.0;
    if ($user && $user["weight"] !== null && (float)$user["weight"] > 0) {
        $weight = (float)$user["weight"];
    } elseif ($user && $user["weight_kg"] !== null && (float)$user["weight_kg"] > 0) {
        $weight = (float)$user["weight_kg"];
    }

    $age = ($user && $user["age"] !== null) ? (int)$user["age"] : (($user && $user["p_age"] !== null) ? (int)$user["p_age"] : 0);
    $gender = ($user && !empty($user["gender"])) ? $user["gender"] : (($user && !empty($user["p_gender"])) ? $user["p_gender"] : "");

    $hasValidBmi = ($height > 0 && $weight > 0);
    $bmi = $hasValidBmi ? round($weight / (($height / 100) * ($height / 100)), 1) : 0.0;
    
    // Dynamic category computation
    $category = "Not configured";
    $categoryColorHex = "#9CA3AF";
    if ($hasValidBmi) {
        if ($bmi < 18.5) {
            $category = "Underweight";
            $categoryColorHex = "#38BDF8";
        } elseif ($bmi <= 24.9) {
            $category = "Healthy Range";
            $categoryColorHex = "#0F9F7A";
        } elseif ($bmi <= 29.9) {
            $category = "Overweight";
            $categoryColorHex = "#F59E0B";
        } else {
            $category = "Obese";
            $categoryColorHex = "#EF4444";
        }
    }

    // 2. Fetch recent BMI trend readings from vitals_readings
    $trendHistory = [];
    $hStmt = $conn->prepare(
        "SELECT value_numeric, status_label, recorded_at 
         FROM vitals_readings 
         WHERE user_id = ? AND reading_type = 'bmi' 
         ORDER BY recorded_at ASC LIMIT 12"
    );
    if ($hStmt) {
        $hStmt->bind_param("i", $userId);
        $hStmt->execute();
        $hRes = $hStmt->get_result();
        while ($hr = $hRes->fetch_assoc()) {
            $trendHistory[] = [
                "value" => (float)$hr["value_numeric"],
                "category" => $hr["status_label"],
                "recorded_at" => $hr["recorded_at"],
                "month" => date("M", strtotime($hr["recorded_at"]))
            ];
        }
        $hStmt->close();
    }

    // Trend direction
    $trendDirection = "stable";
    $initialBmi = $bmi;
    if (count($trendHistory) >= 2) {
        $first = $trendHistory[0]["value"];
        $last = $trendHistory[count($trendHistory) - 1]["value"];
        $initialBmi = $first;
        if ($last > $first + 0.2) {
            $trendDirection = "increasing";
        } elseif ($last < $first - 0.2) {
            $trendDirection = "decreasing";
        }
    }

    $conn->close();

    respondJson(200, [
        "success" => true,
        "bmi" => [
            "value" => $bmi,
            "category" => $category,
            "category_color" => $categoryColorHex,
            "height_cm" => $height,
            "weight_kg" => $weight,
            "age" => $age,
            "gender" => $gender,
            "trend_direction" => $trendDirection,
            "initial_bmi" => $initialBmi,
            "history" => $trendHistory,
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
    $categoryColorHex = ($bmi < 18.5) ? "#38BDF8" : (($bmi <= 24.9) ? "#0F9F7A" : (($bmi <= 29.9) ? "#F59E0B" : "#EF4444"));

    // 1. Update users table
    $uStmt = $conn->prepare("UPDATE users SET height = ?, weight = ? WHERE id = ?");
    if ($uStmt) {
        $uStmt->bind_param("ddi", $height, $weight, $userId);
        $uStmt->execute();
        $uStmt->close();
    }

    // 2. Update user_profiles table
    $pStmt = $conn->prepare("UPDATE user_profiles SET height_cm = ?, weight_kg = ? WHERE user_id = ?");
    if ($pStmt) {
        $pStmt->bind_param("ddi", $height, $weight, $userId);
        $pStmt->execute();
        $pStmt->close();
    }

    // 3. Log vital reading
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
            "category_color" => $categoryColorHex,
            "height_cm" => $height,
            "weight_kg" => $weight
        ]
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);
