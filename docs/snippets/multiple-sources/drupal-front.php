<?php

return [
    'sources' => [
        'default' => [
            'base_url' => env('DRUPAL_BASE_URL'),
            'webhook' => ['secret' => env('WEBHOOK_SECRET')],
            // A change on the main site also invalidates the reviews, which show its talks.
            'flush' => ['also' => ['sources' => ['reviews']]],
        ],
        'reviews' => [
            'base_url' => env('DRUPAL_REVIEWS_URL'),
            'jsonapi_path' => '/api',
            'frontend_url' => env('DRUPAL_REVIEWS_FRONTEND_URL'),
            'webhook' => ['secret' => env('REVIEWS_WEBHOOK_SECRET')],
            // This site is slower: give it more time than the shared defaults.
            'timeout' => 20,
            'retries' => 1,
        ],
    ],
];
