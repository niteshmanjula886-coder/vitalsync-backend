<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tunnel-Skip-Anti-Abuse");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$serverIp = $_SERVER["SERVER_ADDR"] ?? gethostbyname(gethostname());

echo json_encode([
    "success" => true,
    "status" => "online",
    "message" => "VitalSync Backend is reachable",
    "server_ip" => $serverIp,
    "timestamp" => date("Y-m-d H:i:s")
], JSON_UNESCAPED_SLASHES);
