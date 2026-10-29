<?php

return [
    'schema_version' => 3,

    // Keep addresses and secrets in .env so each environment points at its own Drupal.
    'sources' => [
        'default' => [
            'base_url' => env('DRUPAL_BASE_URL'),
            'jsonapi_path' => env('DRUPAL_JSONAPI_PATH', '/jsonapi'),
            'files_path' => env('DRUPAL_FILES_PATH', ''),
            'webhook' => ['secret' => env('WEBHOOK_SECRET')],
        ],
    ],

    'content_types' => [
        // Declare your content types here; see the Quick start.
    ],
];
