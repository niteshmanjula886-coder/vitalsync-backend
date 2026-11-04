<?php

require_once __DIR__ . "/../config/auth_middleware.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

$datasetPath = __DIR__ . "/../ai_engine/dataset.json";
$modelPath = __DIR__ . "/../ai_engine/vitalsync_model.json";

// 1. GET: Return current model status, training metrics, and vocabulary statistics
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (!file_exists($modelPath)) {
        respondJson(404, [
            "success" => false,
            "message" => "Model weights not found. Please trigger training via POST /ai/train.php."
        ]);
    }

    $modelBundle = json_decode(file_get_contents($modelPath), true);
    respondJson(200, [
        "success" => true,
        "model_name" => $modelBundle["model_name"] ?? "VitalSync-Core-AI-v1",
        "trained_at" => $modelBundle["trained_at"] ?? null,
        "metrics" => $modelBundle["metrics"] ?? [],
        "classes" => $modelBundle["classes"] ?? [],
        "vocab_size" => count($modelBundle["vocab"] ?? [])
    ]);
}

// 2. POST: Re-train the model (optionally accept new training patterns)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = getInputData();

    // Check if new intents/patterns are supplied to extend dataset
    if (!empty($data["new_intent"]) && is_array($data["new_intent"])) {
        $newIntent = $data["new_intent"];
        if (!empty($newIntent["tag"]) && !empty($newIntent["patterns"]) && !empty($newIntent["responses"])) {
            $existingDataset = json_decode(file_get_contents($datasetPath), true);
            $found = false;
            foreach ($existingDataset["intents"] as &$it) {
                if ($it["tag"] === $newIntent["tag"]) {
                    $it["patterns"] = array_values(array_unique(array_merge($it["patterns"], $newIntent["patterns"])));
                    $it["responses"] = array_values(array_unique(array_merge($it["responses"], $newIntent["responses"])));
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existingDataset["intents"][] = [
                    "tag" => $newIntent["tag"],
                    "patterns" => $newIntent["patterns"],
                    "responses" => $newIntent["responses"]
                ];
            }
            file_put_contents($datasetPath, json_encode($existingDataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    // Execute Python training pipeline
    $pythonCmd = "python " . escapeshellarg(__DIR__ . "/../ai_engine/train.py") . " 2>&1";
    $output = [];
    $returnCode = 0;
    exec($pythonCmd, $output, $returnCode);

    if ($returnCode !== 0) {
        respondJson(500, [
            "success" => false,
            "message" => "AI Training execution failed",
            "output" => implode("\n", $output)
        ]);
    }

    $updatedBundle = json_decode(file_get_contents($modelPath), true);

    respondJson(200, [
        "success" => true,
        "message" => "VitalSync Core AI model successfully trained from scratch!",
        "model_name" => $updatedBundle["model_name"],
        "trained_at" => $updatedBundle["trained_at"],
        "metrics" => $updatedBundle["metrics"],
        "classes_count" => count($updatedBundle["classes"]),
        "vocab_size" => count($updatedBundle["vocab"])
    ]);
}

respondJson(405, ["success" => false, "message" => "Method not allowed"]);

