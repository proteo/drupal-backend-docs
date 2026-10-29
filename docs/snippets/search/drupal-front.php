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
            // Required by the default match strategy: where a typed term is looked for, and how much a hit counts.
            'term_fields' => ['title' => 5, 'body.value' => 1],
            'sorts' => ['newest' => '-created', 'title' => 'title'],
        ],
    ],

    'search' => [
        'enabled' => true,
        'tabs' => ['talk'],
        'per_page' => 12,
        // A one-letter term is refused with "Enter at least 3 letters or numbers."
        'term' => ['min' => 3],
    ],
];
