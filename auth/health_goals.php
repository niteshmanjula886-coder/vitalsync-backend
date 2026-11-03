<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function resolveUser($conn, $input) {
    if (isset($input["user_id"]) && is_numeric($input["user_id"]) && (int)$input["user_id"] > 0) {
        return (int)$input["user_id"];
    }
    if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"]) && (int)$_GET["user_id"] > 0) {
        return (int)$_GET["user_id"];
    }
    $email = strtolower(trim($input["email"] ?? $_GET["email"] ?? ""));
    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row["id"];
        }
    }
    return 0;
}

$availableGoals = [
    [
        "id" => "1",
        "title" => "Build Muscle",
        "badge" => "Build Muscle",
        "description" => "Increase strength and lean muscle mass",
        "icon_type" => "MUSCLE"
    ],
    [
        "id" => "2",
        "title" => "Weight Loss",
        "badge" => "Weight Loss",
        "description" => "Burn fat and maintain a calorie deficit",
        "icon_type" => "WEIGHT"
    ],
    [
        "id" => "3",
        "title" => "Improve Fitness",
        "badge" => "Improve Fitness",
        "description" => "General health, endurance, and daily activity",
        "icon_type" => "FITNESS"
    ],
    [
        "id" => "4",
        "title" => "Endurance",
        "badge" => "Endurance",
        "description" => "Stamina and cardiovascular performance",
        "icon_type" => "TIMER"
    ],
    [
        "id" => "5",
        "title" => "Better Sleep",
        "badge" => "Better Sleep",
        "description" => "Create a calmer, more regular sleep routine.",
        "icon_type" => "SLEEP"
    ],
    [
        "id" => "6",
        "title" => "General Wellness",
        "badge" => "General Wellness",
        "description" => "Find balance in your daily wellbeing.",
        "icon_type" => "WELLNESS"
    ]
];

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: Fetch user's health goals
// ==========================================
if ($method === "GET") {
    $userId = resolveUser($conn, $_GET);

    if ($userId <= 0) {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "user_id or email is required"
        ]);
    }

    $stmt = $conn->prepare(
        "SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at
         FROM user_health_goals
         WHERE user_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $goalRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // If no row exists, initialize from users or defaults
    if (!$goalRow) {
        $uStmt = $conn->prepare("SELECT primary_goal FROM users WHERE id = ? LIMIT 1");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
        $primary = $uRow["primary_goal"] ?? "Improve Fitness";

        $insStmt = $conn->prepare("INSERT INTO user_health_goals (user_id, primary_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE primary_goal = VALUES(primary_goal)");
        $insStmt->bind_param("is", $userId, $primary);
        $insStmt->execute();
        $insStmt->close();

        $stmt2 = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $goalRow = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    }

    $conn->close();

    $currentGoalTitle = $goalRow["primary_goal"] ?: "Improve Fitness";
    $selectedId = "1";
    foreach ($availableGoals as $g) {
        if (strcasecmp($g["title"], $currentGoalTitle) === 0 || strcasecmp($g["badge"], $currentGoalTitle) === 0) {
            $selectedId = $g["id"];
            break;
        }
    }

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Health goals retrieved successfully",
        "selected_goal_id" => $selectedId,
        "selected_goal_title" => $currentGoalTitle,
        "primary_goal" => $currentGoalTitle,
        "goals" => [
            "user_id" => (int)$goalRow["user_id"],
            "primary_goal" => $goalRow["primary_goal"],
            "target_steps" => (int)$goalRow["target_steps"],
            "target_sleep_hours" => (float)$goalRow["target_sleep_hours"],
            "target_water_ml" => (int)$goalRow["target_water_ml"],
            "target_weight_kg" => $goalRow["target_weight_kg"] !== null ? (float)$goalRow["target_weight_kg"] : null,
            "target_calories" => (int)$goalRow["target_calories"],
            "updated_at" => $goalRow["updated_at"]
        ],
        "available_goals" => $availableGoals
    ]);
}

// ==========================================
// 2. POST / PUT: Update health goals
// ==========================================
if ($method === "POST" || $method === "PUT") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = resolveUser($conn, $data);

    if ($userId <= 0) {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "user_id or email is required"
        ]);
    }

    // Resolve goal title
    $goalId = trim((string)($data["goal_id"] ?? ""));
    $primaryGoal = trim((string)($data["primary_goal"] ?? $data["goal_title"] ?? ""));
    if ($goalId !== "") {
        foreach ($availableGoals as $g) {
            if ($g["id"] === $goalId) {
                $primaryGoal = $g["badge"];
                break;
            }
        }
    }

    $targetSteps = isset($data["target_steps"]) && is_numeric($data["target_steps"]) ? (int)$data["target_steps"] : null;
    $targetSleepHours = isset($data["target_sleep_hours"]) && is_numeric($data["target_sleep_hours"]) ? (float)$data["target_sleep_hours"] : (isset($data["sleep_hours"]) ? (float)$data["sleep_hours"] : null);
    $targetWaterMl = isset($data["target_water_ml"]) && is_numeric($data["target_water_ml"]) ? (int)$data["target_water_ml"] : (isset($data["water_ml"]) ? (int)$data["water_ml"] : null);
    $targetWeightKg = isset($data["target_weight_kg"]) && is_numeric($data["target_weight_kg"]) ? (float)$data["target_weight_kg"] : (isset($data["weight_kg"]) ? (float)$data["weight_kg"] : null);
    $targetCalories = isset($data["target_calories"]) && is_numeric($data["target_calories"]) ? (int)$data["target_calories"] : (isset($data["calories"]) ? (int)$data["calories"] : null);

    if (empty($primaryGoal)) {
        $primaryGoal = "Improve Fitness";
    }

    // 1. Upsert into user_health_goals
    $sql = "INSERT INTO user_health_goals (user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories)
            VALUES (?, ?, COALESCE(?, 10000), COALESCE(?, 8.0), COALESCE(?, 2500), ?, COALESCE(?, 2200))
            ON DUPLICATE KEY UPDATE
                primary_goal = VALUES(primary_goal),
                target_steps = COALESCE(VALUES(target_steps), target_steps),
                target_sleep_hours = COALESCE(VALUES(target_sleep_hours), target_sleep_hours),
                target_water_ml = COALESCE(VALUES(target_water_ml), target_water_ml),
                target_weight_kg = COALESCE(VALUES(target_weight_kg), target_weight_kg),
                target_calories = COALESCE(VALUES(target_calories), target_calories)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(500, [
            "success" => false,
            "status" => "error",
            "message" => "Database statement preparation failed: " . $conn->error
        ]);
    }

    $stmt->bind_param(
        "isididi",
        $userId,
        $primaryGoal,
        $targetSteps,
        $targetSleepHours,
        $targetWaterMl,
        $targetWeightKg,
        $targetCalories
    );

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(500, [
            "success" => false,
            "status" => "error",
            "message" => "Failed to update health goals"
        ]);
    }
    $stmt->close();

    // 2. Also update primary_goal in users table
    $uUp = $conn->prepare("UPDATE users SET primary_goal = ? WHERE id = ?");
    if ($uUp) {
        $uUp->bind_param("si", $primaryGoal, $userId);
        $uUp->execute();
        $uUp->close();
    }

    // 3. Also sync target_steps to activity_goals if present
    if ($targetSteps !== null) {
        try {
            $ag = $conn->prepare("INSERT INTO activity_goals (user_id, daily_step_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_step_goal = VALUES(daily_step_goal)");
            if ($ag) {
                $ag->bind_param("ii", $userId, $targetSteps);
                $ag->execute();
                $ag->close();
            }
        } catch (\Exception $ex) {}
    }

    // 4. Also sync target_water_ml to hydration_goals if present
    if ($targetWaterMl !== null) {
        try {
            $hg = $conn->prepare("INSERT INTO hydration_goals (user_id, daily_target_ml) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_target_ml = VALUES(daily_target_ml)");
            if ($hg) {
                $hg->bind_param("ii", $userId, $targetWaterMl);
                $hg->execute();
                $hg->close();
            }
        } catch (\Exception $ex) {}
    }

    // Fetch updated row
    $fetchStmt = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
    $fetchStmt->bind_param("i", $userId);
    $fetchStmt->execute();
    $updated = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Health goals updated successfully",
        "primary_goal" => $updated["primary_goal"],
        "goals" => [
            "user_id" => (int)$updated["user_id"],
            "primary_goal" => $updated["primary_goal"],
            "target_steps" => (int)$updated["target_steps"],
            "target_sleep_hours" => (float)$updated["target_sleep_hours"],
            "target_water_ml" => (int)$updated["target_water_ml"],
            "target_weight_kg" => $updated["target_weight_kg"] !== null ? (float)$updated["target_weight_kg"] : null,
            "target_calories" => (int)$updated["target_calories"],
            "updated_at" => $updated["updated_at"]
        ]
    ]);
}

respond(405, [
    "success" => false,
    "status" => "error",
    "message" => "Method not allowed. Use GET, POST, or PUT."
]);
