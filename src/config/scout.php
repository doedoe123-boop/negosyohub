<?php

use App\Models\Development;
use App\Models\Property;
use App\Models\Store;

return [
    'driver' => env('SCOUT_DRIVER', 'null'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', false),
    'after_commit' => false,
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'soft_delete' => false,
    'identify' => env('SCOUT_IDENTIFY', false),
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Store::class => [
                'searchableAttributes' => ['name', 'description', 'sector', 'city'],
                'filterableAttributes' => ['sector', 'city'],
            ],
            Property::class => [
                'searchableAttributes' => ['title', 'address_line', 'city', 'province'],
                'filterableAttributes' => ['listing_type', 'property_type', 'city'],
            ],
            Development::class => [
                'searchableAttributes' => ['name', 'developer_name', 'city', 'province'],
                'filterableAttributes' => ['development_type', 'city'],
            ],
        ],
    ],
];
