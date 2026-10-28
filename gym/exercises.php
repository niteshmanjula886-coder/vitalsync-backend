<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    // Return default exercises (user_id IS NULL) + user's custom exercises (user_id = $userId)
    $stmt = $conn->prepare(
        "SELECT id, code, name, target_muscle, default_weight_kg, default_reps, notes, is_custom 
         FROM exercises 
         WHERE user_id IS NULL OR user_id = ? 
         ORDER BY is_custom ASC, id ASC"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $exercises = [];
    while ($r = $result->fetch_assoc()) {
        $exercises[] = [
            "id" => (string)($r["code"] ?: $r["id"]),
            "db_id" => (int)$r["id"],
            "name" => $r["name"],
            "targetMuscle" => $r["target_muscle"],
            "target_muscle" => $r["target_muscle"],
            "defaultWeightKg" => (float)$r["default_weight_kg"],
            "default_weight_kg" => (float)$r["default_weight_kg"],
            "defaultReps" => (int)$r["default_reps"],
            "default_reps" => (int)$r["default_reps"],
            "notes" => $r["notes"] ?? "",
            "isCustom" => (bool)$r["is_custom"]
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($exercises),
        "exercises" => $exercises
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $name = trim($data["name"] ?? "");
    $targetMuscle = trim($data["target_muscle"] ?? $data["targetMuscle"] ?? "");
    $weight = isset($data["default_weight_kg"]) ? (float)$data["default_weight_kg"] : (isset($data["defaultWeightKg"]) ? (float)$data["defaultWeightKg"] : 20.0);
    $reps = isset($data["default_reps"]) ? (int)$data["default_reps"] : (isset($data["defaultReps"]) ? (int)$data["defaultReps"] : 10);
    $notes = trim($data["notes"] ?? "");

    if ($name === "" || $targetMuscle === "") {
        respondJson(400, [
            "success" => false,
            "message" => "Exercise name and target muscle group are required"
        ]);
    }

    $code = "c_" . bin2hex(random_bytes(4));

    $stmt = $conn->prepare(
        "INSERT INTO exercises (user_id, code, name, target_muscle, default_weight_kg, default_reps, notes, is_custom)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("isssdis", $userId, $code, $name, $targetMuscle, $weight, $reps, $notes);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Custom exercise created successfully",
            "exercise" => [
                "id" => $code,
                "db_id" => $newId,
                "name" => $name,
                "targetMuscle" => $targetMuscle,
                "target_muscle" => $targetMuscle,
                "defaultWeightKg" => $weight,
                "default_weight_kg" => $weight,
                "defaultReps" => $reps,
                "default_reps" => $reps,
                "notes" => $notes,
                "isCustom" => true
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to create custom exercise: " . $stmt->error
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

