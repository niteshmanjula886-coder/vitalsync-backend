<?php

header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

$authPath = file_exists(__DIR__ . '/config/auth_middleware.php')
    ? __DIR__ . '/config/auth_middleware.php'
    : __DIR__ . '/../config/auth_middleware.php';
require_once $authPath;

$conn->query("
    CREATE TABLE IF NOT EXISTS gym_nutrition_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        meal_type VARCHAR(50) NOT NULL,
        food_description TEXT NOT NULL,
        calories INT NOT NULL DEFAULT 0,
        protein_g FLOAT NOT NULL DEFAULT 0,
        carbs_g FLOAT NOT NULL DEFAULT 0,
        fats_g FLOAT NOT NULL DEFAULT 0,
        ai_feedback TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$userId = resolveUserIdOrDie($conn, true);
$input = getInputData();

$mealType = trim($input["meal_type"] ?? $input["mealType"] ?? "Lunch");
$foodDesc = trim($input["food_description"] ?? $input["food_name"] ?? $input["foodDescription"] ?? "");
$saveManually = !empty($input["save_manually"]) || !empty($input["saveManually"]);

if (empty($foodDesc)) {
    respondJson(400, [
        "success" => false,
        "message" => "Please enter food items or meal description to log."
    ]);
}

$manualCalories = isset($input["calories"]) ? (int)$input["calories"] : 0;
$manualProtein = isset($input["protein_g"]) ? (float)$input["protein_g"] : (isset($input["protein"]) ? (float)$input["protein"] : 0.0);
$manualCarbs = isset($input["carbs_g"]) ? (float)$input["carbs_g"] : (isset($input["carbs"]) ? (float)$input["carbs"] : 0.0);
$manualFats = isset($input["fats_g"]) ? (float)$input["fats_g"] : (isset($input["fats"]) ? (float)$input["fats"] : 0.0);

/**
 * 1. Multi-Item Tokenizer:
 * Splits incoming user food description into individual item tokens by
 * commas, '+', '&', ' and ', or newlines.
 * E.g. "150g chicken, 200g rice, 1 cup coffee" -> ["150g chicken", "200g rice", "1 cup coffee"]
 */
function splitFoodItems($text) {
    $normalized = preg_replace('/\s+(?:and|\&|\+)\s+/i', ',', $text);
    $normalized = str_replace(["\r\n", "\n", "\r", "+"], ',', $normalized);
    $parts = explode(',', $normalized);
    $items = [];
    foreach ($parts as $p) {
        $trimmed = trim($p);
        if ($trimmed !== '') {
            $items[] = $trimmed;
        }
    }
    return !empty($items) ? $items : [trim($text)];
}

/**
 * 2. Quantity & Unit Extractor:
 * Extracts parsed quantity, unit, and food name from raw token.
 * Handles prefix (e.g. "150g chicken breast", "2 cups coffee")
 * and suffix (e.g. "chicken breast 150g", "rice 200 grams").
 */
function extractQuantityUnitFood($rawItem) {
    $token = trim($rawItem);
    // Prefix match: e.g. 150g chicken breast, 1.5 scoops whey, 2 cups coffee, 3 eggs
    if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(g|gms|gram|grams|kg|kilo|kilos|kilogram|kilograms|ml|milliliters?|l|liters?|scoops?|cups?|bowls?|pieces?|slices?|pack|packets?|plates?)?\s*(?:of\s+)?(.*)$/i', $token, $m) && !empty(trim($m[3]))) {
        return [(float)$m[1], strtolower($m[2] ?? ''), trim($m[3])];
    }
    // Suffix match: e.g. chicken breast 150g, rice 200 grams, creatine 5g
    if (preg_match('/^(.*?)\s+(\d+(?:\.\d+)?)\s*(g|gms|gram|grams|kg|kilo|kilos|kilogram|kilograms|ml|milliliters?|l|liters?|scoops?|cups?|bowls?|pieces?|slices?|pack|packets?)$/i', $token, $m) && !empty(trim($m[1]))) {
        return [(float)$m[2], strtolower($m[3] ?? ''), trim($m[1])];
    }
    // Standalone number prefix without unit, e.g. "3 eggs", "2 chapati"
    if (preg_match('/^\s*(\d+(?:\.\d+)?)\s+(.*)$/i', $token, $m) && !empty(trim($m[2]))) {
        return [(float)$m[1], '', trim($m[2])];
    }
    return [1.0, '', $token];
}

/**
 * 3. Base Nutritional Dictionary & Gram-Scaling Formula:
 * Formula: Scaled Macro = (Base Value per 100g * Parsed Grams) / 100
 */
function parseAndScaleItem($rawToken) {
    list($qty, $unit, $food) = extractQuantityUnitFood($rawToken);
    $rawLower = strtolower(trim($rawToken));

    // Determine Grams or Serving Multiplier
    $isGrams = in_array($unit, ['g', 'gms', 'gram', 'grams', 'kg', 'kilo', 'kilos', 'kilogram', 'kilograms']);
    $parsedGrams = null;
    if ($isGrams) {
        $parsedGrams = in_array($unit, ['kg', 'kilo', 'kilos', 'kilogram', 'kilograms']) ? ($qty * 1000.0) : $qty;
    }

    $isMl = in_array($unit, ['ml', 'milliliter', 'milliliters', 'l', 'liter', 'liters']);
    $parsedMl = null;
    if ($isMl) {
        $parsedMl = in_array($unit, ['l', 'liter', 'liters']) ? ($qty * 1000.0) : $qty;
    }

    // 1. Creatine Monohydrate (0 kcal, 0g P, 0g C, 0g F)
    if (preg_match('/\bcreatine\b/i', $rawLower)) {
        return [
            "matched" => true,
            "name" => "Creatine Monohydrate",
            "calories" => 0,
            "protein_g" => 0.0,
            "carbs_g" => 0.0,
            "fats_g" => 0.0,
            "ai_feedback" => "Creatine monohydrate provides 0 kcal and 0g macros; it boosts ATP regeneration, muscle strength, and intracellular hydration."
        ];
    }

    // 2. Black Coffee / Espresso / Americano (5 kcal, 0.3g P, 0g C, 0g F)
    if (preg_match('/\b(?:black\s*coffee|espresso|americano)\b/i', $rawLower)) {
        $cups = ($unit === 'cup' || $unit === 'cups' || $unit === '') ? $qty : 1.0;
        return [
            "matched" => true,
            "name" => "Black Coffee",
            "calories" => (int)round(5 * $cups),
            "protein_g" => round(0.3 * $cups, 1),
            "carbs_g" => 0.0,
            "fats_g" => 0.0,
            "ai_feedback" => "Black coffee provides virtually zero calories with an effective caffeine boost for metabolic rate and training focus."
        ];
    }

    // 3. Hydration / Green Tea / Water / BCAA / Electrolytes
    if (preg_match('/\b(?:water|green\s*tea|herbal\s*tea|lemon\s*water)\b/i', $rawLower)) {
        return [
            "matched" => true,
            "name" => "Hydration / Green Tea",
            "calories" => 2,
            "protein_g" => 0.1,
            "carbs_g" => 0.3,
            "fats_g" => 0.0,
            "ai_feedback" => "Zero-calorie hydration supporting cellular hydration and metabolic recovery."
        ];
    }

    if (preg_match('/\b(?:bcaa|electrolytes?|amino\s*acids?)\b/i', $rawLower)) {
        return [
            "matched" => true,
            "name" => "BCAA / Electrolytes",
            "calories" => 5,
            "protein_g" => 0.0,
            "carbs_g" => 1.0,
            "fats_g" => 0.0,
            "ai_feedback" => "Intra-workout hydration supporting electrolyte balance and muscular endurance."
        ];
    }

    if (preg_match('/\bpre[\s-]*workout\b/i', $rawLower)) {
        return [
            "matched" => true,
            "name" => "Pre-Workout",
            "calories" => 10,
            "protein_g" => 0.0,
            "carbs_g" => 2.0,
            "fats_g" => 0.0,
            "ai_feedback" => "High-caffeine pre-workout blend supporting training focus and muscular pump."
        ];
    }

    // 4. Regular Milk Coffee / Filter Coffee / Tea / Chai (1 cup ~150-200ml: 75 kcal, 2.5g P, 9g C, 3g F)
    if (preg_match('/\b(?:coffee|filter\s*coffee|milk\s*coffee|cappuccino|latte|tea|chai|milk\s*tea)\b/i', $rawLower)) {
        $cups = ($unit === 'cup' || $unit === 'cups' || $unit === '') ? $qty : ($parsedMl ? $parsedMl / 180.0 : 1.0);
        return [
            "matched" => true,
            "name" => "Coffee / Tea with Milk",
            "calories" => (int)round(75 * $cups),
            "protein_g" => round(2.5 * $cups, 1),
            "carbs_g" => round(9.0 * $cups, 1),
            "fats_g" => round(3.0 * $cups, 1),
            "ai_feedback" => "Provides a quick caffeine boost for focus and workout energy with minimal protein."
        ];
    }

    // 5. Chicken Breast (Grounded USDA per 100g: 165 kcal, 31.0g P, 0.0g C, 3.6g F)
    // Formula: Scaled Macro = (Base per 100g * parsedGrams) / 100
    // Example: 150g -> 247.5 kcal, 46.5g P, 0g C, 5.4g F
    if (preg_match('/\b(?:chicken\s*breast|chicken\s*fillet|chicken\s*tikka|grilled\s*chicken|boiled\s*chicken)\b/i', $rawLower) ||
        (strpos($rawLower, "chicken") !== false && strpos($rawLower, "curry") === false && strpos($rawLower, "gravy") === false)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 150.0); // 1 medium breast is ~150g if no unit specified
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Chicken Breast (" . (int)$grams . "g)",
            "calories" => (int)round(165.0 * $scale),
            "protein_g" => round(31.0 * $scale, 1),
            "carbs_g" => 0.0,
            "fats_g" => round(3.6 * $scale, 1),
            "ai_feedback" => "Ultra-lean poultry protein (31g per 100g) with near-zero carbs, optimal for lean muscle synthesis."
        ];
    }

    // 6. Cooked White/Brown Rice (Grounded USDA per 100g: 130 kcal, 2.7g P, 28.7g C, 0.3g F)
    // Formula: Scaled Macro = (Base per 100g * parsedGrams) / 100
    // Example: 200g -> 260 kcal, 5.4g P, 57.4g C, 0.6g F; 150g (1 cup) -> 195 kcal, 4.0g P, 43.0g C, 0.4g F
    if (preg_match('/\brice\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 150.0); // 1 cup cooked rice is ~150g
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Cooked Rice (" . (int)$grams . "g)",
            "calories" => (int)round(130.0 * $scale),
            "protein_g" => round(2.7 * $scale, 1),
            "carbs_g" => round(28.7 * $scale, 1),
            "fats_g" => round(0.3 * $scale, 1),
            "ai_feedback" => "Easily digestible complex carbohydrates supporting muscle glycogen replenishment."
        ];
    }

    // 7. Mutton Gravy / Mutton Curry / Lamb Curry (Base per 100g: 180 kcal, 12.5g P, 4.0g C, 12.5g F; 200g bowl: 360 kcal, 25g P, 8g C, 25g F)
    if (preg_match('/\b(?:mutton|lamb|goat)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 200.0); // 1 bowl is ~200g
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Mutton Curry / Gravy (" . (int)$grams . "g)",
            "calories" => (int)round(180.0 * $scale),
            "protein_g" => round(12.5 * $scale, 1),
            "carbs_g" => round(4.0 * $scale, 1),
            "fats_g" => round(12.5 * $scale, 1),
            "ai_feedback" => "High in complete animal protein, iron, and zinc for muscle repair, though rich in saturated fats from the gravy."
        ];
    }

    // 8. Chicken Gravy / Chicken Curry (Base per 100g: 140 kcal, 13.5g P, 4.0g C, 7.5g F; 200g bowl: 280 kcal, 27g P, 8g C, 15g F)
    if (preg_match('/\bchicken\s*(?:gravy|curry|masala|butter\s*chicken|tikka\s*masala|korma|roast)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 200.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Chicken Curry / Gravy (" . (int)$grams . "g)",
            "calories" => (int)round(140.0 * $scale),
            "protein_g" => round(13.5 * $scale, 1),
            "carbs_g" => round(4.0 * $scale, 1),
            "fats_g" => round(7.5 * $scale, 1),
            "ai_feedback" => "Rich in complete poultry protein with moderate fats from the gravy supporting muscular rebuilding."
        ];
    }

    // 9. Fish Curry / Fish Fry / Salmon / Tuna (Base per 100g: 160 kcal, 16.0g P, 3.3g C, 8.7g F; 150g serving: 240 kcal, 24g P, 5g C, 13g F)
    if (preg_match('/\b(?:fish|salmon|tuna|pomfret|rohu|tilapia)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 150.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Fish Serving (" . (int)$grams . "g)",
            "calories" => (int)round(160.0 * $scale),
            "protein_g" => round(16.0 * $scale, 1),
            "carbs_g" => round(3.3 * $scale, 1),
            "fats_g" => round(8.7 * $scale, 1),
            "ai_feedback" => "Quality marine protein rich in omega-3 fatty acids for anti-inflammatory recovery and joint health."
        ];
    }

    // 10. Egg White (1 white: 17 kcal, 3.6g P, 0.2g C, 0.1g F; per 100g: 52 kcal, 11g P, 0.7g C, 0.2g F)
    if (preg_match('/egg\s*whites?/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Egg Whites (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(52.0 * $scale),
                "protein_g" => round(11.0 * $scale, 1),
                "carbs_g" => round(0.7 * $scale, 1),
                "fats_g" => round(0.2 * $scale, 1),
                "ai_feedback" => "Pure bioavailable albumin protein with zero fat and minimal calories."
            ];
        }
        $count = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$count Egg White(s)",
            "calories" => 17 * $count,
            "protein_g" => round(3.6 * $count, 1),
            "carbs_g" => round(0.2 * $count, 1),
            "fats_g" => round(0.1 * $count, 1),
            "ai_feedback" => "Pure bioavailable albumin protein with zero fat and minimal calories."
        ];
    }

    // 11. Whole Boiled Egg (1 whole: 78 kcal, 6.3g P, 0.6g C, 5.3g F; per 100g: 155 kcal, 12.6g P, 1.2g C, 10.6g F)
    if (preg_match('/\beggs?\b/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Eggs (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(155.0 * $scale),
                "protein_g" => round(12.6 * $scale, 1),
                "carbs_g" => round(1.2 * $scale, 1),
                "fats_g" => round(10.6 * $scale, 1),
                "ai_feedback" => "Gold-standard complete protein source with choline and healthy fats."
            ];
        }
        $count = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$count Boiled Egg(s)",
            "calories" => 78 * $count,
            "protein_g" => round(6.3 * $count, 1),
            "carbs_g" => round(0.6 * $count, 1),
            "fats_g" => round(5.3 * $count, 1),
            "ai_feedback" => "Gold-standard complete protein source with choline and healthy fats."
        ];
    }

    // 12. Whey Protein / Protein Shake (1 scoop / 30g: 120 kcal, 24g P, 3g C, 1.5g F; per 100g: 400 kcal, 80g P, 10g C, 5g F)
    if (preg_match('/\b(?:whey|whey\s*protein|protein\s*shake|protein\s*powder)\b/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Whey Protein (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(400.0 * $scale),
                "protein_g" => round(80.0 * $scale, 1),
                "carbs_g" => round(10.0 * $scale, 1),
                "fats_g" => round(5.0 * $scale, 1),
                "ai_feedback" => "Fast-digesting whey protein with high leucine content to trigger muscle protein synthesis."
            ];
        }
        $scoops = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$scoops Scoop(s) Whey Protein",
            "calories" => 120 * $scoops,
            "protein_g" => round(24.0 * $scoops, 1),
            "carbs_g" => round(3.0 * $scoops, 1),
            "fats_g" => round(1.5 * $scoops, 1),
            "ai_feedback" => "Fast-digesting whey protein with high leucine content to trigger muscle protein synthesis."
        ];
    }

    // 13. Chapati / Roti (1 piece: 105 kcal, 3.5g P, 20g C, 1.5g F; per 100g: 280 kcal, 9.3g P, 53.3g C, 4g F)
    if (preg_match('/\b(?:chapati|chapatis|roti|rotis|phulka|phulkas)\b/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Chapati / Roti (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(280.0 * $scale),
                "protein_g" => round(9.3 * $scale, 1),
                "carbs_g" => round(53.3 * $scale, 1),
                "fats_g" => round(4.0 * $scale, 1),
                "ai_feedback" => "Whole wheat flatbread providing sustained energy and dietary fiber."
            ];
        }
        $count = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$count Chapati(s)",
            "calories" => 105 * $count,
            "protein_g" => round(3.5 * $count, 1),
            "carbs_g" => round(20.0 * $count, 1),
            "fats_g" => round(1.5 * $count, 1),
            "ai_feedback" => "Whole wheat flatbread providing sustained energy and dietary fiber."
        ];
    }

    // 14. Paneer / Cottage Cheese / Tofu (Per 100g: 265 kcal, 18.0g P, 3.0g C, 20.0g F)
    if (preg_match('/\b(?:paneer|cottage\s*cheese|tofu)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 100.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Paneer (" . (int)$grams . "g)",
            "calories" => (int)round(265.0 * $scale),
            "protein_g" => round(18.0 * $scale, 1),
            "carbs_g" => round(3.0 * $scale, 1),
            "fats_g" => round(20.0 * $scale, 1),
            "ai_feedback" => "Rich in slow-digesting casein dairy protein for sustained amino acid supply."
        ];
    }

    // 15. Dal / Sambhar / Lentils (Per 100g: 105 kcal, 6.0g P, 16.0g C, 2.3g F; 150g bowl: 160 kcal, 9g P, 24g C, 3.5g F)
    if (preg_match('/\b(?:dal|daal|sambar|sambhar|lentils?)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 150.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Dal / Sambhar (" . (int)$grams . "g)",
            "calories" => (int)round(105.0 * $scale),
            "protein_g" => round(6.0 * $scale, 1),
            "carbs_g" => round(16.0 * $scale, 1),
            "fats_g" => round(2.3 * $scale, 1),
            "ai_feedback" => "Vegetarian lentil preparation providing plant protein, dietary fiber, and complex carbohydrates."
        ];
    }

    // 16. Oats / Oatmeal (Per 100g dry: 389 kcal, 13.7g P, 66.3g C, 6.9g F; 40g serving: 160 kcal, 5.5g P, 28g C, 2.8g F)
    if (preg_match('/\b(?:oats|oatmeal)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 40.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Oats (" . (int)$grams . "g)",
            "calories" => (int)round(389.0 * $scale),
            "protein_g" => round(13.7 * $scale, 1),
            "carbs_g" => round(66.3 * $scale, 1),
            "fats_g" => round(6.9 * $scale, 1),
            "ai_feedback" => "High-fiber complex carbohydrate with beta-glucan supporting healthy cholesterol and steady energy."
        ];
    }

    // 17. Bread / Toast (1 slice: 75 kcal, 2.5g P, 14g C, 0.9g F; per 100g: 265 kcal, 9g P, 49g C, 3.2g F)
    if (preg_match('/\b(?:bread|toast)\b/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Bread (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(265.0 * $scale),
                "protein_g" => round(9.0 * $scale, 1),
                "carbs_g" => round(49.0 * $scale, 1),
                "fats_g" => round(3.2 * $scale, 1),
                "ai_feedback" => "Simple carbohydrate source providing quick morning or pre-workout energy."
            ];
        }
        $slices = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$slices Slice(s) Bread",
            "calories" => 75 * $slices,
            "protein_g" => round(2.5 * $slices, 1),
            "carbs_g" => round(14.0 * $slices, 1),
            "fats_g" => round(0.9 * $slices, 1),
            "ai_feedback" => "Simple carbohydrate source providing quick morning or pre-workout energy."
        ];
    }

    // 18. Banana (1 banana ~118g: 105 kcal, 1.3g P, 27g C, 0.3g F; per 100g: 89 kcal, 1.1g P, 22.8g C, 0.3g F)
    if (preg_match('/\bbananas?\b/i', $rawLower)) {
        if ($parsedGrams !== null) {
            $scale = $parsedGrams / 100.0;
            return [
                "matched" => true,
                "name" => "Banana (" . (int)$parsedGrams . "g)",
                "calories" => (int)round(89.0 * $scale),
                "protein_g" => round(1.1 * $scale, 1),
                "carbs_g" => round(22.8 * $scale, 1),
                "fats_g" => round(0.3 * $scale, 1),
                "ai_feedback" => "Potassium-rich fruit providing quick-acting natural fructose and sucrose for workout fuel."
            ];
        }
        $count = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$count Banana(s)",
            "calories" => 105 * $count,
            "protein_g" => round(1.3 * $count, 1),
            "carbs_g" => round(27.0 * $count, 1),
            "fats_g" => round(0.3 * $count, 1),
            "ai_feedback" => "Potassium-rich fruit providing quick-acting natural fructose and sucrose for workout fuel."
        ];
    }

    // 19. Idli & Dosa
    if (preg_match('/\bidlis?\b/i', $rawLower)) {
        $count = max(1, (int)round($qty));
        return [
            "matched" => true,
            "name" => "$count Idli(s)",
            "calories" => 58 * $count,
            "protein_g" => round(2.0 * $count, 1),
            "carbs_g" => round(12.0 * $count, 1),
            "fats_g" => round(0.2 * $count, 1),
            "ai_feedback" => "Fermented steamed rice and lentil cakes providing easily digestible carbohydrates."
        ];
    }

    if (preg_match('/\bdosas?\b/i', $rawLower)) {
        $count = max(1, (int)round($qty));
        $isMasala = (strpos($rawLower, "masala") !== false);
        $baseCal = $isMasala ? 250 : 135;
        $baseProt = $isMasala ? 5.0 : 3.5;
        $baseCarb = $isMasala ? 35.0 : 23.0;
        $baseFat = $isMasala ? 10.0 : 3.5;
        return [
            "matched" => true,
            "name" => "$count Dosa(s)",
            "calories" => $baseCal * $count,
            "protein_g" => round($baseProt * $count, 1),
            "carbs_g" => round($baseCarb * $count, 1),
            "fats_g" => round($baseFat * $count, 1),
            "ai_feedback" => "Crisp fermented crepe delivering clean workout energy."
        ];
    }

    // 20. Chips / Snacks (Per 100g: 536 kcal, 6.0g P, 53.0g C, 35.0g F; 30g pack: 160 kcal, 1.8g P, 16g C, 10.5g F)
    if (preg_match('/\b(?:chips|crisps|wafers|namkeen|fries|french\s*fries|lays|doritos)\b/i', $rawLower)) {
        $grams = $parsedGrams !== null ? $parsedGrams : ($qty * 30.0);
        $scale = $grams / 100.0;
        return [
            "matched" => true,
            "name" => "Chips / Snack (" . (int)$grams . "g)",
            "calories" => (int)round(536.0 * $scale),
            "protein_g" => round(6.0 * $scale, 1),
            "carbs_g" => round(53.0 * $scale, 1),
            "fats_g" => round(35.0 * $scale, 1),
            "ai_feedback" => "High in saturated fats and sodium with minimal protein; keep portion sizes moderate."
        ];
    }

    // 21. Milk (Per 100ml: 65 kcal, 3.3g P, 4.8g C, 3.6g F)
    if (preg_match('/\bmilk\b/i', $rawLower)) {
        $ml = $parsedMl !== null ? $parsedMl : ($parsedGrams !== null ? $parsedGrams : ($qty * 200.0));
        $scale = $ml / 100.0;
        return [
            "matched" => true,
            "name" => "Milk (" . (int)$ml . "ml)",
            "calories" => (int)round(65.0 * $scale),
            "protein_g" => round(3.3 * $scale, 1),
            "carbs_g" => round(4.8 * $scale, 1),
            "fats_g" => round(3.6 * $scale, 1),
            "ai_feedback" => "Wholesome dairy beverage providing natural calcium, vitamin D, and whey/casein proteins."
        ];
    }

    // Unmatched item: preserve token details for downstream scaling or Ollama
    return [
        "matched" => false,
        "name" => trim($rawToken),
        "qty" => $qty,
        "unit" => $unit,
        "food" => $food,
        "parsedGrams" => $parsedGrams,
        "calories" => 0,
        "protein_g" => 0.0,
        "carbs_g" => 0.0,
        "fats_g" => 0.0,
        "ai_feedback" => ""
    ];
}

/**
 * 4. Aggregate Multi-Log Sums:
 * Loops through all parsed tokens, calculates each item's scaled macros,
 * and sums them together into a final combined total.
 */
function processMultiItemNutrition($foodDescription) {
    $tokens = splitFoodItems($foodDescription);
    $totalCalories = 0;
    $totalProtein = 0.0;
    $totalCarbs = 0.0;
    $totalFats = 0.0;
    $feedbacks = [];
    $allMatched = true;
    $unmatchedTokens = [];

    foreach ($tokens as $token) {
        $item = parseAndScaleItem($token);
        if ($item["matched"]) {
            $totalCalories += $item["calories"];
            $totalProtein += $item["protein_g"];
            $totalCarbs += $item["carbs_g"];
            $totalFats += $item["fats_g"];
            if (!empty($item["ai_feedback"])) {
                $feedbacks[] = $item["ai_feedback"];
            }
        } else {
            $allMatched = false;
            $unmatchedTokens[] = $token;
            // Apply proportional gram scaling baseline for unlisted foods with gram weights
            if ($item["parsedGrams"] !== null && $item["parsedGrams"] > 0) {
                $scale = $item["parsedGrams"] / 100.0;
                $cal = (int)round(120.0 * $scale);
                $p = round(5.0 * $scale, 1);
                $c = round(18.0 * $scale, 1);
                $f = round(3.0 * $scale, 1);
                $totalCalories += $cal;
                $totalProtein += $p;
                $totalCarbs += $c;
                $totalFats += $f;
            }
        }
    }

    $feedback = "";
    if (!empty($feedbacks)) {
        $feedback = end($feedbacks);
        if (count($tokens) > 1) {
            $feedback = "Meal logged: " . (int)$totalCalories . " kcal, " . round($totalProtein, 1) . "g protein, " . round($totalCarbs, 1) . "g carbs, " . round($totalFats, 1) . "g fats. " . $feedback;
        }
    }

    return [
        "all_matched" => $allMatched,
        "tokens" => $tokens,
        "unmatched_tokens" => $unmatchedTokens,
        "calories" => (int)round($totalCalories),
        "protein_g" => round($totalProtein, 1),
        "carbs_g" => round($totalCarbs, 1),
        "fats_g" => round($totalFats, 1),
        "ai_feedback" => $feedback
    ];
}

$calories = 0;
$protein = 0.0;
$carbs = 0.0;
$fats = 0.0;
$aiFeedback = "";

if ($saveManually && $manualCalories > 0) {
    // User explicitly entered or edited all values
    $calories = $manualCalories;
    $protein = $manualProtein;
    $carbs = $manualCarbs;
    $fats = $manualFats;
    $aiFeedback = "Manually logged entry. Great job staying accountable with your fitness nutrition!";
} else {
    // Run deterministic tokenizing and gram-based scaling engine
    $parsedMulti = processMultiItemNutrition($foodDesc);

    $calories = $parsedMulti["calories"];
    $protein = $parsedMulti["protein_g"];
    $carbs = $parsedMulti["carbs_g"];
    $fats = $parsedMulti["fats_g"];
    $aiFeedback = $parsedMulti["ai_feedback"];

    if ($parsedMulti["all_matched"]) {
        // All items matched -> exact USDA scaled macros guaranteed
        // Optionally query Ollama for 1 personalized clinical coach sentence (fast 2s timeout)
        if (function_exists("curl_init")) {
            $coachPrompt = "The user logged: \"$foodDesc\" for $mealType.\n" .
                           "Calculated USDA nutritional macros: {$calories} kcal, {$protein}g protein, {$carbs}g carbs, {$fats}g fats.\n" .
                           "Write 1 concise, clinical and encouraging sentence evaluating this meal's nutritional quality and fitness recovery benefits.\n" .
                           "Return ONLY valid JSON: {\"ai_feedback\": \"<1 concise sentence>\"}";

            $ollamaPayload = json_encode([
                "model" => "qwen2.5:1.5b",
                "prompt" => $coachPrompt,
                "stream" => false,
                "format" => "json",
                "options" => [
                    "num_gpu" => 0,
                    "temperature" => 0.2,
                    "num_predict" => 100
                ]
            ]);

            $ch = curl_init("http://127.0.0.1:11434/api/generate");
            if ($ch) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $ollamaPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $resp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $resp) {
                    $parsedResp = json_decode($resp, true);
                    $contentStr = $parsedResp["response"] ?? "";
                    $feedbackData = json_decode($contentStr, true);
                    if (is_array($feedbackData) && !empty($feedbackData["ai_feedback"])) {
                        $aiFeedback = trim($feedbackData["ai_feedback"]);
                    }
                }
            }
        }
    } else {
        // Some items were unlisted -> Query Ollama strictly for the unmatched items
        $unmatched = $parsedMulti["unmatched_tokens"];
        if (!empty($unmatched) && function_exists("curl_init")) {
            $unmatchedText = implode(", ", $unmatched);
            $prompt = "You are a clinical sports nutritionist. Calculate accurate nutritional macros for the unlisted food: \"$unmatchedText\" (Meal: $mealType).\n" .
                      "CRITICAL NUTRITIONAL RULES:\n" .
                      "- Base calculations strictly on real USDA nutritional values for the specified quantity/grams.\n" .
                      "- Supplements like creatine monohydrate, BCAAs, water, and black coffee have 0g protein and 0-5 kcal.\n" .
                      "- Never return generic 350 kcal / 22g protein placeholders.\n" .
                      "- Return ONLY valid JSON: {\"calories\": int, \"protein_g\": float, \"carbs_g\": float, \"fats_g\": float, \"ai_feedback\": \"1 concise sentence\"}.";

            $ollamaPayload = json_encode([
                "model" => "qwen2.5:1.5b",
                "prompt" => $prompt,
                "stream" => false,
                "format" => "json",
                "options" => [
                    "num_gpu" => 0,
                    "temperature" => 0.1,
                    "top_p" => 0.85,
                    "num_predict" => 150
                ]
            ]);

            $ch = curl_init("http://127.0.0.1:11434/api/generate");
            if ($ch) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $ollamaPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $resp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $resp) {
                    $parsedResp = json_decode($resp, true);
                    $contentStr = $parsedResp["response"] ?? "";
                    $macroData = json_decode($contentStr, true);
                    if (is_array($macroData) && isset($macroData["calories"])) {
                        $ollamaCal = (int)$macroData["calories"];
                        $ollamaP = (float)($macroData["protein_g"] ?? 0.0);
                        $ollamaC = (float)($macroData["carbs_g"] ?? 0.0);
                        $ollamaF = (float)($macroData["fats_g"] ?? 0.0);

                        // If single unmatched item had baseline added, replace baseline with Ollama values
                        if (count($unmatched) === count($parsedMulti["tokens"])) {
                            $calories = $ollamaCal;
                            $protein = $ollamaP;
                            $carbs = $ollamaC;
                            $fats = $ollamaF;
                        } else {
                            $calories += $ollamaCal;
                            $protein += $ollamaP;
                            $carbs += $ollamaC;
                            $fats += $ollamaF;
                        }

                        if (!empty($macroData["ai_feedback"])) {
                            $aiFeedback = trim($macroData["ai_feedback"]);
                        }
                    }
                }
            }
        }

        // Post-validation safeguards
        $lowerDesc = strtolower($foodDesc);
        $hasProteinSource = preg_match('/\b(?:chicken|mutton|meat|fish|paneer|egg|whey|protein|dal|curry|gravy)\b/i', $lowerDesc);
        if (!$hasProteinSource) {
            if (preg_match('/\bcreatine\b/i', $lowerDesc) && !preg_match('/\b(?:rice|roti|bread|banana)\b/i', $lowerDesc)) {
                $protein = 0.0;
                $calories = min($calories, 10);
            } elseif (preg_match('/\b(?:black\s*coffee|green\s*tea|water)\b/i', $lowerDesc)) {
                $protein = min($protein, 0.5);
                $calories = min($calories, 15);
            }
        }

        if ($calories <= 0) {
            $calories = 190;
            $protein = 4.5;
            $carbs = 28.0;
            $fats = 5.0;
            $aiFeedback = "Meal logged: {$calories} kcal, {$protein}g protein, {$carbs}g carbs, {$fats}g fats.";
        }
    }

    if (empty($aiFeedback)) {
        $aiFeedback = "Meal logged: {$calories} kcal, {$protein}g protein, {$carbs}g carbs, {$fats}g fats. Great nutrition tracking for muscle recovery!";
    }

    // Apply manual overrides if user provided any partial numbers
    if ($manualCalories > 0) $calories = $manualCalories;
    if ($manualProtein > 0.0) $protein = $manualProtein;
    if ($manualCarbs > 0.0) $carbs = $manualCarbs;
    if ($manualFats > 0.0) $fats = $manualFats;
}

// Persist meal log to database
$stmt = $conn->prepare("
    INSERT INTO gym_nutrition_logs (user_id, meal_type, food_description, calories, protein_g, carbs_g, fats_g, ai_feedback, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    respondJson(500, [
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
}

$stmt->bind_param("issiddds", $userId, $mealType, $foodDesc, $calories, $protein, $carbs, $fats, $aiFeedback);
$stmt->execute();
$mealId = $stmt->insert_id;
$stmt->close();

respondJson(200, [
    "success" => true,
    "message" => "Meal analyzed and logged successfully",
    "data" => [
        "id" => $mealId,
        "user_id" => $userId,
        "meal_type" => $mealType,
        "food_description" => $foodDesc,
        "calories" => $calories,
        "protein_g" => $protein,
        "carbs_g" => $carbs,
        "fats_g" => $fats,
        "ai_feedback" => $aiFeedback,
        "created_at" => date("Y-m-d H:i:s")
    ]
]);
