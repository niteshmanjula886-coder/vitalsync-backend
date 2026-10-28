<?php

require_once __DIR__ . "/../config/auth_middleware.php";

$splits = [
    [
        "split" => "PUSH",
        "displayName" => "Push",
        "description" => "Chest, Shoulders & Triceps"
    ],
    [
        "split" => "PULL",
        "displayName" => "Pull",
        "description" => "Back, Biceps & Rear Delts"
    ],
    [
        "split" => "LEGS",
        "displayName" => "Legs",
        "description" => "Quads, Hamstrings, Glutes & Calves"
    ],
    [
        "split" => "UPPER_BODY",
        "displayName" => "Upper Body",
        "description" => "Chest, Back, Shoulders & Arms"
    ],
    [
        "split" => "LOWER_BODY",
        "displayName" => "Lower Body",
        "description" => "Quads, Hamstrings, Glutes & Calves"
    ],
    [
        "split" => "FULL_BODY",
        "displayName" => "Full Body",
        "description" => "All major muscle groups"
    ],
    [
        "split" => "CUSTOM",
        "displayName" => "Custom Workout",
        "description" => "Custom exercise routine"
    ]
];

respondJson(200, [
    "success" => true,
    "splits" => $splits
]);

