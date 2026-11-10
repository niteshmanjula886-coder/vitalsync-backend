<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tunnel-Skip-Anti-Abuse");

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

function buildProfileResponse($user, $userProfile = null) {
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $baseUrl = $scheme . "://" . $host . "/vitalsync-backend/";

    $rawAvatar = !empty($userProfile["avatar_url"]) ? $userProfile["avatar_url"] : ($user["profile_image"] ?? null);
    $imageUrl = !empty($rawAvatar) ? (preg_match('#^https?://#i', $rawAvatar) ? $rawAvatar : $baseUrl . ltrim($rawAvatar, "/")) : null;

    $dob = $user["dob"] ?? null;
    $age = $userProfile["age"] ?? ($user["age"] !== null ? (int)$user["age"] : null);
    if ($age === null && !empty($dob)) {
        if (preg_match('/\b(19\d\d|20\d\d)\b/', $dob, $matches)) {
            $birthYear = (int)$matches[1];
            $currentYear = (int)date("Y");
            $age = max(1, min(120, $currentYear - $birthYear));
        }
    }

    $height = ($userProfile["height_cm"] ?? null) !== null ? (float)$userProfile["height_cm"] : (($user["height"] ?? null) !== null ? (float)$user["height"] : null);
    $weight = ($userProfile["weight_kg"] ?? null) !== null ? (float)$userProfile["weight_kg"] : (($user["weight"] ?? null) !== null ? (float)$user["weight"] : null);
    $gender = ($userProfile["gender"] ?? null) ?: (($user["gender"] ?? null) ?: "Not Specified");
    $phone = ($userProfile["phone"] ?? null) ?: ($user["phone"] ?? null);
    $bloodGroup = $userProfile["blood_group"] ?? null;
    $fullName = ($userProfile["full_name"] ?? null) ?: ($user["full_name"] ?? "");

    return [
        "id" => (int)$user["id"],
        "user_id" => (int)$user["id"],
        "name" => $fullName,
        "full_name" => $fullName,
        "email" => $user["email"],
        "phone" => $phone,
        "dob" => $user["dob"],
        "age" => $age,
        "gender" => $gender,
        "height" => $height,
        "height_cm" => $height,
        "weight" => $weight,
        "weight_kg" => $weight,
        "blood_group" => $bloodGroup,
        "activity_level" => $user["activity_level"],
        "primary_goal" => $user["primary_goal"] ?? "Fitness",
        "step_goal" => isset($user["step_goal"]) && (int)$user["step_goal"] > 0 ? (int)$user["step_goal"] : null,
        "profile_image" => $user["profile_image"],
        "image_url" => $imageUrl,
        "avatar_url" => $imageUrl,
        "created_at" => $user["created_at"],
        "updated_at" => $userProfile["updated_at"] ?? $user["updated_at"]
    ];
}

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: Fetch Profile, Health Goals & App Settings
// ==========================================
if ($method === "GET") {
    $email = strtolower(trim($_GET["email"] ?? ""));
    $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);

    if ($userId <= 0 && $email === "") {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "Please provide user_id or email in the query parameters"
        ]);
    }

    if ($userId > 0) {
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        respond(404, [
            "success" => false,
            "status" => "error",
            "message" => "User not found"
        ]);
    }

    $userId = (int)$user["id"];

    // 2. Fetch from user_profiles
    $pStmt = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
    $pStmt->bind_param("i", $userId);
    $pStmt->execute();
    $uProf = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    // Auto-initialize user_profiles if missing
    if (!$uProf) {
        $initP = $conn->prepare("INSERT INTO user_profiles (user_id, full_name, age, gender, height_cm, weight_kg, phone, avatar_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
        $initP->bind_param("isisddss", $userId, $user["full_name"], $user["age"], $user["gender"], $user["height"], $user["weight"], $user["phone"], $user["profile_image"]);
        $initP->execute();
        $initP->close();

        $pStmt2 = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
        $pStmt2->bind_param("i", $userId);
        $pStmt2->execute();
        $uProf = $pStmt2->get_result()->fetch_assoc();
        $pStmt2->close();
    }

    // 3. Fetch from user_health_goals
    $gStmt = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
    $gStmt->bind_param("i", $userId);
    $gStmt->execute();
    $uGoals = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();

    if (!$uGoals) {
        $primary = !empty($user["primary_goal"]) ? $user["primary_goal"] : null;
        $initG = $conn->prepare("INSERT INTO user_health_goals (user_id, primary_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE primary_goal = VALUES(primary_goal)");
        $initG->bind_param("is", $userId, $primary);
        $initG->execute();
        $initG->close();

        $gStmt2 = $conn->prepare("SELECT user_id, primary_goal, target_steps, target_sleep_hours, target_water_ml, target_weight_kg, target_calories, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
        $gStmt2->bind_param("i", $userId);
        $gStmt2->execute();
        $uGoals = $gStmt2->get_result()->fetch_assoc();
        $gStmt2->close();
    }

    // 4. Fetch from user_app_settings
    $sStmt = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
    $sStmt->bind_param("i", $userId);
    $sStmt->execute();
    $uSettings = $sStmt->get_result()->fetch_assoc();
    $sStmt->close();

    if (!$uSettings) {
        $initS = $conn->prepare("INSERT INTO user_app_settings (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id = user_id");
        $initS->bind_param("i", $userId);
        $initS->execute();
        $initS->close();

        $sStmt2 = $conn->prepare("SELECT user_id, push_notifications, vital_alerts, daily_reminders, metric_units, dark_mode, data_sync_interval, updated_at FROM user_app_settings WHERE user_id = ? LIMIT 1");
        $sStmt2->bind_param("i", $userId);
        $sStmt2->execute();
        $uSettings = $sStmt2->get_result()->fetch_assoc();
        $sStmt2->close();
    }

    // Merge latest step_goal from activity_goals & user_health_goals
    $agGoal = 0;
    $agStmt = $conn->prepare("SELECT daily_step_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
    if ($agStmt) {
        $agStmt->bind_param("i", $userId);
        $agStmt->execute();
        $agR = $agStmt->get_result()->fetch_assoc();
        $agStmt->close();
        if (!empty($agR["daily_step_goal"]) && (int)$agR["daily_step_goal"] > 0) {
            $agGoal = (int)$agR["daily_step_goal"];
        }
    }
    $resolvedStepGoal = ($agGoal > 0) ? $agGoal : ((int)($uGoals["target_steps"] ?? 8000) > 0 ? (int)$uGoals["target_steps"] : 8000);
    $user["step_goal"] = $resolvedStepGoal;

    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Profile fetched successfully",
        "profile" => buildProfileResponse($user, $uProf),
        "health_goals" => [
            "user_id" => (int)$uGoals["user_id"],
            "primary_goal" => $uGoals["primary_goal"],
            "step_goal" => $resolvedStepGoal,
            "target_steps" => $resolvedStepGoal,
            "target_sleep_hours" => (float)$uGoals["target_sleep_hours"],
            "target_water_ml" => (int)$uGoals["target_water_ml"],
            "target_weight_kg" => $uGoals["target_weight_kg"] !== null ? (float)$uGoals["target_weight_kg"] : null,
            "target_calories" => (int)$uGoals["target_calories"],
            "updated_at" => $uGoals["updated_at"]
        ],
        "app_settings" => [
            "user_id" => (int)$uSettings["user_id"],
            "push_notifications" => (bool)$uSettings["push_notifications"],
            "vital_alerts" => (bool)$uSettings["vital_alerts"],
            "daily_reminders" => (bool)$uSettings["daily_reminders"],
            "metric_units" => $uSettings["metric_units"] ?: "metric",
            "dark_mode" => (bool)$uSettings["dark_mode"],
            "data_sync_interval" => (int)($uSettings["data_sync_interval"] ?? 15),
            "updated_at" => $uSettings["updated_at"]
        ]
    ]);
}

// ==========================================
// 2. POST / PUT: Update Profile
// ==========================================
if ($method === "POST" || $method === "PUT") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = isset($data["user_id"]) ? (int)$data["user_id"] : (isset($data["id"]) ? (int)$data["id"] : (isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0)));
    $email = strtolower(trim($data["email"] ?? $_GET["email"] ?? ""));

    if ($userId <= 0 && $email === "") {
        respond(400, [
            "success" => false,
            "status" => "error",
            "message" => "user_id or email is required"
        ]);
    }

    // Verify existing user
    if ($userId > 0) {
        $checkStmt = $conn->prepare("SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal FROM users WHERE id = ? LIMIT 1");
        $checkStmt->bind_param("i", $userId);
    } else {
        $checkStmt = $conn->prepare("SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal FROM users WHERE email = ? LIMIT 1");
        $checkStmt->bind_param("s", $email);
    }
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$existing) {
        respond(404, [
            "success" => false,
            "status" => "error",
            "message" => "User not found"
        ]);
    }

    $targetUserId = (int)$existing["id"];

    // Parse parameters
    $name = isset($data["name"]) || isset($data["full_name"]) ? trim($data["name"] ?? $data["full_name"]) : $existing["full_name"];
    $phone = isset($data["phone"]) ? trim((string)$data["phone"]) : $existing["phone"];
    $gender = isset($data["gender"]) ? trim((string)$data["gender"]) : ($existing["gender"] ?: "Not Specified");
    $bloodGroup = isset($data["blood_group"]) ? strtoupper(trim((string)$data["blood_group"])) : null;
    $dob = isset($data["dob"]) ? trim((string)$data["dob"]) : $existing["dob"];

    $age = isset($data["age"]) && is_numeric($data["age"]) ? (int)$data["age"] : ($existing["age"] !== null ? (int)$existing["age"] : 24);
    if (!isset($data["age"]) && !empty($dob)) {
        if (preg_match('/\b(19\d\d|20\d\d)\b/', $dob, $matches)) {
            $birthYear = (int)$matches[1];
            $currentYear = (int)date("Y");
            $age = max(1, min(120, $currentYear - $birthYear));
        }
    }

    $height = null;
    if (isset($data["height_cm"]) && is_numeric($data["height_cm"])) {
        $height = (float)$data["height_cm"];
    } elseif (isset($data["height"]) && is_numeric($data["height"])) {
        $height = (float)$data["height"];
    } elseif ($existing["height"] !== null) {
        $height = (float)$existing["height"];
    } else {
        $height = null;
    }

    $weight = null;
    if (isset($data["weight_kg"]) && is_numeric($data["weight_kg"])) {
        $weight = (float)$data["weight_kg"];
    } elseif (isset($data["weight"]) && is_numeric($data["weight"])) {
        $weight = (float)$data["weight"];
    } elseif ($existing["weight"] !== null) {
        $weight = (float)$existing["weight"];
    } else {
        $weight = null;
    }

    $activityLevel = isset($data["activity_level"]) ? trim((string)$data["activity_level"]) : $existing["activity_level"];
    $primaryGoal = isset($data["primary_goal"]) ? trim((string)$data["primary_goal"]) : $existing["primary_goal"];

    // 1. Update users table
    $uUp = $conn->prepare(
        "UPDATE users SET full_name = ?, phone = ?, dob = ?, age = ?, gender = ?, height = ?, weight = ?, activity_level = ?, primary_goal = ? WHERE id = ?"
    );
    $uUp->bind_param("sssisddssi", $name, $phone, $dob, $age, $gender, $height, $weight, $activityLevel, $primaryGoal, $targetUserId);
    $uUp->execute();
    $uUp->close();

    // 2. Upsert into user_profiles table
    $avatarUrl = isset($data["avatar_url"]) ? trim((string)$data["avatar_url"]) : null;
    $pUp = $conn->prepare(
        "INSERT INTO user_profiles (user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             full_name = VALUES(full_name),
             age = VALUES(age),
             gender = VALUES(gender),
             height_cm = VALUES(height_cm),
             weight_kg = VALUES(weight_kg),
             blood_group = COALESCE(VALUES(blood_group), blood_group),
             phone = VALUES(phone),
             avatar_url = COALESCE(VALUES(avatar_url), avatar_url)"
    );
    $pUp->bind_param("isisddsss", $targetUserId, $name, $age, $gender, $height, $weight, $bloodGroup, $phone, $avatarUrl);
    $pUp->execute();
    $pUp->close();

    // 3. Optional step_goal update across activity_goals, user_health_goals, and users.step_goal (if present)
    $stepGoalParam = null;
    if (isset($data["step_goal"]) && is_numeric($data["step_goal"])) {
        $stepGoalParam = (int)$data["step_goal"];
    } elseif (isset($data["target_steps"]) && is_numeric($data["target_steps"])) {
        $stepGoalParam = (int)$data["target_steps"];
    } elseif (isset($data["daily_step_goal"]) && is_numeric($data["daily_step_goal"])) {
        $stepGoalParam = (int)$data["daily_step_goal"];
    }
    if ($stepGoalParam !== null && $stepGoalParam >= 1000 && $stepGoalParam <= 100000) {
        $agUp = $conn->prepare("INSERT INTO activity_goals (user_id, daily_step_goal) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_step_goal = VALUES(daily_step_goal)");
        if ($agUp) {
            $agUp->bind_param("ii", $targetUserId, $stepGoalParam);
            $agUp->execute();
            $agUp->close();
        }
        $hgUp = $conn->prepare("INSERT INTO user_health_goals (user_id, target_steps) VALUES (?, ?) ON DUPLICATE KEY UPDATE target_steps = VALUES(target_steps)");
        if ($hgUp) {
            $hgUp->bind_param("ii", $targetUserId, $stepGoalParam);
            $hgUp->execute();
            $hgUp->close();
        }
        try {
            $chkSg = $conn->query("SHOW COLUMNS FROM users LIKE 'step_goal'");
            if ($chkSg && $chkSg->num_rows > 0) {
                $usUp = $conn->prepare("UPDATE users SET step_goal = ? WHERE id = ?");
                if ($usUp) {
                    $usUp->bind_param("ii", $stepGoalParam, $targetUserId);
                    $usUp->execute();
                    $usUp->close();
                }
            }
        } catch (\Throwable $e) {}
    }

    // Fetch updated records
    $fetchU = $conn->prepare("SELECT id, full_name, email, phone, dob, age, gender, height, weight, activity_level, primary_goal, profile_image, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
    $fetchU->bind_param("i", $targetUserId);
    $fetchU->execute();
    $updatedUser = $fetchU->get_result()->fetch_assoc();
    $fetchU->close();

    if ($stepGoalParam !== null && $stepGoalParam >= 1000) {
        $updatedUser["step_goal"] = $stepGoalParam;
    } else {
        $agF = $conn->prepare("SELECT daily_step_goal FROM activity_goals WHERE user_id = ? LIMIT 1");
        if ($agF) {
            $agF->bind_param("i", $targetUserId);
            $agF->execute();
            $agFr = $agF->get_result()->fetch_assoc();
            $agF->close();
            if (!empty($agFr["daily_step_goal"]) && (int)$agFr["daily_step_goal"] > 0) {
                $updatedUser["step_goal"] = (int)$agFr["daily_step_goal"];
            }
        }
    }

    $fetchP = $conn->prepare("SELECT user_id, full_name, age, gender, height_cm, weight_kg, blood_group, phone, avatar_url, updated_at FROM user_profiles WHERE user_id = ? LIMIT 1");
    $fetchP->bind_param("i", $targetUserId);
    $fetchP->execute();
    $updatedProf = $fetchP->get_result()->fetch_assoc();
    $fetchP->close();
    $conn->close();

    respond(200, [
        "success" => true,
        "status" => "success",
        "message" => "Profile updated successfully",
        "step_goal" => $updatedUser["step_goal"] ?? null,
        "profile" => buildProfileResponse($updatedUser, $updatedProf)
    ]);
}

respond(405, [
    "success" => false,
    "status" => "error",
    "message" => "Method not allowed. Use GET, POST, or PUT."
]);
