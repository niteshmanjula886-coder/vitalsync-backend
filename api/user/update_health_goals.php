<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../config/db.php";

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
    ]
];

$method = $_SERVER["REQUEST_METHOD"];

// GET: Retrieve user's current health goal
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

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Health goals retrieved successfully",
        "primary_goal" => $currentGoalTitle,
        "selected_goal" => $currentGoalTitle,
        "goals" => [
            "user_id" => (int)$goalRow["user_id"],
            "primary_goal" => $currentGoalTitle,
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

// POST or PUT: Update health goal
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

    $goalId = trim((string)($data["goal_id"] ?? ""));
    $primaryGoal = trim((string)($data["primary_goal"] ?? $data["goal_title"] ?? $data["selected_goal"] ?? $data["goal"] ?? ""));

    if ($goalId !== "" && empty($primaryGoal)) {
        foreach ($availableGoals as $g) {
            if ($g["id"] === $goalId) {
                $primaryGoal = $g["badge"];
                break;
            }
        }
    }

    if (empty($primaryGoal)) {
        $primaryGoal = "Improve Fitness";
    }

    // 1. Upsert user_health_goals
    $stmt = $conn->prepare("INSERT INTO user_health_goals (user_id, primary_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE primary_goal = VALUES(primary_goal)");
    $stmt->bind_param("is", $userId, $primaryGoal);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(500, [
            "success" => false,
            "status" => "error",
            "message" => "Failed to update user_health_goals"
        ]);
    }
    $stmt->close();

    // 2. Update users table primary_goal
    $uUp = $conn->prepare("UPDATE users SET primary_goal = ? WHERE id = ?");
    if ($uUp) {
        $uUp->bind_param("si", $primaryGoal, $userId);
        $uUp->execute();
        $uUp->close();
    }

    // Return success response
    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Health goal updated successfully",
        "primary_goal" => $primaryGoal,
        "selected_goal" => $primaryGoal,
        "data" => [
            "user_id" => $userId,
            "primary_goal" => $primaryGoal
        ]
    ]);
}

respond(405, [
    "success" => false,
    "status" => "error",
    "message" => "Method not allowed. Use GET, POST, or PUT."
]);

