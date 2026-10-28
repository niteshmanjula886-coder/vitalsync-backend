<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, title, description, icon, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifs = [];
    $unreadCount = 0;
    while ($r = $result->fetch_assoc()) {
        $isRead = (bool)$r["is_read"];
        if (!$isRead) $unreadCount++;

        $notifs[] = [
            "id" => (int)$r["id"],
            "title" => $r["title"],
            "description" => $r["description"],
            "icon" => $r["icon"],
            "is_read" => $isRead,
            "created_at" => $r["created_at"],
            "time_ago" => "Recent"
        ];
    }
    $stmt->close();

    // Seed sample notifications if empty
    if (empty($notifs)) {
        $seed = [
            ["Time to drink water", "Your hydration goal is 62% complete.", "💧", 0],
            ["Activity Reminder", "You have 1,755 steps left to hit your goal.", "👟", 0],
            ["Sleep Analysis Ready", "You reached 80% sleep score with optimal deep sleep.", "🌙", 1],
            ["Weekly Report Generated", "Your comprehensive wellness overview is ready to view.", "📊", 1]
        ];

        $ins = $conn->prepare("INSERT INTO notifications (user_id, title, description, icon, is_read) VALUES (?, ?, ?, ?, ?)");
        foreach ($seed as $s) {
            $ins->bind_param("isssi", $userId, $s[0], $s[1], $s[2], $s[3]);
            $ins->execute();
            $notifs[] = [
                "id" => (int)$ins->insert_id,
                "title" => $s[0],
                "description" => $s[1],
                "icon" => $s[2],
                "is_read" => (bool)$s[3],
                "created_at" => date("Y-m-d H:i:s"),
                "time_ago" => "Recent"
            ];
            if (!$s[3]) $unreadCount++;
        }
        $ins->close();
    }

    $conn->close();

    respondJson(200, [
        "success" => true,
        "unread_count" => $unreadCount,
        "notifications" => $notifs
    ]);
}

if ($method === "POST") {
    $data = getInputData();
    $action = strtolower(trim((string)($data["action"] ?? "")));
    $notifId = isset($data["id"]) ? (int)$data["id"] : 0;

    if ($action === "mark_all_read") {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "All notifications marked as read",
            "updated_count" => $updated
        ]);
    }

    if ($notifId > 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notifId, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        respondJson(200, [
            "success" => true,
            "message" => "Notification marked as read",
            "id" => $notifId
        ]);
    }

    respondJson(400, [
        "success" => false,
        "message" => "Please specify notification 'id' or action: 'mark_all_read'"
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

