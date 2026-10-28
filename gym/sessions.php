<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, session_name, split_type, date_string, timestamp_formatted, duration_minutes, total_volume_kg, estimated_calories_burned 
         FROM workout_sessions 
         WHERE user_id = ? 
         ORDER BY date_string DESC, id DESC LIMIT 50"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sRes = $stmt->get_result();

    $sessions = [];
    while ($s = $sRes->fetch_assoc()) {
        $sessionId = (int)$s["id"];

        // Load exercises for this session
        $exStmt = $conn->prepare(
            "SELECT id, exercise_name, target_muscle, notes 
             FROM workout_session_exercises 
             WHERE workout_session_id = ? 
             ORDER BY id ASC"
        );
        $exStmt->bind_param("i", $sessionId);
        $exStmt->execute();
        $exRes = $exStmt->get_result();

        $exercises = [];
        while ($ex = $exRes->fetch_assoc()) {
            $sessionExId = (int)$ex["id"];

            // Load sets
            $setStmt = $conn->prepare(
                "SELECT set_number, reps, weight_kg, is_completed 
                 FROM workout_exercise_sets 
                 WHERE session_exercise_id = ? 
                 ORDER BY set_number ASC"
            );
            $setStmt->bind_param("i", $sessionExId);
            $setStmt->execute();
            $setRes = $setStmt->get_result();

            $sets = [];
            while ($st = $setRes->fetch_assoc()) {
                $sets[] = [
                    "setNumber" => (int)$st["set_number"],
                    "reps" => (int)$st["reps"],
                    "weightKg" => (float)$st["weight_kg"],
                    "isCompleted" => (bool)$st["is_completed"]
                ];
            }
            $setStmt->close();

            $exercises[] = [
                "exercise" => [
                    "id" => "ex_" . $sessionExId,
                    "name" => $ex["exercise_name"],
                    "targetMuscle" => $ex["target_muscle"],
                    "notes" => $ex["notes"] ?? ""
                ],
                "sets" => $sets,
                "notes" => $ex["notes"] ?? ""
            ];
        }
        $exStmt->close();

        $sessions[] = [
            "id" => (string)$sessionId,
            "name" => $s["session_name"],
            "split" => $s["split_type"],
            "dateString" => $s["date_string"],
            "timestampFormatted" => $s["timestamp_formatted"],
            "durationMinutes" => (int)$s["duration_minutes"],
            "totalVolumeKg" => (float)$s["total_volume_kg"],
            "estimatedCaloriesBurned" => (int)$s["estimated_calories_burned"],
            "exercises" => $exercises
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($sessions),
        "workout_sessions" => $sessions
    ]);
}

if ($method === "POST") {
    $data = getInputData();

    $name = trim($data["name"] ?? $data["session_name"] ?? "Workout Session");
    $split = strtoupper(trim((string)($data["split"] ?? $data["split_type"] ?? "PUSH")));
    $dateString = !empty($data["dateString"]) ? trim((string)$data["dateString"]) : (!empty($data["date_string"]) ? trim((string)$data["date_string"]) : date("Y-m-d"));
    $timestampFormatted = !empty($data["timestampFormatted"]) ? trim((string)$data["timestampFormatted"]) : date("M d, Y \\a\\t h:i A");
    $duration = isset($data["durationMinutes"]) ? (int)$data["durationMinutes"] : (isset($data["duration_minutes"]) ? (int)$data["duration_minutes"] : 45);
    $volume = isset($data["totalVolumeKg"]) ? (float)$data["totalVolumeKg"] : (isset($data["total_volume_kg"]) ? (float)$data["total_volume_kg"] : 0.0);
    $calories = isset($data["estimatedCaloriesBurned"]) ? (int)$data["estimatedCaloriesBurned"] : (isset($data["estimated_calories_burned"]) ? (int)$data["estimated_calories_burned"] : 280);
    $exercises = isset($data["exercises"]) && is_array($data["exercises"]) ? $data["exercises"] : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "INSERT INTO workout_sessions (user_id, session_name, split_type, date_string, timestamp_formatted, duration_minutes, total_volume_kg, estimated_calories_burned)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssidi", $userId, $name, $split, $dateString, $timestampFormatted, $duration, $volume, $calories);
        $stmt->execute();
        $sessionId = $stmt->insert_id;
        $stmt->close();

        // Insert exercises and sets
        foreach ($exercises as $we) {
            $exData = $we["exercise"] ?? $we;
            $exName = trim($exData["name"] ?? "Exercise");
            $targetMuscle = trim($exData["targetMuscle"] ?? $exData["target_muscle"] ?? "General");
            $notes = trim($we["notes"] ?? "");

            $exStmt = $conn->prepare(
                "INSERT INTO workout_session_exercises (workout_session_id, exercise_name, target_muscle, notes)
                 VALUES (?, ?, ?, ?)"
            );
            $exStmt->bind_param("isss", $sessionId, $exName, $targetMuscle, $notes);
            $exStmt->execute();
            $sessionExId = $exStmt->insert_id;
            $exStmt->close();

            $sets = isset($we["sets"]) && is_array($we["sets"]) ? $we["sets"] : [];
            $setStmt = $conn->prepare(
                "INSERT INTO workout_exercise_sets (session_exercise_id, set_number, reps, weight_kg, is_completed)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($sets as $st) {
                $setNum = isset($st["setNumber"]) ? (int)$st["setNumber"] : (isset($st["set_number"]) ? (int)$st["set_number"] : 1);
                $reps = (int)($st["reps"] ?? 10);
                $weight = isset($st["weightKg"]) ? (float)$st["weightKg"] : (isset($st["weight_kg"]) ? (float)$st["weight_kg"] : 20.0);
                $isComp = isset($st["isCompleted"]) ? ($st["isCompleted"] ? 1 : 0) : (isset($st["is_completed"]) ? ($st["is_completed"] ? 1 : 0) : 1);

                $setStmt->bind_param("iiidi", $sessionExId, $setNum, $reps, $weight, $isComp);
                $setStmt->execute();
            }
            $setStmt->close();
        }

        $conn->commit();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Workout session saved successfully",
            "session_id" => $sessionId,
            "name" => $name,
            "split" => $split,
            "total_volume_kg" => $volume,
            "duration_minutes" => $duration
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();

        respondJson(500, [
            "success" => false,
            "message" => "Failed to save workout session: " . $e->getMessage()
        ]);
    }
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

