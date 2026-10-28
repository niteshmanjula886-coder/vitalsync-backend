<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

function resolveUserId($conn, $userId, $email) {
    if ($userId > 0) return $userId;
    if ($email !== "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return (int)$row["id"];
        }
    }
    return 0;
}

$method = $_SERVER["REQUEST_METHOD"];

// ==========================================
// 1. GET: List Reminders for User
// ==========================================
if ($method === "GET") {
    $email = strtolower(trim($_GET["email"] ?? ""));
    $userId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : (isset($_GET["id"]) ? (int)$_GET["id"] : 0);

    $uid = resolveUserId($conn, $userId, $email);
    if ($uid <= 0) {
        respond(400, [
            "success" => false,
            "message" => "Valid user_id or email required"
        ]);
    }

    // Fetch existing
    $stmt = $conn->prepare("SELECT * FROM reminders WHERE user_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $reminders = [];
    while ($row = $result->fetch_assoc()) {
        $reminders[] = [
            "id" => (int)$row["id"],
            "title" => $row["title"],
            "time" => $row["time"],
            "frequency" => $row["frequency"],
            "repeat_days" => $row["repeat_days"],
            "sound_vibration" => (bool)$row["sound_vibration"],
            "is_enabled" => (bool)$row["is_enabled"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }
    $stmt->close();

    // If no reminders exist yet, seed default standard reminders from the UI
    if (empty($reminders)) {
        $defaults = [
            ["Drink Water", "08:00 AM", "Every day", "0,1,2,3,4,5,6", 1, 1],
            ["Exercise", "06:30 PM", "Mon, Wed, Fri", "1,3,5", 1, 1],
            ["Sleep", "10:30 PM", "Every day", "0,1,2,3,4,5,6", 1, 1],
            ["Medication", "09:00 AM", "Every day", "0,1,2,3,4,5,6", 1, 1]
        ];

        $insertStmt = $conn->prepare(
            "INSERT INTO reminders (user_id, title, time, frequency, repeat_days, sound_vibration, is_enabled)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($defaults as $d) {
            $insertStmt->bind_param("issssii", $uid, $d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
            $insertStmt->execute();
            $newId = $insertStmt->insert_id;
            $reminders[] = [
                "id" => (int)$newId,
                "title" => $d[0],
                "time" => $d[1],
                "frequency" => $d[2],
                "repeat_days" => $d[3],
                "sound_vibration" => (bool)$d[4],
                "is_enabled" => (bool)$d[5],
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ];
        }
        $insertStmt->close();
    }

    $conn->close();

    respond(200, [
        "success" => true,
        "count" => count($reminders),
        "reminders" => $reminders
    ]);
}

// ==========================================
// 2. POST / PUT: Create or Update / Toggle Reminder
// ==========================================
if ($method === "POST" || $method === "PUT") {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $reminderId = isset($data["id"]) ? (int)$data["id"] : (isset($data["reminder_id"]) ? (int)$data["reminder_id"] : 0);
    $userId = isset($data["user_id"]) ? (int)$data["user_id"] : 0;
    $email = strtolower(trim($data["email"] ?? ""));

    $uid = resolveUserId($conn, $userId, $email);

    // If updating existing reminder
    if ($reminderId > 0) {
        $updates = [];
        $types = "";
        $params = [];

        if (isset($data["title"])) {
            $updates[] = "title = ?";
            $types .= "s";
            $params[] = trim((string)$data["title"]);
        }
        if (isset($data["time"])) {
            $updates[] = "time = ?";
            $types .= "s";
            $params[] = trim((string)$data["time"]);
        }
        if (isset($data["frequency"])) {
            $updates[] = "frequency = ?";
            $types .= "s";
            $params[] = trim((string)$data["frequency"]);
        }
        if (isset($data["repeat_days"])) {
            $updates[] = "repeat_days = ?";
            $types .= "s";
            $params[] = trim((string)$data["repeat_days"]);
        }
        if (isset($data["sound_vibration"])) {
            $updates[] = "sound_vibration = ?";
            $types .= "i";
            $params[] = $data["sound_vibration"] ? 1 : 0;
        }
        if (isset($data["is_enabled"])) {
            $updates[] = "is_enabled = ?";
            $types .= "i";
            $params[] = $data["is_enabled"] ? 1 : 0;
        }

        if (empty($updates)) {
            respond(400, [
                "success" => false,
                "message" => "No fields to update for reminder"
            ]);
        }

        $sql = "UPDATE reminders SET " . implode(", ", $updates) . " WHERE id = ?";
        $types .= "i";
        $params[] = $reminderId;

        if ($uid > 0) {
            $sql .= " AND user_id = ?";
            $types .= "i";
            $params[] = $uid;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        // Fetch updated reminder
        $fetch = $conn->prepare("SELECT * FROM reminders WHERE id = ? LIMIT 1");
        $fetch->bind_param("i", $reminderId);
        $fetch->execute();
        $updatedRow = $fetch->get_result()->fetch_assoc();
        $fetch->close();
        $conn->close();

        respond(200, [
            "success" => true,
            "message" => "Reminder updated successfully",
            "reminder" => $updatedRow
        ]);
    }

    // Creating new reminder
    if ($uid <= 0) {
        respond(400, [
            "success" => false,
            "message" => "user_id or email is required to create a reminder"
        ]);
    }

    $title = trim($data["title"] ?? $data["reminder_name"] ?? "");
    $time = trim($data["time"] ?? $data["selected_time"] ?? "08:00 AM");
    $frequency = trim($data["frequency"] ?? $data["selected_frequency"] ?? "Every day");
    $repeatDays = trim($data["repeat_days"] ?? "0,1,2,3,4,5,6");
    $soundVibration = isset($data["sound_vibration"]) ? ($data["sound_vibration"] ? 1 : 0) : 1;
    $isEnabled = isset($data["is_enabled"]) ? ($data["is_enabled"] ? 1 : 0) : 1;

    if ($title === "") {
        respond(400, [
            "success" => false,
            "message" => "Reminder title is required"
        ]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO reminders (user_id, title, time, frequency, repeat_days, sound_vibration, is_enabled)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssii", $uid, $title, $time, $frequency, $repeatDays, $soundVibration, $isEnabled);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respond(201, [
            "success" => true,
            "message" => "Reminder created successfully",
            "reminder" => [
                "id" => $newId,
                "user_id" => $uid,
                "title" => $title,
                "time" => $time,
                "frequency" => $frequency,
                "repeat_days" => $repeatDays,
                "sound_vibration" => (bool)$soundVibration,
                "is_enabled" => (bool)$isEnabled
            ]
        ]);
    }

    respond(500, [
        "success" => false,
        "message" => "Failed to create reminder: " . $stmt->error
    ]);
}

// ==========================================
// 3. DELETE: Remove Reminder
// ==========================================
if ($method === "DELETE") {
    $reminderId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    if ($reminderId <= 0) {
        $data = json_decode(file_get_contents("php://input"), true);
        $reminderId = isset($data["id"]) ? (int)$data["id"] : 0;
    }

    if ($reminderId <= 0) {
        respond(400, [
            "success" => false,
            "message" => "Reminder id is required to delete"
        ]);
    }

    $stmt = $conn->prepare("DELETE FROM reminders WHERE id = ?");
    $stmt->bind_param("i", $reminderId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected > 0) {
        respond(200, [
            "success" => true,
            "message" => "Reminder deleted successfully"
        ]);
    } else {
        respond(404, [
            "success" => false,
            "message" => "Reminder not found"
        ]);
    }
}

respond(405, [
    "success" => false,
    "message" => "Method not allowed"
]);

