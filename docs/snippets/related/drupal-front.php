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
            ],
            'relations' => [
                'topics' => ['to' => 'topic', 'via' => 'topic', 'many' => true],
                'tracks' => ['to' => 'track', 'via' => 'tracks', 'many' => true],
            ],
            'includes' => ['listing' => ['topics', 'tracks']],
            'filters' => [
                'topic' => ['type' => 'equals', 'relation' => 'topics', 'native_path' => 'topic.id'],
                'track' => ['type' => 'equals', 'relation' => 'tracks', 'native_path' => 'tracks.id'],
            ],
        ],

        'topic' => [
            'role' => 'facet',
            'entity_type' => 'taxonomy_term',
            'bundle' => 'topic',
            'conditions' => [['attribute' => 'talk_count', 'operator' => '>', 'value' => 0]],
            'title_attribute' => 'name',
            'fields' => [
                'name' => ['source' => 'name', 'required' => true],
                'talk_count' => ['source' => 'talk_count', 'type' => 'integer'],
            ],
        ],

        'track' => [
            'role' => 'facet',
            'entity_type' => 'taxonomy_term',
            'bundle' => 'tracks',
            'conditions' => [['attribute' => 'talk_count', 'operator' => '>', 'value' => 0]],
            'title_attribute' => 'name',
            'fields' => [
                'name' => ['source' => 'name', 'required' => true],
                'talk_count' => ['source' => 'talk_count', 'type' => 'integer'],
            ],
        ],
    ],
];
