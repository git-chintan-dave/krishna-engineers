<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory low-stock thresholds
    |--------------------------------------------------------------------------
    |
    | Keys are the database IDs of tools and section sizes.
    | Set a threshold greater than zero to enable its low-stock alert.
    |
    */

    'inventory' => [
        'tool_thresholds' => [
            // 1 => 5,
            // 2 => 10,
        ],

        'section_size_thresholds' => [
            // 1 => 100,
            // 2 => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard limits
    |--------------------------------------------------------------------------
    */

    'recent_limit' => 8,
    'top_operator_limit' => 10,
];
