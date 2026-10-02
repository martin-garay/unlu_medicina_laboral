<?php

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => [
            'driver' => 'database', 'connection' => null, 'table' => 'jobs',
            'queue' => 'certificates', 'retry_after' => 120, 'after_commit' => false,
        ],
    ],
    'failed' => ['driver' => 'database-uuids', 'database' => env('DB_CONNECTION', 'pgsql'), 'table' => 'failed_jobs'],
];
