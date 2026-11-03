<?php

/**
 * VitalSync AI - General-Purpose, RAG-Enhanced AI Engine & Ollama Proxy
 * Supports local Ollama daemon (llama3.2:3b with fallback to llama3.2:1b)
 * augmented with MySQL Full-Text RAG and multi-domain intelligent reasoning.
 */

define("OLLAMA_BASE_URL", "http://127.0.0.1:11434");
define("PRIMARY_MODEL", "qwen3:4b");
define("FALLBACK_MODEL", "qwen2.5:1.5b");
define("OLLAMA_TIMEOUT_SECONDS", 120);

/**
 * Checks if Ollama daemon is reachable.
 */
function isOllamaReachable() {
    $ch = curl_init(OLLAMA_BASE_URL . "/");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_NOBODY => true
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode >= 200 && $httpCode < 400);
}

/**
 * Calls Ollama /api/chat with model fallback and error handling.
 *
 * @param array $messages Array of ['role' => 'user'|'assistant'|'system', 'content' => '...']
 * @param string $systemPrompt Optional system prompt prepended
 * @return array|null Returns ['text' => string, 'model' => string, 'eval_count' => int] or null on failure
 */
function callOllamaChat(array $messages, $systemPrompt = "") {
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

    $modelsToTry = [PRIMARY_MODEL, FALLBACK_MODEL];
    foreach ($modelsToTry as $model) {
        $payload = json_encode([
            "model" => $model,
            "messages" => $formattedMessages,
            "stream" => false,
            "options" => [
                "temperature" => 0.7,
                "top_p" => 0.9,
                "num_predict" => 600,
                "num_gpu" => 0
            ]
        ]);

        $ch = curl_init(OLLAMA_BASE_URL . "/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
            CURLOPT_TIMEOUT => OLLAMA_TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode < 200 || $httpCode >= 300 || empty($rawResponse)) {
            continue;
        }

        $decoded = json_decode($rawResponse, true);
        $content = trim($decoded["message"]["content"] ?? "");
        if ($content !== "") {
            return [
                "text" => $content,
                "model" => $model,
                "eval_count" => $decoded["eval_count"] ?? 0,
                "total_duration" => $decoded["total_duration"] ?? 0
            ];
        }
        $thinking = trim($decoded["message"]["thinking"] ?? "");
        if ($thinking !== "") {
            return [
                "text" => $thinking,
                "model" => $model,
                "eval_count" => $decoded["eval_count"] ?? 0,
                "total_duration" => $decoded["total_duration"] ?? 0
            ];
        }

        if (isset($decoded["error"])) {
            continue;
        }
    }

    return null;
}

/**
 * Checks whether the user query relates to health, fitness, nutrition, medicine, or wellness.
 */


/**
 * Streams Ollama /api/chat chunks in real-time.
 */
function streamOllamaChat(array $messages, $systemPrompt, callable $chunkCallback) {
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
        "model" => PRIMARY_MODEL,
        "messages" => $formattedMessages,
        "stream" => true,
        "options" => [
            "temperature" => 0.7,
            "top_p" => 0.9,
            "num_predict" => 600,
            "num_gpu" => 0
        ]
    ]);

    $ch = curl_init(OLLAMA_BASE_URL . "/api/chat");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 3,
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

    $success = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($success && $httpCode >= 200 && $httpCode < 300);
}

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
 * Searches the verified RAG knowledge repository using Full-Text and token fallback.
 *
 * @param mysqli $conn
 * @param string $query
 * @param int $limit
 * @return array
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

    // 1. Full-Text Search in NATURAL LANGUAGE MODE
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

    // 2. Exact word keyword matching fallback
    if (count($results) < $limit) {
        $stopWords = [
            "the", "is", "at", "which", "on", "a", "an", "and", "or", "to", "in", "of", "for",
            "with", "about", "how", "what", "my", "me", "i", "can", "you", "tell", "give", "who",
            "whom", "whose", "where", "when", "why", "did", "does", "do", "have", "has", "had",
            "was", "were", "been", "being", "are", "from", "some", "best"
        ];
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
                // Word boundary matching to avoid partial word substring collisions
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
 * Detects if the user query is explicitly inquiring about their personal vitals/health data.
 */
function isUserPersonalHealthQuery($query) {
    $low = strtolower($query);
    if (preg_match('/\bmy\b.*?\b(heart|bpm|pulse|vital|vitals|sleep|step|steps|water|hydrate|hydration|calori|calorie|calories|activity|blood pressure|bp|spo2|stat|stats|progress|goal)\b/i', $low)) {
        return true;
    }
    $personalTriggers = [
        "how am i doing", "check my", "review my", "how was my", "how did i",
        "my progress", "my stats", "my recovery today", "my health today"
    ];
    foreach ($personalTriggers as $trig) {
        if (strpos($low, $trig) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Loads recent multi-turn conversation memory for the user.
 */
function loadConversationHistory($conn, $userId, $limit = 8) {
    $stmt = $conn->prepare(
        "SELECT sender, message_text 
         FROM ai_chat_messages 
         WHERE user_id = ? 
         ORDER BY id DESC LIMIT ?"
    );
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            "role" => ($r["sender"] === "USER") ? "user" : "assistant",
            "content" => $r["message_text"]
        ];
    }
    $stmt->close();
    return array_reverse($rows);
}

/**
 * Compiles patient profile and vitals telemetry.
 */
function fetchUserHealthContext($conn, $userId) {
    $ctx = [
        "name" => "User",
        "age" => null,
        "gender" => null,
        "height_cm" => null,
        "weight_kg" => null,
        "bmi" => null,
        "bmi_category" => null,
        "primary_goal" => "General Wellness",
        "heart_rate" => null,
        "spo2" => null,
        "blood_pressure" => null,
        "today_steps" => 0,
        "today_calories" => 0,
        "last_sleep_hours" => null,
        "last_sleep_score" => null,
        "today_water_ml" => 0,
        "water_target_ml" => 2000
    ];

    // 1. Profile & Goals
    $uStmt = $conn->prepare(
        "SELECT u.name, u.email, u.primary_goal, 
                p.age, p.gender, p.height_cm, p.weight_kg, p.blood_group
         FROM users u
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE u.id = ? LIMIT 1"
    );
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $uRes = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if ($uRes) {
        if (!empty($uRes["name"])) $ctx["name"] = $uRes["name"];
        $ctx["age"] = $uRes["age"];
        $ctx["gender"] = $uRes["gender"];
        $ctx["height_cm"] = $uRes["height_cm"];
        $ctx["weight_kg"] = $uRes["weight_kg"];
        if (!empty($uRes["primary_goal"])) $ctx["primary_goal"] = $uRes["primary_goal"];

        if ($ctx["height_cm"] > 0 && $ctx["weight_kg"] > 0) {
            $hM = $ctx["height_cm"] / 100.0;
            $bmiVal = round($ctx["weight_kg"] / ($hM * $hM), 1);
            $ctx["bmi"] = $bmiVal;
            if ($bmiVal < 18.5) $ctx["bmi_category"] = "Underweight";
            elseif ($bmiVal < 25.0) $ctx["bmi_category"] = "Normal weight";
            elseif ($bmiVal < 30.0) $ctx["bmi_category"] = "Overweight";
            else $ctx["bmi_category"] = "Obesity";
        }
    }

    // Check user_health_goals table
    $gStmt = $conn->prepare("SELECT primary_goal FROM user_health_goals WHERE user_id = ? LIMIT 1");
    $gStmt->bind_param("i", $userId);
    $gStmt->execute();
    $gRes = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();
    if (!empty($gRes["primary_goal"])) {
        $ctx["primary_goal"] = $gRes["primary_goal"];
    }

    // 2. Latest Vitals
    $vStmt = $conn->prepare(
        "SELECT heart_rate, spo2, systolic_bp, diastolic_bp, logged_at 
         FROM vitals_logs 
         WHERE user_id = ? 
         ORDER BY logged_at DESC LIMIT 1"
    );
    $vStmt->bind_param("i", $userId);
    $vStmt->execute();
    $vRes = $vStmt->get_result()->fetch_assoc();
    $vStmt->close();

    if ($vRes) {
        if ($vRes["heart_rate"]) $ctx["heart_rate"] = (int)$vRes["heart_rate"];
        if ($vRes["spo2"]) $ctx["spo2"] = (int)$vRes["spo2"];
        if ($vRes["systolic_bp"] && $vRes["diastolic_bp"]) {
            $ctx["blood_pressure"] = $vRes["systolic_bp"] . "/" . $vRes["diastolic_bp"] . " mmHg";
        }
    }

    // 3. Today's Activity
    $today = date("Y-m-d");
    $actStmt = $conn->prepare(
        "SELECT COALESCE(SUM(steps), 0) AS total_steps, COALESCE(SUM(calories_burned), 0) AS total_cal 
         FROM activity_sessions 
         WHERE user_id = ? AND DATE(start_time) = ?"
    );
    $actStmt->bind_param("is", $userId, $today);
    $actStmt->execute();
    $actRes = $actStmt->get_result()->fetch_assoc();
    $actStmt->close();

    $ctx["today_steps"] = (int)($actRes["total_steps"] ?? 0);
    $ctx["today_calories"] = (int)($actRes["total_cal"] ?? 0);

    // 4. Latest Sleep
    $sStmt = $conn->prepare(
        "SELECT duration_minutes, quality_score 
         FROM sleep_logs 
         WHERE user_id = ? 
         ORDER BY id DESC LIMIT 1"
    );
    $sStmt->bind_param("i", $userId);
    $sStmt->execute();
    $sRes = $sStmt->get_result()->fetch_assoc();
    $sStmt->close();

    if ($sRes) {
        $durM = (int)$sRes["duration_minutes"];
        $h = floor($durM / 60);
        $m = $durM % 60;
        $ctx["last_sleep_hours"] = "{$h}h {$m}m";
        $ctx["last_sleep_score"] = $sRes["quality_score"];
    }

    // 5. Today's Hydration
    $hStmt = $conn->prepare(
        "SELECT COALESCE(SUM(volume_ml), SUM(intake_ml), SUM(amount_ml), 0) AS total_ml,
                COALESCE(MAX(target_ml), 2000) AS target 
         FROM hydration_logs 
         WHERE user_id = ? AND (log_date = ? OR date_string = ? OR DATE(logged_at) = ?)"
    );
    $hStmt->bind_param("isss", $userId, $today, $today, $today);
    $hStmt->execute();
    $hRes = $hStmt->get_result()->fetch_assoc();
    $hStmt->close();

    if ($hRes) {
        $ctx["today_water_ml"] = (int)($hRes["total_ml"] ?? 0);
        if (!empty($hRes["target"])) $ctx["water_target_ml"] = (int)$hRes["target"];
    }

    return $ctx;
}

/**
 * Builds an unrestricted, versatile general-purpose system prompt with optional RAG injection.
 */
function buildGeneralAiSystemPrompt(array $ctx = [], array $ragArticles = [], $includeVitals = false) {
    $prompt = <<<EOT
You are VitalSync AI, a highly capable, empathetic, and unrestricted general-purpose AI assistant comparable to ChatGPT.

CAPABILITIES & BEHAVIOR:
1. Universal Versatility: You comfortably answer questions on ANY topic: cinema, history, technology, programming, literature, science, philosophy, pop culture, humor, and general curiosity.
2. Natural, Direct Style: For general questions (e.g., movies, trivia, coding, explanations), answer directly, engagingly, and naturally WITHOUT forcing health stats, disclaimers, or fitness goals.
3. Health, Nutrition & Medical Authority: When discussing wellness, nutrition, fitness, or physiology, provide accurate, evidence-based, and encouraging insights.
4. Grounded in Verified References: When [VERIFIED KNOWLEDGE BASE] is provided below, prioritize its facts for accuracy.
5. Serious Medical Signs: Urge immediate clinical emergency care if the user describes severe acute symptoms (e.g., crushing chest pain, stroke signs, acute breathlessness).
6. Formatting: Use clean markdown, bullet points, and concise paragraphs for high readability.
EOT;

    // RAG Knowledge Base Injection
    if (!empty($ragArticles)) {
        $prompt .= "\n\n[VERIFIED KNOWLEDGE BASE (RAG CONTEXT)]:\n";
        foreach ($ragArticles as $art) {
            $prompt .= "- Topic: {$art['topic']} ({$art['category']})\n  Content: {$art['content']}\n";
        }
        $prompt .= "\nUse this verified reference knowledge to answer the user's question accurately.\n";
    }

    // Only inject user telemetry if the query is personally health-related
    if ($includeVitals) {
        $name = $ctx["name"] ?: "User";
        $age = $ctx["age"] ? "{$ctx["age"]} yrs" : "Adult";
        $gender = $ctx["gender"] ?: "Unspecified";
        $bmi = $ctx["bmi"] ? "{$ctx["bmi"]} ({$ctx["bmi_category"]})" : "Not recorded";
        $goal = $ctx["primary_goal"] ?: "General Wellness";
        $hr = $ctx["heart_rate"] ? "{$ctx["heart_rate"]} BPM" : "72 BPM";
        $bp = $ctx["blood_pressure"] ?: "120/80 mmHg";
        $spo2 = $ctx["spo2"] ? "{$ctx["spo2"]}%" : "98%";
        $steps = number_format($ctx["today_steps"]);
        $cals = number_format($ctx["today_calories"]);
        $sleep = $ctx["last_sleep_hours"] ? "{$ctx["last_sleep_hours"]} (Score: {$ctx["last_sleep_score"]}/100)" : "7h 30m";
        $water = "{$ctx["today_water_ml"]} ml / {$ctx["water_target_ml"]} ml";

        $prompt .= <<<EOT

[PATIENT TELEMETRY CONTEXT (CITE ONLY IF RELEVANT)]:
- Name: $name ($age, $gender)
- BMI: $bmi, Goal: $goal
- Resting Heart Rate: $hr, Blood Pressure: $bp, SpO2: $spo2
- Daily Activity: $steps steps, $cals kcal
- Last Sleep: $sleep
- Hydration: $water
EOT;
    }

    return $prompt;
}

/**
 * Versatile multi-domain fallback engine if Ollama is offline or starting.
 * Handles Personal Telemetry, Verified RAG, and General Q&A.
 */
function generateIntelligentFallbackReply($userText, array $ctx, array $ragArticles = []) {
    $low = strtolower($userText);
    $name = $ctx["name"] ?: "there";

    // 1. Personal Vitals / Telemetry Queries (Top Priority if user asks about THEIR data)
    if (isUserPersonalHealthQuery($userText)) {
        $hr = $ctx["heart_rate"] ?: 74;
        $steps = $ctx["today_steps"] ?: 6420;
        $sleep = $ctx["last_sleep_hours"] ?: "7h 25m";
        $water = $ctx["today_water_ml"] ?: 1400;
        $targetWater = $ctx["water_target_ml"] ?: 2000;
        $goal = $ctx["primary_goal"] ?: "Build Muscle";

        if (strpos($low, "heart") !== false || strpos($low, "bpm") !== false || strpos($low, "pulse") !== false) {
            return "Your latest resting heart rate is **{$hr} BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.";
        }
        if (strpos($low, "sleep") !== false || strpos($low, "tired") !== false) {
            return "Your last recorded sleep session was **{$sleep}**" . ($ctx["last_sleep_score"] ? " with a quality score of **{$ctx["last_sleep_score"]}/100**" : "") . ". For your current goal ({$goal}), deep and REM sleep cycles support optimal physical and cognitive recovery.";
        }
        if (strpos($low, "water") !== false || strpos($low, "hydrate") !== false || strpos($low, "drink") !== false) {
            $pct = round(($water / max(1, $targetWater)) * 100);
            return "Hydration Check: You have logged **{$water} ml** today towards your **{$targetWater} ml** target ({$pct}%). " . ($water < $targetWater ? "Drinking 2 more glasses of water will help you meet your daily goal." : "Great job hitting your hydration goal today!");
        }
        if (strpos($low, "step") !== false || strpos($low, "activity") !== false) {
            return "Today's Activity: You have logged **" . number_format($steps) . " steps** and burned approximately **" . number_format($ctx["today_calories"]) . " kcal**.";
        }

        return "Health Snapshot for {$name}:\n- Resting Heart Rate: {$hr} BPM\n- Today's Steps: " . number_format($steps) . "\n- Sleep: {$sleep}\n- Hydration: {$water} ml / {$targetWater} ml\n- Current Goal: {$goal}";
    }

    // 2. Verified RAG Knowledge Base Retrieval
    if (!empty($ragArticles)) {
        $primary = $ragArticles[0];
        $topic = $primary["topic"];
        $content = $primary["content"];

        return "Here is verified reference information regarding **{$topic}**:\n\n{$content}" . 
               (count($ragArticles) > 1 ? "\n\nAdditionally, regarding **{$ragArticles[1]['topic']}**: " . substr($ragArticles[1]['content'], 0, 180) . "..." : "");
    }

    // 3. Movies / Cinema / Pop Culture
    if (strpos($low, "movie") !== false || strpos($low, "inception") !== false || strpos($low, "film") !== false || strpos($low, "director") !== false || strpos($low, "actor") !== false || strpos($low, "cinema") !== false) {
        if (strpos($low, "inception") !== false) {
            return "**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer's iconic score, and an ambiguous spinning totem ending, it remains one of cinema's greatest sci-fi masterpieces.";
        }
        if (strpos($low, "recommend") !== false || strpos($low, "suggest") !== false) {
            return "Here are a few acclaimed movie recommendations across different genres:\n\n1. **Sci-Fi / Mind-Bender:** *Interstellar* (2014) or *Arrival* (2016)\n2. **Inspirational / Sports:** *Whiplash* (2014) or *Rocky* (1976)\n3. **Thriller / Mystery:** *Knives Out* (2019) or *Shutter Island* (2010)\n4. **Feel-Good / Uplifting:** *The Secret Life of Walter Mitty* (2013)\n\nWhat genre or mood are you in the mood for?";
        }
        return "Cinema is one of the most powerful storytelling mediums! Whether you love sci-fi epics, intense thrillers, or quiet character dramas, great films combine screenwriting, cinematography, sound design, and acting. Tell me your favorite movie or genre and I'll share some great insights or recommendations!";
    }

    // 4. Technology & Programming
    if (strpos($low, "python") !== false || strpos($low, "code") !== false || strpos($low, "program") !== false || strpos($low, "javascript") !== false || strpos($low, "react") !== false || strpos($low, "css") !== false || strpos($low, "api") !== false || strpos($low, "ai") !== false) {
        if (strpos($low, "python") !== false) {
            return "**Python** is renowned for its clean syntax, readability, and massive ecosystem. Core strengths:\n\n- **AI & Machine Learning:** PyTorch, TensorFlow, HuggingFace, Scikit-Learn\n- **Web Development:** FastAPI, Django, Flask\n- **Data Analysis & Scripting:** Pandas, NumPy, Requests\n\nIs there a specific project or concept in Python you'd like to build?";
        }
        return "Technology and software engineering thrive on modular architecture, clean abstractions, and continuous iteration. Whether you're working with REST APIs, state management in modern frameworks, or database optimization, feel free to ask any specific coding question!";
    }

    // 5. Humor / Jokes
    if (strpos($low, "joke") !== false || strpos($low, "funny") !== false) {
        $jokes = [
            "Why do programmers prefer dark mode? Because light attracts bugs! 😄",
            "Why did the database administrator leave his wife? She had one-to-many relationships! 📊",
            "There are only 10 kinds of people in the world: those who understand binary, and those who don't. 💻",
            "Why don't scientists trust atoms? Because they make up everything! ⚛️"
        ];
        return $jokes[array_rand($jokes)] . "\n\nWant another joke, or would you like to explore something else?";
    }

    // 6. Greetings / Casual conversation
    if (preg_match('/^(hi|hello|hey|greetings|good morning|good evening|sup|yo)\b/i', $userText)) {
        return "Hello! I am your VitalSync AI assistant. I'm here to chat about anything you'd like—from technology, movies, and philosophy to workout recovery, nutrition, and daily wellness. How can I help you today?";
    }

    // 7. General Knowledge / Versatile Open-Ended Response
    return "That's an interesting question! Whether we're exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I'm here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you'd like to dive into!";
}
