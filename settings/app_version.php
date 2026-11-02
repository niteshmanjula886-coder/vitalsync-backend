<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

echo json_encode([
    "success" => true,
    "status" => "success",
    "version" => "1.0",
    "version_name" => "1.0 - Stable",
    "build_type" => "Release",
    "is_up_to_date" => true,
    "checked_at" => date("M d, Y \\a\\t h:i A"),
    "features" => [
        "All your health essentials (heart rate, blood pressure, SpO2, steps, hydration, sleep)",
        "Personalized AI Insights and live Wellness Score",
        "Seamless Bluetooth Low Energy wearable synchronization"
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

