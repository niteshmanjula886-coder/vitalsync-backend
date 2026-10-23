<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "vitalsync";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log("VitalSync database connection failed: " . $conn->connect_error);

    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

$conn->set_charset("utf8mb4");