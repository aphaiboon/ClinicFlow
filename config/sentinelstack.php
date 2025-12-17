<?php

return [
    'enabled' => env('SENTINELSTACK_ENABLED', false),

    'api_url' => env('SENTINELSTACK_API_URL'),

    'api_key' => env('SENTINELSTACK_API_KEY'),

    'service_id' => env('SENTINELSTACK_SERVICE_ID', 'clinicflow'),

    'service_version' => env('SENTINELSTACK_SERVICE_VERSION', '1.0.0'),

    'instance_id' => env('SENTINELSTACK_INSTANCE_ID', env('APP_NAME', 'unknown')),

    'region' => env('SENTINELSTACK_REGION', 'us-west-2'),

    'environment' => env('SENTINELSTACK_ENVIRONMENT', env('APP_ENV', 'development')),

    'tenant_id' => env('SENTINELSTACK_TENANT_ID'),

    'queue' => [
        'connection' => env('SENTINELSTACK_QUEUE_CONNECTION', 'default'),
        'queue' => env('SENTINELSTACK_QUEUE_NAME', 'sentinelstack'),
    ],

    'retry' => [
        'max_attempts' => env('SENTINELSTACK_RETRY_MAX_ATTEMPTS', 5),
        'backoff_intervals' => [
            1,
            2,
            4,
            8,
            16,
        ],
    ],
];

