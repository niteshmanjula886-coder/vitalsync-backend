<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tunnel-Skip-Anti-Abuse, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$host = "localhost";
$user = "root";
$password = "";
$database = "vitalsync_db";

// Connect using MySQLi with automatic fallback to vitalsync if needed
try {
    $conn = new mysqli($host, $user, $password, $database);
} catch (\Throwable $e) {
    try {
        $database = "vitalsync";
        $conn = new mysqli($host, $user, $password, $database);
    } catch (\Throwable $e2) {
        error_log("Database connection failed: " . $e2->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Database error: " . $e2->getMessage()
        ], JSON_UNESCAPED_SLASHES);
        exit();
    }
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection error"
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

$conn->set_charset("utf8mb4");

// Load PDO and helper functions
require_once __DIR__ . "/config/db_connect.php";
