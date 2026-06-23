<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WAHA API Connection
    |--------------------------------------------------------------------------
    */
    'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
    'api_key'  => env('WAHA_API_KEY', ''),
    'session'  => env('WAHA_SESSION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Anti-Banned: Typing Simulation Delay (milliseconds)
    |--------------------------------------------------------------------------
    | Delay before sending a reply to simulate human typing.
    */
    'typing_delay_min' => env('WAHA_TYPING_DELAY_MIN', 1000),
    'typing_delay_max' => env('WAHA_TYPING_DELAY_MAX', 3000),

    /*
    |--------------------------------------------------------------------------
    | Anti-Banned: Bulk Send Settings
    |--------------------------------------------------------------------------
    */
    // Delay between individual messages (milliseconds)
    'bulk_delay_min' => env('WAHA_BULK_DELAY_MIN', 30000),   // 30 seconds
    'bulk_delay_max' => env('WAHA_BULK_DELAY_MAX', 60000),   // 60 seconds

    // How many messages before pausing
    'bulk_batch_size' => env('WAHA_BULK_BATCH_SIZE', 4),

    // Pause between batches (seconds)
    'bulk_batch_pause_min' => env('WAHA_BULK_BATCH_PAUSE_MIN', 60),
    'bulk_batch_pause_max' => env('WAHA_BULK_BATCH_PAUSE_MAX', 120),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('WAHA_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'driver' => env('WAHA_DB_DRIVER', 'mysql'), // mysql or pgsql
        'host' => env('WAHA_DB_HOST', '127.0.0.1'),
        'port' => env('WAHA_DB_PORT', '3306'),
        'database' => env('WAHA_DB_DATABASE', 'waha'),
        'username' => env('WAHA_DB_USERNAME', 'root'),
        'password' => env('WAHA_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => env('WAHA_DB_PREFIX', ''),
    ],
];
