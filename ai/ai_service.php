<?php

set_time_limit(300);
ini_set('max_execution_time', 300);

/**
 * VitalSync AI - Local Ollama AI Engine (qwen2.5:1.5b)
 * 
 * 1. TARGET MODEL:
 *    - Model: "qwen2.5:1.5b"
 *    - Target Endpoint: http://127.0.0.1:11434/api/chat
 * 
 * 2. SYSTEM PROMPT REASONING & GROUNDING:
 *    - NLP Tolerance: Infer intent smoothly despite typos or broken grammar without pointing them out
 *    - Hardware Disconnection Check: Alert if metric is missing or "NOT_RECORDED":
 *      "Your [metric] has not been recorded yet. Please connect your compatible smartwatch or health tracker via Settings > Connected Devices to enable live monitoring."
 *    - BMI & Weight Guidance: If BMI between 25.0 and 29.9, clearly state overweight category, no false flattery, suggest 300–400 kcal deficit, daily brisk walking, adequate protein
 *    - Activity Analysis: Compare actual vs target steps, highlight deficit with realistic pacing tips
 *    - Diet Plans: Structured meal breakdown (Breakfast, Lunch, Snack, Dinner)
 *    - Formatting: Clean markdown bullets and bold all metrics and targets (e.g., **26.1 BMI**, **0 / 10,000 steps**)
 * 
 * 3. TELEMETRY INJECTION:
 *    - Passes live vitals from DB: Height, Weight, BMI, Heart Rate, BP, SpO2, Steps/Target, Hydration/Target
 *    - If sensor reading is missing or 0, marks as "NOT_RECORDED"
 * 
 * 4. OLLAMA CHAT API INTEGRATION:
 *    - Structured JSON payload with messages (system prompt, conversation history, user prompt), stream=false, temperature=0.3
 */

if (!defined("OLLAMA_BASE_URL")) {
    define("OLLAMA_BASE_URL", "http://127.0.0.1:11434");
}
define("OLLAMA_MODEL", "qwen2.5:1.5b");
define("OLLAMA_TIMEOUT_SECONDS", 300);

/**
 * Returns list of adaptable Ollama host candidates
 */
function getOllamaCandidateUrls(): array {
    $hosts = [];
    $envHost = trim((string)getenv("OLLAMA_HOST"));
    if ($envHost !== "") {
        $hosts[] = preg_match('#^https?://#i', $envHost) ? $envHost : "http://" . $envHost;
    }
    $hosts[] = "http://127.0.0.1:11434";
    $hosts[] = "http://localhost:11434";
    $localIp = gethostbyname(gethostname());
    if ($localIp && $localIp !== "127.0.0.1" && $localIp !== "localhost") {
        $hosts[] = "http://{$localIp}:11434";
    }
    return array_unique($hosts);
}

/**
 * Checks if local or adaptable Ollama daemon is reachable.
 */
function isOllamaReachable(&$resolvedBaseUrl = null) {
    $candidates = getOllamaCandidateUrls();
    foreach ($candidates as $candidate) {
        $target = rtrim($candidate, "/");
        if (function_exists("curl_init")) {
            $ch = curl_init($target . "/");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_NOBODY => true
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode >= 200 && $httpCode < 400) {
                $resolvedBaseUrl = $target;
                return true;
            }
        }
        $errno = 0;
        $errstr = "";
        $parsed = parse_url($target);
        $host = $parsed["host"] ?? "127.0.0.1";
        $port = (int)($parsed["port"] ?? 11434);
        $sock = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($sock) {
            fclose($sock);
            $resolvedBaseUrl = $target;
            return true;
        }
    }
    return false;
}

/**
 * Calls local Ollama /api/chat with qwen2.5:1.5b.
 * Sends properly structured JSON payload with system prompt, conversation history, and user prompt.
 *
 * @param array $messages Array of ['role' => 'user'|'assistant'|'system', 'content' => string]
 * @param string $systemPrompt Enforces clinical reasoning and telemetry grounding
 * @return array|null Returns ['text' => string, 'model' => string, 'eval_count' => int] or ['error' => string] on failure
 */
function callAiChat(array $messages, $systemPrompt = "") {
    $formattedMessages = [];
    if (!empty($systemPrompt)) {
        $formattedMessages[] = [
            "role" => "system",
            "content" => $systemPrompt
        ];
    }

    foreach ($messages as $m) {
        $formattedMessages[] = [
            "role" => ($m["role"] === "AI" || $m["role"] === "assistant") ? "assistant" : "user",
            "content" => (string)$m["content"]
        ];
    }

    $payload = json_encode([
        "model" => OLLAMA_MODEL,
        "messages" => $formattedMessages,
        "stream" => false,
        "keep_alive" => "30m",
        "options" => [
            "num_gpu" => 0,
            "temperature" => 0.25,
            "top_p" => 0.9,
            "repeat_penalty" => 1.1,
            "num_predict" => 512
        ]
    ]);

    $rawResponse = false;
    $httpCode = 0;
    $curlErr = "";

    $resolvedUrl = OLLAMA_BASE_URL;
    isOllamaReachable($resolvedUrl);

    if (function_exists("curl_init")) {
        $ch = curl_init(rtrim($resolvedUrl, "/") . "/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 300
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
    } else {
        $errno = 0;
        $errstr = "";
        $parsed = parse_url($resolvedUrl);
        $host = $parsed["host"] ?? "127.0.0.1";
        $port = (int)($parsed["port"] ?? 11434);
        $sock = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($sock) {
            fclose($sock);
            $ctx = stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json\r\n",
                    "content" => $payload,
                    "timeout" => 300,
                    "ignore_errors" => true
                ]
            ]);
            $rawResponse = @file_get_contents(rtrim($resolvedUrl, "/") . "/api/chat", false, $ctx);
            if ($rawResponse !== false) {
                $httpCode = 200;
            }
        }
    }

    if (!empty($curlErr)) {
        error_log("Ollama cURL error: " . $curlErr);
        return [
            "error" => "Ollama cURL error: " . $curlErr,
            "text" => null
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300 || empty($rawResponse)) {
        $errDetail = "";
        if (!empty($rawResponse)) {
            $errJson = json_decode($rawResponse, true);
            $errDetail = $errJson["error"] ?? $rawResponse;
        }
        error_log("Ollama HTTP Error ($httpCode): " . $errDetail);
        return [
            "error" => "Ollama HTTP $httpCode: " . ($errDetail ?: "Empty response"),
            "text" => null
        ];
    }

    $decoded = json_decode($rawResponse, true);
    $content = trim($decoded["message"]["content"] ?? "");
    if ($content === "") {
        return null;
    }

    // Strip any internal thinking tags, system block markers, and trailing hanging bullets
    $content = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $content);
    $content = preg_replace('/\[(LIVE_USER_SNAPSHOT|END_TELEMETRY|NUTRITION REFERENCE BASELINES|MACRO CALCULATION RULES|PATIENT REAL-TIME TELEMETRY SNAPSHOT)\]/i', '', $content);
    $content = preg_replace('/(\n\s*[-*•]\s*(\*\*[^*]*\*\*:?)?\s*)$/', '', $content);
    $content = trim($content);

    return [
        "text" => $content,
        "model" => OLLAMA_MODEL,
        "eval_count" => $decoded["eval_count"] ?? 0
    ];
}

/**
 * Streams AI chat chunks in real-time from Ollama /api/chat.
 */
function streamAiChat(array $messages, $systemPrompt, callable $chunkCallback) {
    $formattedMessages = [];
    if (!empty($systemPrompt)) {
        $formattedMessages[] = [
            "role" => "system",
            "content" => $systemPrompt
        ];
    }

    foreach ($messages as $m) {
        $formattedMessages[] = [
            "role" => ($m["role"] === "AI" || $m["role"] === "assistant") ? "assistant" : "user",
            "content" => (string)$m["content"]
        ];
    }

    $payload = json_encode([
        "model" => OLLAMA_MODEL,
        "messages" => $formattedMessages,
        "stream" => true,
        "keep_alive" => "30m",
        "options" => [
            "num_gpu" => 0,
            "temperature" => 0.25,
            "top_p" => 0.9,
            "repeat_penalty" => 1.1,
            "num_predict" => 512
        ]
    ]);

    if (!function_exists("curl_init")) {
        return false;
    }

    $ch = curl_init(OLLAMA_BASE_URL . "/api/chat");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ],
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_WRITEFUNCTION => function($curlHandle, $data) use ($chunkCallback) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $decoded = json_decode($line, true);
                if ($decoded) {
                    $chunk = $decoded["message"]["content"] ?? "";
                    $isDone = $decoded["done"] ?? false;
                    if (!empty($chunk) || $isDone) {
                        $chunkCallback($chunk, $isDone);
                    }
                }
            }
            return strlen($data);
        }
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($res && $httpCode >= 200 && $httpCode < 300);
}

/**
 * Checks whether the user query relates to health, fitness, nutrition, medicine, or wellness.
 */
function isHealthKnowledgeQuery($query) {
    $low = strtolower($query);
    $healthTerms = [
        "protein", "calorie", "diet", "nutrition", "food", "eat", "meal", "carb", "fat",
        "vitamin", "mineral", "supplement", "creatine", "whey", "magnesium", "omega",
        "heart", "rate", "bpm", "pulse", "blood pressure", "hypertension", "bp", "cardio",
        "fever", "temperature", "infection", "sick", "headache", "dehydration", "ors",
        "sleep", "rem", "deep sleep", "insomnia", "circadian", "wake", "bedtime",
        "water", "drink", "hydration", "fluid", "electrolytes",
        "muscle", "workout", "gym", "exercise", "hypertrophy", "training", "reps", "sets",
        "hrv", "cortisol", "stress", "recovery", "soreness", "fatigue", "vitals",
        "health", "medical", "doctor", "symptoms", "pain", "cramp", "bradycardia", "tachycardia"
    ];
    foreach ($healthTerms as $term) {
        if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $low)) {
            return true;
        }
    }
    return false;
}

/**
 * Searches verified RAG knowledge base.
 */
function searchKnowledgeBase($conn, $query, $limit = 3) {
    $results = [];
    if (!isHealthKnowledgeQuery($query)) {
        return $results;
    }

    $cleanQuery = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query));
    if (empty($cleanQuery)) {
        return $results;
    }

    $stmt = $conn->prepare(
        "SELECT id, category, topic, content,
                MATCH(keywords, content) AGAINST(? IN NATURAL LANGUAGE MODE) AS score
         FROM ai_knowledge_base
         WHERE MATCH(keywords, content) AGAINST(? IN NATURAL LANGUAGE MODE)
         ORDER BY score DESC LIMIT ?"
    );
    if ($stmt) {
        $stmt->bind_param("ssi", $cleanQuery, $cleanQuery, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($row["score"] >= 0.08) {
                $results[] = [
                    "id" => (int)$row["id"],
                    "category" => $row["category"],
                    "topic" => $row["topic"],
                    "content" => $row["content"]
                ];
            }
        }
        $stmt->close();
    }

    if (count($results) < $limit) {
        $stopWords = ["the", "is", "at", "which", "on", "a", "an", "and", "or", "to", "in", "of", "for", "with", "about", "how", "what", "my", "me", "i", "can", "you", "tell", "give"];
        $rawWords = preg_split('/\s+/', strtolower($cleanQuery));
        $keywords = [];
        foreach ($rawWords as $w) {
            $w = trim($w);
            if (strlen($w) >= 3 && !in_array($w, $stopWords)) {
                $keywords[] = $w;
            }
        }

        if (!empty($keywords)) {
            $existingIds = array_column($results, "id");
            $conditions = [];
            $types = "";
            $params = [];

            foreach (array_slice($keywords, 0, 4) as $kw) {
                $conditions[] = "(keywords REGEXP ? OR topic REGEXP ? OR content REGEXP ?)";
                $types .= "sss";
                $regex = "[[:<:]]" . $kw . "[[:>:]]";
                $params[] = $regex;
                $params[] = $regex;
                $params[] = $regex;
            }

            $sql = "SELECT id, category, topic, content FROM ai_knowledge_base WHERE (" . implode(" OR ", $conditions) . ")";
            if (!empty($existingIds)) {
                $sql .= " AND id NOT IN (" . implode(",", array_map("intval", $existingIds)) . ")";
            }
            $remaining = $limit - count($results);
            $sql .= " LIMIT " . intval($remaining);

            $stmt2 = $conn->prepare($sql);
            if ($stmt2) {
                $stmt2->bind_param($types, ...$params);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                while ($r = $res2->fetch_assoc()) {
                    $results[] = [
                        "id" => (int)$r["id"],
                        "category" => $r["category"],
                        "topic" => $r["topic"],
                        "content" => $r["content"]
                    ];
                }
                $stmt2->close();
            }
        }
    }

    return $results;
}

/**
 * Detects if user query is inquiring about their personal vitals or health metrics.
 */
function isUserPersonalHealthQuery($query) {
    $low = strtolower($query);
    if (preg_match('/\b(my|i|today|stats|vitals|readiness|workout|train|score|progress|sleep|steps|water|heart|bpm|pulse|bp|blood pressure|spo2|oxygen|bmi|weight|diet|meal|food)\b/i', $low)) {
        return true;
    }
    return false;
}

/**
 * Loads recent multi-turn conversation memory (default 10 turns), optionally scoped to a chat session.
 */
function loadConversationHistory($conn, $userId, $limit = 10, $sessionId = null) {
    if ($sessionId !== null && (int)$sessionId > 0) {
        $stmt = $conn->prepare(
            "SELECT sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content 
             FROM ai_chat_messages 
             WHERE user_id = ? AND session_id = ? 
             ORDER BY id DESC LIMIT ?"
        );
        $sId = (int)$sessionId;
        $stmt->bind_param("iii", $userId, $sId, $limit);
    } else {
        $stmt = $conn->prepare(
            "SELECT sender, COALESCE(NULLIF(message_text, ''), message) AS msg_content 
             FROM ai_chat_messages 
             WHERE user_id = ? 
             ORDER BY id DESC LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
    }
    if (!$stmt) {
        return [];
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $content = trim((string)($r["msg_content"] ?? ""));
        if ($content === "") continue;
        $rows[] = [
            "role" => (strcasecmp(trim((string)$r["sender"]), "user") === 0) ? "user" : "assistant",
            "content" => $content
        ];
    }
    $stmt->close();
    return array_reverse($rows);
}

/**
 * Compiles patient telemetry feature store from the database.
 * If sensor readings are missing or 0, marks them explicitly as "NOT_RECORDED" to trigger the hardware warning rule.
 */
function fetchUserHealthContext($conn, $userId) {
    $ctx = [
        "name" => "there",
        "age" => null,
        "gender" => null,
        "height_cm" => "NOT_RECORDED",
        "weight_kg" => "NOT_RECORDED",
        "bmi_value" => "NOT_RECORDED",
        "bmi_category" => "NOT_RECORDED",
        "primary_goal" => "General Wellness",
        "calories_target" => 2200,
        "protein_target_g" => 140,
        "hr_current" => "NOT_RECORDED",
        "hr_recorded_at" => null,
        "hr_resting" => "NOT_RECORDED",
        "blood_pressure" => "NOT_RECORDED",
        "bp_recorded_at" => null,
        "spo2" => "NOT_RECORDED",
        "spo2_numeric" => null,
        "spo2_recorded_at" => null,
        "steps_current" => 0,
        "steps_target" => 10000,
        "today_distance_km" => 0.0,
        "today_activity_calories" => 0,
        "water_current_ml" => 0,
        "water_target_ml" => 2500,
        "recent_activity_zone" => "Resting / Sedentary",
        "last_sleep_hours" => "NOT_RECORDED",
        "last_sleep_score" => null,
        "today_nutrition_summary" => "0 kcal logged",
        "latest_activity_session" => "No reading logged yet"
    ];

    // 1. Profile: Height, Weight, Age, Gender, Goal
    $uStmt = $conn->prepare(
        "SELECT u.name, u.email, u.primary_goal, u.height AS u_height, u.weight AS u_weight, u.age AS u_age, u.gender AS u_gender,
                p.age AS p_age, p.gender AS p_gender, p.height_cm AS p_height, p.weight_kg AS p_weight
         FROM users u
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE u.id = ? LIMIT 1"
    );
    if ($uStmt) {
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uRes = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        if ($uRes) {
            if (!empty($uRes["name"])) {
                $ctx["name"] = explode(" ", trim($uRes["name"]))[0];
            }
            $age = $uRes["p_age"] ?: $uRes["u_age"];
            if ($age && (int)$age > 0) $ctx["age"] = (int)$age;

            $gender = $uRes["p_gender"] ?: $uRes["u_gender"];
            if (!empty($gender)) $ctx["gender"] = trim($gender);

            if (!empty($uRes["primary_goal"])) $ctx["primary_goal"] = trim($uRes["primary_goal"]);

            $heightVal = (float)($uRes["p_height"] ?: $uRes["u_height"] ?: 0);
            $weightVal = (float)($uRes["p_weight"] ?: $uRes["u_weight"] ?: 0);

            if ($heightVal > 0) {
                $ctx["height_cm"] = round($heightVal, 1);
            }
            if ($weightVal > 0) {
                $ctx["weight_kg"] = round($weightVal, 1);
            }

            if ($heightVal > 0 && $weightVal > 0) {
                $hM = $heightVal / 100.0;
                $bmi = round($weightVal / ($hM * $hM), 1);
                $ctx["bmi_value"] = $bmi;
                if ($bmi < 18.5) {
                    $ctx["bmi_category"] = "Underweight";
                } elseif ($bmi < 25.0) {
                    $ctx["bmi_category"] = "Normal weight";
                } elseif ($bmi < 30.0) {
                    $ctx["bmi_category"] = "Overweight";
                } else {
                    $ctx["bmi_category"] = "Obese";
                }
            }
        }
    }

    // 2. Health & Nutrition Targets
    $hgUpdatedAt = null;
    try {
        $gStmt = $conn->prepare("SELECT primary_goal, target_steps, target_water_ml, target_weight_kg, updated_at FROM user_health_goals WHERE user_id = ? LIMIT 1");
        if ($gStmt) {
            $gStmt->bind_param("i", $userId);
            $gStmt->execute();
            $gRes = $gStmt->get_result()->fetch_assoc();
            $gStmt->close();
            if (!empty($gRes["primary_goal"])) $ctx["primary_goal"] = $gRes["primary_goal"];
            if (!empty($gRes["target_steps"]) && (int)$gRes["target_steps"] > 0) {
                $ctx["steps_target"] = (int)$gRes["target_steps"];
                $hgUpdatedAt = $gRes["updated_at"] ?? null;
            }
            if (!empty($gRes["target_water_ml"]) && (int)$gRes["target_water_ml"] > 0) $ctx["water_target_ml"] = (int)$gRes["target_water_ml"];
        }
        $agStmt = $conn->prepare("SELECT daily_step_goal, updated_at FROM activity_goals WHERE user_id = ? LIMIT 1");
        if ($agStmt) {
            $agStmt->bind_param("i", $userId);
            $agStmt->execute();
            $agRes = $agStmt->get_result()->fetch_assoc();
            $agStmt->close();
            if (!empty($agRes["daily_step_goal"]) && (int)$agRes["daily_step_goal"] > 0) {
                if ($hgUpdatedAt === null || empty($agRes["updated_at"]) || strtotime($agRes["updated_at"]) >= strtotime($hgUpdatedAt)) {
                    $ctx["steps_target"] = (int)$agRes["daily_step_goal"];
                }
            }
        }
        $hgStmt = $conn->prepare("SELECT daily_target_ml FROM hydration_goals WHERE user_id = ? LIMIT 1");
        if ($hgStmt) {
            $hgStmt->bind_param("i", $userId);
            $hgStmt->execute();
            $hgRes = $hgStmt->get_result()->fetch_assoc();
            $hgStmt->close();
            if (!empty($hgRes["daily_target_ml"]) && (int)$hgRes["daily_target_ml"] > 0) {
                $ctx["water_target_ml"] = (int)$hgRes["daily_target_ml"];
            }
        }
        $ngStmt = $conn->prepare("SELECT daily_calorie_target, daily_protein_target FROM nutrition_goals WHERE user_id = ? LIMIT 1");
        if ($ngStmt) {
            $ngStmt->bind_param("i", $userId);
            $ngStmt->execute();
            $ngRes = $ngStmt->get_result()->fetch_assoc();
            $ngStmt->close();
            if ($ngRes) {
                if (!empty($ngRes["daily_calorie_target"])) $ctx["calories_target"] = (int)$ngRes["daily_calorie_target"];
                if (!empty($ngRes["daily_protein_target"])) $ctx["protein_target_g"] = (int)round((float)$ngRes["daily_protein_target"]);
            }
        }
    } catch (\Exception $e) {}

    // 3. Latest Live Vitals with timestamps (Heart Rate, Blood Pressure, SpO2)
    $vStmt = $conn->prepare(
        "SELECT heart_rate, spo2, systolic_bp, diastolic_bp, logged_at 
         FROM vitals_logs 
         WHERE user_id = ?
         ORDER BY logged_at DESC LIMIT 1"
    );
    if ($vStmt) {
        $vStmt->bind_param("i", $userId);
        $vStmt->execute();
        $vRes = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();

        if ($vRes) {
            $ts = $vRes["logged_at"] ?? null;
            if (!empty($vRes["heart_rate"]) && (int)$vRes["heart_rate"] > 0) {
                $ctx["hr_current"] = (int)$vRes["heart_rate"];
                $ctx["hr_recorded_at"] = $ts;
            }
            if (!empty($vRes["spo2"]) && (int)$vRes["spo2"] > 0) {
                $ctx["spo2_numeric"] = (int)$vRes["spo2"];
                $ctx["spo2"] = (int)$vRes["spo2"] . "%";
                $ctx["spo2_recorded_at"] = $ts;
            }
            if (!empty($vRes["systolic_bp"]) && !empty($vRes["diastolic_bp"]) && (int)$vRes["systolic_bp"] > 0 && (int)$vRes["diastolic_bp"] > 0) {
                $ctx["blood_pressure"] = (int)$vRes["systolic_bp"] . "/" . (int)$vRes["diastolic_bp"] . " mmHg";
                $ctx["bp_recorded_at"] = $ts;
            }
        }
    }

    // Check vitals_readings for latest recorded individual vitals (including Health Connect syncs)
    $vrStmt = $conn->prepare("SELECT value_numeric, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'heart_rate' ORDER BY recorded_at DESC LIMIT 1");
    if ($vrStmt) {
        $vrStmt->bind_param("i", $userId);
        $vrStmt->execute();
        $val = $vrStmt->get_result()->fetch_assoc();
        $vrStmt->close();
        if ($val && (float)$val["value_numeric"] > 0 && ($ctx["hr_current"] === "NOT_RECORDED" || strtotime((string)$val["recorded_at"]) >= strtotime((string)($ctx["hr_recorded_at"] ?? "1970-01-01")))) {
            $ctx["hr_current"] = (int)$val["value_numeric"];
            $ctx["hr_recorded_at"] = $val["recorded_at"] ?? null;
        }
    }

    $vsStmt = $conn->prepare("SELECT value_numeric, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'spo2' ORDER BY recorded_at DESC LIMIT 1");
    if ($vsStmt) {
        $vsStmt->bind_param("i", $userId);
        $vsStmt->execute();
        $val = $vsStmt->get_result()->fetch_assoc();
        $vsStmt->close();
        if ($val && (float)$val["value_numeric"] > 0 && ($ctx["spo2"] === "NOT_RECORDED" || strtotime((string)$val["recorded_at"]) >= strtotime((string)($ctx["spo2_recorded_at"] ?? "1970-01-01")))) {
            $ctx["spo2_numeric"] = (int)$val["value_numeric"];
            $ctx["spo2"] = (int)$val["value_numeric"] . "%";
            $ctx["spo2_recorded_at"] = $val["recorded_at"] ?? null;
        }
    }

    $vbpStmt = $conn->prepare("SELECT value_numeric, value_secondary, recorded_at FROM vitals_readings WHERE user_id = ? AND reading_type = 'blood_pressure' ORDER BY recorded_at DESC LIMIT 1");
    if ($vbpStmt) {
        $vbpStmt->bind_param("i", $userId);
        $vbpStmt->execute();
        $val = $vbpStmt->get_result()->fetch_assoc();
        $vbpStmt->close();
        if ($val && (float)$val["value_numeric"] > 0 && (float)$val["value_secondary"] > 0 && ($ctx["blood_pressure"] === "NOT_RECORDED" || strtotime((string)$val["recorded_at"]) >= strtotime((string)($ctx["bp_recorded_at"] ?? "1970-01-01")))) {
            $ctx["blood_pressure"] = (int)$val["value_numeric"] . "/" . (int)$val["value_secondary"] . " mmHg";
            $ctx["bp_recorded_at"] = $val["recorded_at"] ?? null;
        }
    }

    // Resting Heart Rate Baseline (TODAY)
    $rStmt = $conn->prepare("SELECT MIN(heart_rate) as resting_hr FROM vitals_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE() AND heart_rate >= 45 AND heart_rate <= 110");
    if ($rStmt) {
        $rStmt->bind_param("i", $userId);
        $rStmt->execute();
        $rRes = $rStmt->get_result()->fetch_assoc();
        $rStmt->close();
        if ($rRes && (int)$rRes["resting_hr"] > 0) {
            $ctx["hr_resting"] = (int)$rRes["resting_hr"] . " bpm";
        } elseif ($ctx["hr_current"] !== "NOT_RECORDED") {
            $ctx["hr_resting"] = $ctx["hr_current"] . " bpm";
        }
    }

    // 4. Daily Steps & Latest Activity / Cycling Session
    $today = date("Y-m-d");
    $actStmt = $conn->prepare(
        "SELECT COALESCE(SUM(steps), 0) AS total_steps,
                COALESCE(SUM(distance_km), 0) AS total_dist,
                COALESCE(SUM(calories_burned), 0) AS total_cal
         FROM activity_sessions 
         WHERE user_id = ? AND DATE(start_time) = ?"
    );
    if ($actStmt) {
        $actStmt->bind_param("is", $userId, $today);
        $actStmt->execute();
        $actRes = $actStmt->get_result()->fetch_assoc();
        $actStmt->close();
        $ctx["steps_current"] = (int)($actRes["total_steps"] ?? 0);
        $ctx["today_distance_km"] = round((float)($actRes["total_dist"] ?? 0), 2);
        $ctx["today_activity_calories"] = (int)($actRes["total_cal"] ?? 0);
    }

    $laStmt = $conn->prepare(
        "SELECT activity_type, duration_seconds, distance_km, calories_burned, start_time
         FROM activity_sessions
         WHERE user_id = ?
         ORDER BY id DESC LIMIT 1"
    );
    if ($laStmt) {
        $laStmt->bind_param("i", $userId);
        $laStmt->execute();
        $laRes = $laStmt->get_result()->fetch_assoc();
        $laStmt->close();
        if ($laRes) {
            $mins = floor((int)$laRes["duration_seconds"] / 60);
            $dist = round((float)$laRes["distance_km"], 2);
            $cal = (int)$laRes["calories_burned"];
            $ctx["latest_activity_session"] = "{$laRes['activity_type']}: {$dist} km, {$mins} min, {$cal} kcal ({$laRes['start_time']})";
        }
    }

    // 5. Exertion Zone
    $hrNum = is_numeric($ctx["hr_current"]) ? (int)$ctx["hr_current"] : 0;
    if ($hrNum > 140) {
        $ctx["recent_activity_zone"] = "Anaerobic / Peak Zone";
    } elseif ($hrNum > 115) {
        $ctx["recent_activity_zone"] = "Aerobic / Cardio Zone";
    } elseif ($hrNum > 95) {
        $ctx["recent_activity_zone"] = "Fat Burn / Active Zone";
    } elseif ($ctx["steps_current"] > 5000) {
        $ctx["recent_activity_zone"] = "Active Recovery";
    } else {
        $ctx["recent_activity_zone"] = "Resting / Sedentary";
    }

    // 6. Today's Hydration
    $hStmt = $conn->prepare(
        "SELECT COALESCE(SUM(COALESCE(volume_ml, intake_ml, amount_ml, 0)), 0) AS total_ml
         FROM hydration_logs 
         WHERE user_id = ? AND (log_date = ? OR date_string = ? OR DATE(logged_at) = ?)"
    );
    if ($hStmt) {
        $hStmt->bind_param("isss", $userId, $today, $today, $today);
        $hStmt->execute();
        $hRes = $hStmt->get_result()->fetch_assoc();
        $hStmt->close();
        if ($hRes) {
            $ctx["water_current_ml"] = (int)($hRes["total_ml"] ?? 0);
        }
    }

    // 7. Latest Sleep
    $sStmt = $conn->prepare(
        "SELECT duration_minutes, quality_score, log_date 
         FROM sleep_logs 
         WHERE user_id = ? 
         ORDER BY id DESC LIMIT 1"
    );
    if ($sStmt) {
        $sStmt->bind_param("i", $userId);
        $sStmt->execute();
        $sRes = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();
        if ($sRes && (int)$sRes["duration_minutes"] > 0) {
            $durM = (int)$sRes["duration_minutes"];
            $h = floor($durM / 60);
            $m = $durM % 60;
            $score = $sRes["quality_score"] ? " (Score: {$sRes['quality_score']}/100)" : "";
            $ctx["last_sleep_hours"] = "{$h}h {$m}m{$score}";
            $ctx["last_sleep_score"] = $sRes["quality_score"];
        }
    }

    // 8. Today's Logged Nutrition Macros (gym_nutrition_logs + meal_entries)
    try {
        $calTot = 0; $pTot = 0; $cTot = 0; $fTot = 0;
        $gnStmt = $conn->prepare("SELECT COALESCE(SUM(calories),0) AS cal, COALESCE(SUM(protein_g),0) AS p, COALESCE(SUM(carbs_g),0) AS c, COALESCE(SUM(fats_g),0) AS f FROM gym_nutrition_logs WHERE user_id = ? AND log_date = ?");
        if ($gnStmt) {
            $gnStmt->bind_param("is", $userId, $today);
            $gnStmt->execute();
            $gnRes = $gnStmt->get_result()->fetch_assoc();
            $gnStmt->close();
            if ($gnRes) {
                $calTot += (int)$gnRes["cal"];
                $pTot += (int)round((float)$gnRes["p"]);
                $cTot += (int)round((float)$gnRes["c"]);
                $fTot += (int)round((float)$gnRes["f"]);
            }
        }
        $meStmt = $conn->prepare("SELECT COALESCE(SUM(calories),0) AS cal, COALESCE(SUM(protein),0) AS p, COALESCE(SUM(carbs),0) AS c, COALESCE(SUM(fat),0) AS f FROM meal_entries WHERE user_id = ? AND entry_date = ?");
        if ($meStmt) {
            $meStmt->bind_param("is", $userId, $today);
            $meStmt->execute();
            $meRes = $meStmt->get_result()->fetch_assoc();
            $meStmt->close();
            if ($meRes) {
                $calTot += (int)$meRes["cal"];
                $pTot += (int)round((float)$meRes["p"]);
                $cTot += (int)round((float)$meRes["c"]);
                $fTot += (int)round((float)$meRes["f"]);
            }
        }
        if ($calTot > 0 || $pTot > 0) {
            $ctx["today_nutrition_summary"] = "{$calTot} kcal ({$pTot}g P, {$cTot}g C, {$fTot}g F)";
        }
    } catch (\Exception $e) {}

    return $ctx;
}

/**
 * Builds the comprehensive VitalSync AI system prompt enforcing query-proportional length,
 * exact macro quantity multiplication, combined meal totals, live vitals + Good/Moderate/Poor assessment.
 */
function buildGeneralAiSystemPrompt(array $ctx = [], array $ragArticles = [], $includeVitals = true, string $clientLiveContext = "") {
    $name = $ctx["name"] ?? "there";
    $ageStr = !empty($ctx["age"]) ? (string)$ctx["age"] : "Not set";
    $genderStr = !empty($ctx["gender"]) ? (string)$ctx["gender"] : "Not set";
    $heightStr = (($ctx["height_cm"] ?? "NOT_RECORDED") !== "NOT_RECORDED") ? "**{$ctx['height_cm']} cm**" : "Not set";
    $weightStr = (($ctx["weight_kg"] ?? "NOT_RECORDED") !== "NOT_RECORDED") ? "**{$ctx['weight_kg']} kg**" : "Not set";
    $bmiStr = (($ctx["bmi_value"] ?? "NOT_RECORDED") !== "NOT_RECORDED") ? "**{$ctx['bmi_value']}** ({$ctx['bmi_category']})" : "Not set";

    $hrStr = is_numeric($ctx["hr_current"] ?? null)
        ? "**{$ctx['hr_current']} bpm**" . (!empty($ctx["hr_recorded_at"]) ? " (logged {$ctx['hr_recorded_at']})" : "")
        : "No reading logged yet";
    $bpStr = (($ctx["blood_pressure"] ?? "NOT_RECORDED") !== "NOT_RECORDED")
        ? "**{$ctx['blood_pressure']}**" . (!empty($ctx["bp_recorded_at"]) ? " (logged {$ctx['bp_recorded_at']})" : "")
        : "No reading logged yet";
    $spo2Str = (($ctx["spo2"] ?? "NOT_RECORDED") !== "NOT_RECORDED")
        ? "**{$ctx['spo2']}**" . (!empty($ctx["spo2_recorded_at"]) ? " (logged {$ctx['spo2_recorded_at']})" : "")
        : "No reading logged yet";

    $stepsCurrentStr = number_format((int)($ctx["steps_current"] ?? 0));
    $stepsTargetStr = number_format((int)($ctx["steps_target"] ?? 10000));
    $waterCurrentStr = number_format((int)($ctx["water_current_ml"] ?? 0));
    $waterTargetStr = number_format((int)($ctx["water_target_ml"] ?? 2500));
    $calTargetStr = number_format((int)($ctx["calories_target"] ?? 2200));
    $protTargetStr = (int)($ctx["protein_target_g"] ?? 140);
    $sleepStr = (($ctx["last_sleep_hours"] ?? "NOT_RECORDED") !== "NOT_RECORDED") ? "**{$ctx['last_sleep_hours']}**" : "No reading logged yet";
    $nutritionStr = $ctx["today_nutrition_summary"] ?? "0 kcal logged";
    $latestActivityStr = $ctx["latest_activity_session"] ?? "No reading logged yet";
    $goal = $ctx["primary_goal"] ?? "General Wellness";

    $prompt = <<<EOT
You are VitalSync AI, a concise, accurate wellness, nutrition, and fitness assistant.

### RESPONSE LENGTH & STYLE (STRICT):
- **Short or direct questions** (single food check, single vital check, personal info lookup, yes/no question, follow-up question): Reply in **2–4 concise sentences OR at most 3 short bullet points**. Do not write long essays, intros, or filler paragraphs.
- **Detailed / breakdown requests** (multi-item meal macro breakdown, workout routine, full daily summary): Provide a clean, structured breakdown with exact numbers and totals.
- **Branding**: Refer to yourself only as VitalSync AI. Never display internal model names or raw system/prompt tags.
- **NLP Tolerance**: Understand typos, shorthand, and informal phrasing naturally without pointing them out.

### MACRO BREAKDOWNS, QUANTITY MULTIPLICATION & COMBINED TOTALS:
- Whenever asked for calories or macros of foods/meals, multiply EVERY food item by the **EXACT quantity requested** (e.g., 10 boiled eggs = 10 x 1 egg; 2 scoops whey = 2 x 1 scoop) rather than reporting single-unit values.
- Show each item's multiplied **Calories | Protein | Carbs | Fat** and always end with a **Combined Meal Total** summing all items accurately (Calories ~= Protein x 4 + Carbs x 4 + Fat x 9).
- Verified Reference Macro Baselines:
  * 1 large whole boiled egg (50g): 75 kcal | 6.3g P | 0.6g C | 5.0g F
  * 1 egg white (33g): 17 kcal | 3.6g P | 0.2g C | 0.1g F
  * 1 scoop whey protein (30g): 120 kcal | 24g P | 2g C | 1.5g F
  * 1 plate beef biryani (280g): 500 kcal | 24g P | 58g C | 18g F
  * 100g cooked white rice: 130 kcal | 2.7g P | 28g C | 0.3g F

### LIVE APP DATA & GOOD / MODERATE / POOR ASSESSMENT:
- Use the live user snapshot below directly whenever asked about personal info, vitals, sleep, hydration, steps, meals, or workouts. If a metric reads "No reading logged yet" and is not in the on-device snapshot either, state clearly that no reading is logged yet.
- For vitals and wellness checks (Heart Rate, SpO2, Hydration, Sleep), ALWAYS provide:
  1. The exact live reading (+ timestamp if available).
  2. A clear status rating: **Good**, **Moderate / Fair**, or **Poor / Low** (Resting HR 60-100 bpm = Good, 50-59 or 101-110 = Moderate/Fair, <50 or >110 = Poor/Low; SpO2 95-100% = Good, 90-94% = Moderate/Fair, <90% = Poor/Low; Hydration >=75% target = Good, 40-74% = Moderate/Fair, <40% = Poor/Low; Sleep 7-9h = Good, 6-6.9h = Moderate/Fair, <6h = Poor/Low).
  3. A brief 1-2 sentence actionable suggestion.

### LIVE USER TELEMETRY (BACKEND DB):
- Profile: {$name}, Age: {$ageStr}, Gender: {$genderStr}
- Body Stats: Height {$heightStr}, Weight {$weightStr}, BMI {$bmiStr}
- Fitness Goal: {$goal} | Daily Targets: **{$calTargetStr} kcal** | **{$protTargetStr}g protein** | **{$waterTargetStr} ml** water | **{$stepsTargetStr} steps**
- Heart Rate: {$hrStr}
- SpO2: {$spo2Str}
- Blood Pressure: {$bpStr}
- Daily Steps: **{$stepsCurrentStr}** / **{$stepsTargetStr}**
- Hydration: **{$waterCurrentStr}** / **{$waterTargetStr} ml**
- Latest Sleep: {$sleepStr}
- Today's Logged Nutrition: **{$nutritionStr}**
- Latest Activity/Cycling Session: {$latestActivityStr}
EOT;

    if ($clientLiveContext !== "") {
        $prompt .= "\n\n### LIVE ON-DEVICE APP SNAPSHOT (LATEST PREF/HEALTH CONNECT STATE):\n" . $clientLiveContext;
    }

    if (!empty($ragArticles)) {
        $prompt .= "\n\n### VERIFIED KNOWLEDGE BASE REFERENCE:\n";
        foreach ($ragArticles as $art) {
            $prompt .= "- {$art['topic']}: {$art['content']}\n";
        }
    }

    return $prompt;
}

/**
 * Intelligent zero-downtime fallback engine if Ollama is starting or unavailable.
 * Strictly enforces hardware warnings, BMI guidance, step deficits, and meal breakdowns.
 */
function generateIntelligentFallbackReply($userText, array $ctx, array $ragArticles = []) {
    $low = strtolower(trim($userText));
    $name = $ctx["name"] ?: "there";

    // 1. Critical Safety Classifier & Emergency Guardrails
    $emergencyTerms = ["chest pain", "chest pressure", "heart attack", "can't breathe", "cannot breathe", "fainting", "passed out", "blacking out", "severe breathlessness"];
    $hrNum = is_numeric($ctx["hr_current"]) ? (int)$ctx["hr_current"] : -1;
    $hasEmergency = false;
    foreach ($emergencyTerms as $t) {
        if (strpos($low, $t) !== false) {
            $hasEmergency = true;
            break;
        }
    }

    if ($hasEmergency || ($hrNum > 150 && strpos($ctx["recent_activity_zone"], "Resting") !== false)) {
        $hrNotice = $hrNum > 0 ? " Your recorded heart rate is currently **{$hrNum} bpm**." : "";
        return "⚠️ **Urgent Medical Alert**: Please stop what you're doing right now and seek immediate clinical or emergency assistance.{$hrNotice} Severe symptoms like chest pressure or acute shortness of breath require immediate physician evaluation.";
    }

    // 2. Unlogged Vitals Checks (Rule: Missing or NOT_RECORDED)
    if (preg_match('/\b(heart|rate|bpm|pulse)\b/i', $low) && $ctx["hr_current"] === "NOT_RECORDED") {
        return "Your Heart Rate has not been logged today. Tap '+ Log Vitals' on the Home screen to record your latest reading.";
    }

    if (preg_match('/\b(blood pressure|bp|systolic|diastolic)\b/i', $low) && $ctx["blood_pressure"] === "NOT_RECORDED") {
        return "Your Blood Pressure has not been logged today. Tap '+ Log Vitals' on the Home screen to record your latest reading.";
    }

    if (preg_match('/\b(spo2|oxygen|saturation|o2)\b/i', $low) && $ctx["spo2"] === "NOT_RECORDED") {
        return "Your SpO2 has not been logged today. Tap '+ Log Vitals' on the Home screen to record your latest reading.";
    }

    // 3. BMI & Weight Guidance (Rule: BMI between 25.0 and 29.9 -> Overweight guidance)
    $bmiVal = is_numeric($ctx["bmi_value"]) ? (float)$ctx["bmi_value"] : null;
    if (preg_match('/\b(bmi|weight|overweight|fat|body mass)\b/i', $low)) {
        if ($bmiVal !== null && $bmiVal >= 25.0 && $bmiVal <= 29.9) {
            return "Looking at your stats, your current BMI is **{$bmiVal}**, which falls into the **overweight** category (25.0–29.9).\n\nHere is an actionable, evidence-based plan to move toward an optimal range:\n• **Caloric Deficit**: Create a modest deficit of **300–400 kcal** below maintenance per day.\n• **Daily Activity**: Target **8,000–10,000 brisk steps** daily to stimulate non-exercise activity thermogenesis.\n• **Protein Target**: Consume **1.6–2.0g of protein** per kg of target bodyweight to preserve lean muscle tissue while losing fat.";
        } elseif ($bmiVal !== null) {
            return "Your recorded BMI is **{$bmiVal}** ({$ctx["bmi_category"]}) based on your height of **{$ctx["height_cm"]} cm** and weight of **{$ctx["weight_kg"]} kg**. Keep up structured daily activity and balanced hydration to maintain metabolic health!";
        } else {
            return "Your height or weight has not been recorded yet. Please update your profile stats in Settings > Profile so I can calculate your live BMI.";
        }
    }

    // 4. Diet Plans & Nutrition Breakdown (Rule: Breakfast, Lunch, Snack, Dinner)
    if (preg_match('/\b(diet|meal|nutrition|food plan|eating plan|what to eat|meal plan)\b/i', $low)) {
        return "Here is a structured, balanced daily meal plan designed for sustained energy, recovery, and lean muscle support:\n\n" .
               "• **Breakfast**: 3 scrambled whole eggs with spinach, 1 slice of whole-wheat toast, and black coffee or green tea (**~350 kcal, 24g protein**).\n" .
               "• **Lunch**: Grilled chicken breast or grilled paneer (**150g**) with 1 cup cooked brown rice and a mixed leafy garden salad (**~500 kcal, 38g protein**).\n" .
               "• **Snack**: Greek yogurt (**200g**) topped with a handful of fresh berries or almonds (**~180 kcal, 18g protein**).\n" .
               "• **Dinner**: Baked fish or spiced lentils/dal (**1 bowl**) paired with steamed broccoli, carrots, and a small sweet potato (**~450 kcal, 32g protein**).\n\n" .
               "Drink at least **2,500 ml** of water throughout the day to support optimal nutrient absorption.";
    }

    // 5. Activity Analysis & Step Deficit (Rule: Compare actual vs target, highlight deficit)
    $steps = (int)($ctx["steps_current"] ?? 0);
    $stepTarget = (int)($ctx["steps_target"] ?? 10000);
    $stepDeficit = max(0, $stepTarget - $steps);
    $stepsStr = number_format($steps);
    $targetStr = number_format($stepTarget);
    $deficitStr = number_format($stepDeficit);

    if (preg_match('/\b(step|steps|walk|walking|activity|distance)\b/i', $low)) {
        if ($steps === 0) {
            return "Looking at your activity, you have logged **0 / {$targetStr} steps** today, leaving a full **{$deficitStr} step deficit**.\n\n• **Tip**: Knock out a brisk 15-minute walk right now to log your first **1,500 steps** and build momentum for the day!";
        } elseif ($stepDeficit > 0) {
            $pct = round(($steps / $stepTarget) * 100);
            return "You have clocked **{$stepsStr} / {$targetStr} steps** today (**{$pct}%** of your goal), with a current deficit of **{$deficitStr} steps**.\n\n• **Pacing Advice**: A 25-minute evening stroll or a post-dinner walk will help you close the remaining **{$deficitStr} steps** smoothly!";
        } else {
            return "Outstanding effort! You've crushed your daily step target with **{$stepsStr} / {$targetStr} steps** logged today. Excellent cardiovascular discipline!";
        }
    }

    // 6. Hydration Slot
    $water = (int)($ctx["water_current_ml"] ?? 0);
    $waterTarget = (int)($ctx["water_target_ml"] ?? 2500);
    if (preg_match('/\b(water|hydrate|hydration|drink|fluid)\b/i', $low)) {
        $waterStr = number_format($water);
        $wTargetStr = number_format($waterTarget);
        $wPct = $waterTarget > 0 ? round(($water / $waterTarget) * 100) : 0;
        return "Hydration Status: You have logged **{$waterStr} / {$wTargetStr} ml** today (**{$wPct}%** achieved). " .
               ($water < $waterTarget ? "Keep sipping an extra **250 ml** every couple of hours to keep intracellular hydration and muscle endurance high." : "You have hit your hydration goal—stellar work!");
    }

    // 7. General Status Check
    if (preg_match('/\b(status|overview|how am i doing|progress|summary|check|vitals)\b/i', $low)) {
        $hrDisplay = is_numeric($ctx["hr_current"]) ? "**{$ctx["hr_current"]} bpm**" : "NOT_RECORDED";
        $bpDisplay = $ctx["blood_pressure"] !== "NOT_RECORDED" ? "**{$ctx["blood_pressure"]}**" : "NOT_RECORDED";
        $spo2Display = $ctx["spo2"] !== "NOT_RECORDED" ? "**{$ctx["spo2"]}**" : "NOT_RECORDED";

        return "Here is your live VitalSync health overview:\n" .
               "• **Daily Steps**: **{$stepsStr} / {$targetStr} steps** (" . ($steps === 0 ? "Deficit: **{$targetStr} steps**" : "Deficit: **{$deficitStr} steps**") . ")\n" .
               "• **Hydration**: **" . number_format($water) . " / " . number_format($waterTarget) . " ml**\n" .
               "• **Heart Rate**: {$hrDisplay}\n" .
               "• **Blood Pressure**: {$bpDisplay}\n" .
               "• **SpO2**: {$spo2Display}\n\n" .
               ($hrDisplay === "NOT_RECORDED" ? "Tip: Tap '+ Log Vitals' on Home to log your Heart Rate, SpO2, or Blood Pressure today." : "Your vitals are tracking steadily.");
    }

    // 8. RAG Knowledge Base Retrieval Fallback
    if (!empty($ragArticles)) {
        $art = $ragArticles[0];
        return "Here is verified reference information regarding **{$art['topic']}**:\n\n{$art['content']}\n\n• Maintain consistent hydration (**" . number_format($water) . " ml** logged) and daily movement to support optimal physiological recovery!";
    }

    // 9. Conversational Default
    return "Hello {$name}! I'm tracking your activity: **{$stepsStr} / {$targetStr} steps** and **" . number_format($water) . " ml** water logged today. Ask me anything about your steps, diet plan, workout readiness, or live vitals!";
}
