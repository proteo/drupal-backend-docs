<?php

return [
    'schema_version' => 3,

    'sources' => [
        'default' => ['base_url' => env('DRUPAL_BASE_URL', 'https://drupal.test')],
    ],

    'content_types' => [
        'talk' => [
            'role' => 'primary',
            'entity_type' => 'node',
            'bundle' => 'talk',
            'listing_path' => '/talks',
            'per_page' => 12,
            'fields' => [
                'title' => ['source' => 'title', 'required' => true],
                'summary' => ['source' => 'body', 'type' => 'html'],
            ],
        ],
    ],
];
