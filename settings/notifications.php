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
