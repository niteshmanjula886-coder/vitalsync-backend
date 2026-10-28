<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);
$method = $_SERVER["REQUEST_METHOD"];

// GET: list readings
if ($method === "GET") {
    $type = trim($_GET["type"] ?? $_GET["reading_type"] ?? "");
    $limit = isset($_GET["limit"]) ? max(1, min(100, (int)$_GET["limit"])) : 30;

    if ($type !== "") {
        $stmt = $conn->prepare(
            "SELECT id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, notes, recorded_at 
             FROM vitals_readings 
             WHERE user_id = ? AND reading_type = ? 
             ORDER BY recorded_at DESC LIMIT ?"
        );
        $stmt->bind_param("isi", $userId, $type, $limit);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, notes, recorded_at 
             FROM vitals_readings 
             WHERE user_id = ? 
             ORDER BY recorded_at DESC LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $readings = [];
    while ($row = $result->fetch_assoc()) {
        $readings[] = [
            "id" => (int)$row["id"],
            "reading_type" => $row["reading_type"],
            "value_numeric" => $row["value_numeric"] !== null ? (float)$row["value_numeric"] : null,
            "value_secondary" => $row["value_secondary"] !== null ? (float)$row["value_secondary"] : null,
            "value_text" => $row["value_text"],
            "unit" => $row["unit"],
            "status_label" => $row["status_label"],
            "source" => $row["source"],
            "notes" => $row["notes"],
            "recorded_at" => $row["recorded_at"]
        ];
    }
    $stmt->close();
    $conn->close();

    respondJson(200, [
        "success" => true,
        "count" => count($readings),
        "readings" => $readings
    ]);
}

// POST: Add new reading (for AddReadingScreen.kt & ReadingReviewScreen.kt)
if ($method === "POST") {
    $data = getInputData();

    $type = strtolower(trim($data["reading_type"] ?? $data["type"] ?? ""));
    $valNum = isset($data["value_numeric"]) && is_numeric($data["value_numeric"]) ? (float)$data["value_numeric"] : null;
    $valSec = isset($data["value_secondary"]) && is_numeric($data["value_secondary"]) ? (float)$data["value_secondary"] : null;
    $valText = trim((string)($data["value_text"] ?? ""));
    $unit = trim((string)($data["unit"] ?? ""));
    $statusLabel = trim((string)($data["status_label"] ?? "Normal"));
    $source = trim((string)($data["source"] ?? "Manual Entry"));
    $notes = isset($data["notes"]) ? trim((string)$data["notes"]) : null;
    $recordedAt = !empty($data["recorded_at"]) ? trim((string)$data["recorded_at"]) : date("Y-m-d H:i:s");

    if ($type === "") {
        respondJson(400, [
            "success" => false,
            "message" => "reading_type is required (e.g. heart_rate, blood_pressure, spo2, bmi, hydration, steps, sleep)"
        ]);
    }

    // Default units and formatting if not supplied
    if ($type === "heart_rate") {
        if ($unit === "") $unit = "bpm";
        if ($valText === "" && $valNum !== null) $valText = ((int)$valNum) . " BPM";
        if ($valNum !== null) {
            if ($valNum < 60) $statusLabel = "Low Rate";
            elseif ($valNum <= 90) $statusLabel = "Normal";
            else $statusLabel = "High Rate";
        }
    } elseif ($type === "blood_pressure") {
        if ($unit === "") $unit = "mmHg";
        if ($valText === "" && $valNum !== null && $valSec !== null) $valText = ((int)$valNum) . "/" . ((int)$valSec) . " mmHg";
        if ($valNum !== null && $valSec !== null) {
            if ($valNum < 120 && $valSec < 80) $statusLabel = "Normal";
            elseif ($valNum <= 129 && $valSec < 80) $statusLabel = "Elevated";
            else $statusLabel = "Hypertension";
        }
    } elseif ($type === "spo2") {
        if ($unit === "") $unit = "%";
        if ($valText === "" && $valNum !== null) $valText = ((int)$valNum) . "%";
        if ($valNum !== null) {
            $statusLabel = ($valNum >= 95) ? "Optimal" : "Low";
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO vitals_readings (user_id, reading_type, value_numeric, value_secondary, value_text, unit, status_label, source, notes, recorded_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        respondJson(500, [
            "success" => false,
            "message" => "Database statement preparation failed: " . $conn->error
        ]);
    }

    $stmt->bind_param("isddssssss", $userId, $type, $valNum, $valSec, $valText, $unit, $statusLabel, $source, $notes, $recordedAt);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        respondJson(201, [
            "success" => true,
            "message" => "Vital reading recorded successfully",
            "reading" => [
                "id" => $newId,
                "user_id" => $userId,
                "reading_type" => $type,
                "value_numeric" => $valNum,
                "value_secondary" => $valSec,
                "value_text" => $valText,
                "unit" => $unit,
                "status_label" => $statusLabel,
                "source" => $source,
                "recorded_at" => $recordedAt
            ]
        ]);
    }

    respondJson(500, [
        "success" => false,
        "message" => "Failed to record vital reading: " . $stmt->error
    ]);
}

// DELETE: Remove reading
if ($method === "DELETE") {
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    if ($id <= 0) {
        $data = getInputData();
        $id = isset($data["id"]) ? (int)$data["id"] : 0;
    }

    if ($id <= 0) {
        respondJson(400, [
            "success" => false,
            "message" => "Reading ID is required to delete"
        ]);
    }

    $stmt = $conn->prepare("DELETE FROM vitals_readings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected > 0) {
        respondJson(200, [
            "success" => true,
            "message" => "Reading deleted successfully"
        ]);
    }

    respondJson(404, [
        "success" => false,
        "message" => "Reading not found or unauthorized"
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

