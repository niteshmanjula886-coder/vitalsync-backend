<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$availableGoals = [
    [
        "id" => "1",
        "title" => "Fitness",
        "badge" => "Improve Fitness",
        "description" => "Build a consistent routine and improve fitness.",
        "icon_type" => "FITNESS"
    ],
    [
        "id" => "2",
        "title" => "Better Sleep",
        "badge" => "Better Sleep",
        "description" => "Create a calmer, more regular sleep routine.",
        "icon_type" => "SLEEP"
    ],
    [
        "id" => "3",
        "title" => "Weight Management",
        "badge" => "Weight Management",
        "description" => "Track changes and build sustainable habits.",
        "icon_type" => "WEIGHT"
    ],
    [
        "id" => "4",
        "title" => "General Wellness",
        "badge" => "General Wellness",
        "description" => "Find balance in your daily wellbeing.",
        "icon_type" => "WELLNESS"
    ]
];

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: Fetch user's current health goal & available goals
// ==========================================
if ($method === "GET") {
    $email = strtolower(trim($_GET["email"] ?? ""));
    $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);

    $currentGoalTitle = "Fitness";

    if ($userId > 0 || $email !== "") {
        if ($userId > 0) {
            $stmt = $conn->prepare("SELECT primary_goal FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $conn->prepare("SELECT primary_goal FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                if (!empty($user["primary_goal"])) {
                    $currentGoalTitle = $user["primary_goal"];
                }
            }
            $stmt->close();
        }
    }

    // Match selected goal ID
    $selectedId = "1";
    foreach ($availableGoals as $g) {
        if (strcasecmp($g["title"], $currentGoalTitle) === 0 || strcasecmp($g["badge"], $currentGoalTitle) === 0) {
            $selectedId = $g["id"];
            break;
        }
    }

    $conn->close();

    respond(200, [
        "success" => true,
        "selected_goal_id" => $selectedId,
        "selected_goal_title" => $currentGoalTitle,
        "available_goals" => $availableGoals
    ]);
}

// ==========================================
// 2. POST: Update user's health goal
// ==========================================
if ($method === "POST") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = isset($data["user_id"]) ? (int)$data["user_id"] : (isset($data["id"]) ? (int)$data["id"] : 0);
    $email = strtolower(trim($data["email"] ?? ""));
    $goalId = trim((string)($data["goal_id"] ?? ""));
    $goalTitle = trim((string)($data["goal_title"] ?? $data["primary_goal"] ?? ""));

    if ($userId <= 0 && $email === "") {
        respond(400, [
            "success" => false,
            "message" => "user_id or email is required"
        ]);
    }

    // Resolve goal title
    if ($goalId !== "") {
        foreach ($availableGoals as $g) {
            if ($g["id"] === $goalId) {
                $goalTitle = $g["badge"];
                break;
            }
        }
    }

    if ($goalTitle === "") {
        respond(400, [
            "success" => false,
            "message" => "Please specify goal_id (1-4) or goal_title"
        ]);
    }

    if ($userId > 0) {
        $stmt = $conn->prepare("UPDATE users SET primary_goal = ? WHERE id = ?");
        $stmt->bind_param("si", $goalTitle, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET primary_goal = ? WHERE email = ?");
        $stmt->bind_param("ss", $goalTitle, $email);
    }

    if (!$stmt) {
        respond(500, [
            "success" => false,
            "message" => "Database statement preparation failed: " . $conn->error
        ]);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(500, [
            "success" => false,
            "message" => "Failed to update health goal"
        ]);
    }

    $stmt->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "message" => "Health goal updated successfully",
        "primary_goal" => $goalTitle
    ]);
}

respond(405, [
    "success" => false,
    "message" => "POST or GET method required"
]);

