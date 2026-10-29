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
            'fields' => [
                'title' => ['source' => 'title', 'required' => true],
            ],
            'term_fields' => ['title' => 5],
            'filters' => [
                // The key is the filter; each key under `fields` is one checkbox and one URL parameter.
                'extras' => [
                    'type' => 'boolean',
                    'label' => 'Extras',
                    'match' => 'any',
                    'fields' => [
                        // A real boolean field: the box selects talks where it equals 1.
                        'recording' => ['native' => 'has_recording'],
                        // A relationship Drupal cannot reduce to a boolean: the box selects talks where it has a value.
                        'slides' => ['native' => 'slides_resources', 'present' => true],
                    ],
                    'labels' => ['recording' => 'Has a recording', 'slides' => 'Has slides'],
                ],
                // A type can declare any number of boolean filters, each with its own fields and `match`.
                'audience' => [
                    'type' => 'boolean',
                    'label' => 'Audience',
                    'match' => 'all',
                    'fields' => [
                        'beginner' => ['native' => 'is_beginner'],
                        'free' => ['native' => 'is_free'],
                    ],
                    'labels' => ['beginner' => 'For beginners', 'free' => 'Free to attend'],
                ],
            ],
        ],
    ],

    'search' => [
        'enabled' => true,
        'tabs' => ['talk'],
    ],
];
