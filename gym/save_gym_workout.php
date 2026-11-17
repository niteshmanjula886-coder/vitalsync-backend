<?php

header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

// Ensure tables exist on first run
$conn->query("
    CREATE TABLE IF NOT EXISTS gym_workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        split_name VARCHAR(100) NOT NULL,
        duration_minutes INT NOT NULL DEFAULT 0,
        total_volume_kg FLOAT NOT NULL DEFAULT 0,
        total_sets INT NOT NULL DEFAULT 0,
        calories_burned INT NOT NULL DEFAULT 0,
        exercises_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
    CREATE TABLE IF NOT EXISTS gym_workout_sets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workout_id INT NOT NULL,
        user_id INT NOT NULL,
        exercise_name VARCHAR(150) NOT NULL,
        target_muscle VARCHAR(100) NULL,
        set_number INT NOT NULL DEFAULT 1,
        weight_kg FLOAT NOT NULL DEFAULT 0,
        reps INT NOT NULL DEFAULT 0,
        is_completed TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_workout_id (workout_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$userId = resolveUserIdOrDie($conn, true);
$input = getInputData();

$splitName = trim($input["split_name"] ?? $input["split"] ?? "Push");
$durationMinutes = max(1, (int)($input["duration_minutes"] ?? $input["durationMinutes"] ?? 30));
$totalVolumeKg = (float)($input["total_volume_kg"] ?? $input["totalVolumeKg"] ?? 0.0);
<<<<<<< Updated upstream
// Normalize exercises payload and filter out incomplete / unchecked rows
$rawExercises = $input["exercises"] ?? [];
if (is_string($rawExercises)) {
    $rawExercises = json_decode($rawExercises, true) ?: [];
}
$completedExercises = [];
$calcCompletedSets = 0;
$calcVolumeKg = 0.0;
if (is_array($rawExercises)) {
    foreach ($rawExercises as $ex) {
        $sets = $ex["sets"] ?? [];
        $doneSets = [];
        if (is_array($sets)) {
            foreach ($sets as $st) {
                $isDone = (!empty($st["isCompleted"]) || !empty($st["is_completed"])) ? 1 : 0;
                if ($isDone === 1) {
                    $doneSets[] = $st;
                    $calcCompletedSets++;
                    $wKg = (float)($st["weightKg"] ?? $st["weight_kg"] ?? 0.0);
                    $reps = (int)($st["reps"] ?? 0);
                    $calcVolumeKg += ($wKg * $reps);
                }
            }
        }
        if (!empty($doneSets)) {
            $ex["sets"] = $doneSets;
            $completedExercises[] = $ex;
        }
    }
}
$exercises = $completedExercises;
$exercisesJson = json_encode($exercises, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$rawTotalSets = (int)($input["total_sets"] ?? $input["totalSets"] ?? 0);
$totalSets = ($rawTotalSets > 0) ? $rawTotalSets : $calcCompletedSets;

$rawVolumeKg = (float)($input["total_volume_kg"] ?? $input["totalVolumeKg"] ?? 0.0);
$totalVolumeKg = ($rawVolumeKg > 0.0) ? $rawVolumeKg : (float)$calcVolumeKg;

// Determine MET and User Body Weight for standard MET-based calorie calculation
// Formula: Calories Burned = (MET * 3.5 * weightKg / 200) * durationMinutes
$userWeight = 70.0;
$wStmt = $conn->prepare("SELECT weight FROM users WHERE id = ? LIMIT 1");
if ($wStmt) {
    $wStmt->bind_param("i", $userId);
    $wStmt->execute();
    $wRes = $wStmt->get_result();
    if ($wRow = $wRes->fetch_assoc()) {
        if (!empty($wRow["weight"]) && is_numeric($wRow["weight"]) && (float)$wRow["weight"] > 0) {
            $userWeight = (float)$wRow["weight"];
        }
    }
    $wStmt->close();
}

$met = 5.5; // Standard strength training MET
$metCalculatedCalories = max(1, (int)round(($met * 3.5 * $userWeight / 200.0) * $durationMinutes));
$rawCalories = (int)($input["calories_burned"] ?? $input["caloriesBurned"] ?? 0);
$caloriesBurned = ($rawCalories > 0) ? $rawCalories : $metCalculatedCalories;

=======
$totalSets = (int)($input["total_sets"] ?? $input["totalSets"] ?? 0);
$caloriesBurned = (int)($input["calories_burned"] ?? $input["caloriesBurned"] ?? round($durationMinutes * 6.5 + $totalVolumeKg * 0.02));

// Normalize exercises payload
$exercises = $input["exercises"] ?? [];
if (is_string($exercises)) {
    $exercises = json_decode($exercises, true) ?: [];
}
$exercisesJson = json_encode($exercises, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

>>>>>>> Stashed changes
// Insert into gym_workouts
$stmt = $conn->prepare("
    INSERT INTO gym_workouts (user_id, split_name, duration_minutes, total_volume_kg, total_sets, calories_burned, exercises_json, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database prepare error: " . $conn->error
    ]);
}

<<<<<<< Updated upstream
$stmt->bind_param("isidiis", $userId, $splitName, $durationMinutes, $totalVolumeKg, $totalSets, $caloriesBurned, $exercisesJson);
=======
$stmt->bind_param("isidids", $userId, $splitName, $durationMinutes, $totalVolumeKg, $totalSets, $caloriesBurned, $exercisesJson);
>>>>>>> Stashed changes
$stmt->execute();
$workoutId = $stmt->insert_id;
$stmt->close();

<<<<<<< Updated upstream
// Insert individual sets if provided (strictly completed only)
if ($workoutId > 0 && is_array($exercises) && !empty($exercises)) {
    $setStmt = $conn->prepare("
        INSERT INTO gym_workout_sets (workout_id, user_id, exercise_name, target_muscle, set_number, weight_kg, reps, is_completed, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
=======
// Insert individual sets if provided
if ($workoutId > 0 && is_array($exercises) && !empty($exercises)) {
    $setStmt = $conn->prepare("
        INSERT INTO gym_workout_sets (workout_id, user_id, exercise_name, target_muscle, set_number, weight_kg, reps, is_completed, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
>>>>>>> Stashed changes
    ");
    if ($setStmt) {
        foreach ($exercises as $ex) {
            $exName = trim($ex["exercise"]["name"] ?? $ex["name"] ?? "Exercise");
            $targetMuscle = trim($ex["exercise"]["targetMuscle"] ?? $ex["targetMuscle"] ?? "");
            $sets = $ex["sets"] ?? [];
            if (is_array($sets)) {
                foreach ($sets as $st) {
                    $setNum = (int)($st["setNumber"] ?? $st["set_number"] ?? 1);
                    $wKg = (float)($st["weightKg"] ?? $st["weight_kg"] ?? 0.0);
                    $reps = (int)($st["reps"] ?? 0);
<<<<<<< Updated upstream
                    $setStmt->bind_param("iissidi", $workoutId, $userId, $exName, $targetMuscle, $setNum, $wKg, $reps);
=======
                    $isDone = (!empty($st["isCompleted"]) || !empty($st["is_completed"])) ? 1 : 0;
                    $setStmt->bind_param("iissidii", $workoutId, $userId, $exName, $targetMuscle, $setNum, $wKg, $reps, $isDone);
>>>>>>> Stashed changes
                    $setStmt->execute();
                }
            }
        }
        $setStmt->close();
    }
}

// Synchronize with legacy workout_sessions table if it exists
try {
    $chk = $conn->query("SHOW TABLES LIKE 'workout_sessions'");
    if ($chk && $chk->num_rows > 0) {
        $legacyName = $splitName . " Routine";
        $dateStr = date("Y-m-d");
        $timeFmt = date("h:i A");
        $legStmt = $conn->prepare("
            INSERT INTO workout_sessions (user_id, session_name, split_type, date_string, timestamp_formatted, duration_minutes, total_volume_kg, estimated_calories_burned)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($legStmt) {
            $legStmt->bind_param("issssidi", $userId, $legacyName, $splitName, $dateStr, $timeFmt, $durationMinutes, $totalVolumeKg, $caloriesBurned);
            $legStmt->execute();
            $legStmt->close();
        }
    }
} catch (\Throwable $e) {
    // Ignore legacy sync failures
}

respondJson(200, [
    "success" => true,
    "message" => "Workout session saved successfully",
    "workout_id" => $workoutId,
    "data" => [
        "id" => $workoutId,
        "user_id" => $userId,
        "split_name" => $splitName,
        "duration_minutes" => $durationMinutes,
        "total_volume_kg" => $totalVolumeKg,
        "total_sets" => $totalSets,
        "calories_burned" => $caloriesBurned,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);
