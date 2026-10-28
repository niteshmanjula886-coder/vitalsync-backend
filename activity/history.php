<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$userId = resolveUserIdOrDie($conn, true);

// Fetch recent vitals readings
$vStmt = $conn->prepare(
    "SELECT id, reading_type, value_text, value_numeric, unit, status_label, source, recorded_at 
     FROM vitals_readings 
     WHERE user_id = ? 
     ORDER BY recorded_at DESC LIMIT 50"
);
$vStmt->bind_param("i", $userId);
$vStmt->execute();
$vResult = $vStmt->get_result();

$historyItems = [];
$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));

while ($r = $vResult->fetch_assoc()) {
    $recDate = date("Y-m-d", strtotime($r["recorded_at"]));
    $dayTag = date("M d", strtotime($r["recorded_at"]));

    if ($recDate === $today) {
        $dateGroup = "Today • $dayTag";
    } elseif ($recDate === $yesterday) {
        $dateGroup = "Yesterday • $dayTag";
    } else {
        $dateGroup = $dayTag;
    }

    $typeMap = [
        "heart_rate" => ["Heart Rate", "❤️", "bpm"],
        "blood_pressure" => ["Blood Pressure", "🩺", "mmHg"],
        "spo2" => ["SpO₂", "🫁", "%"],
        "hydration" => ["Hydration", "💧", "ml"],
        "steps" => ["Step Count", "👟", "steps"],
        "sleep" => ["Sleep", "🌙", "hours"],
        "bmi" => ["BMI", "⚖️", "kg/m²"]
    ];

    $meta = $typeMap[$r["reading_type"]] ?? ["Vital Reading", "📊", $r["unit"]];

    $historyItems[] = [
        "id" => (string)$r["id"],
        "category" => $meta[0],
        "dateGroup" => $dateGroup,
        "dayTag" => $dayTag,
        "icon" => $meta[1],
        "title" => $meta[0],
        "value" => (string)($r["value_numeric"] ?? $r["value_text"]),
        "unit" => $r["unit"] ?: $meta[2],
        "status" => $r["status_label"],
        "time" => date("h:i A", strtotime($r["recorded_at"])) . " • " . ($r["source"] ?: "VitalSync")
    ];
}
$vStmt->close();
$conn->close();

// Provide fallback sample data if empty
if (empty($historyItems)) {
    $historyItems = [
        [
            "id" => "1",
            "category" => "Heart Rate",
            "dateGroup" => "Today • " . date("M d"),
            "dayTag" => date("M d"),
            "icon" => "❤️",
            "title" => "Heart Rate",
            "value" => "78",
            "unit" => "bpm",
            "status" => "Normal",
            "time" => "09:30 AM • Health Connect"
        ],
        [
            "id" => "2",
            "category" => "Blood Pressure",
            "dateGroup" => "Today • " . date("M d"),
            "dayTag" => date("M d"),
            "icon" => "🩺",
            "title" => "Blood Pressure",
            "value" => "120/80",
            "unit" => "mmHg",
            "status" => "Normal",
            "time" => "09:30 AM • Saved"
        ],
        [
            "id" => "3",
            "category" => "SpO₂",
            "dateGroup" => "Today • " . date("M d"),
            "dayTag" => date("M d"),
            "icon" => "🫁",
            "title" => "SpO₂",
            "value" => "98",
            "unit" => "%",
            "status" => "Optimal",
            "time" => "09:30 AM • Saved"
        ]
    ];
}

respondJson(200, [
    "success" => true,
    "count" => count($historyItems),
    "history" => $historyItems
]);

