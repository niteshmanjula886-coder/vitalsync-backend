<?php

header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

$userId = resolveUserIdOrDie($conn, true);
$input = getInputData();

$mealId = (int)($input["id"] ?? $input["meal_id"] ?? $_GET["id"] ?? $_GET["meal_id"] ?? 0);

if ($mealId <= 0) {
    respondJson(400, [
        "success" => false,
        "message" => "Valid meal ID required to delete."
    ]);
}

$stmt = $conn->prepare("DELETE FROM gym_nutrition_logs WHERE id = ? AND user_id = ?");
if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
}

$stmt->bind_param("ii", $mealId, $userId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    respondJson(200, [
        "success" => true,
        "message" => "Meal log deleted successfully"
    ]);
} else {
    respondJson(404, [
        "success" => false,
        "message" => "Meal record not found or already deleted."
    ]);
}
