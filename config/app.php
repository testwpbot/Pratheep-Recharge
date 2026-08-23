<?php

return [

    'name' => env('APP_NAME', 'Happy Pratheep Recharge'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'Asia/Colombo'),

    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    // Optional. When set, GET /cron.php?key=... must match. Leave empty for DirectAdmin wget.
    'cron_key' => env('CRON_KEY', ''),

    'maintenance' => [
        'driver' => 'file',
    ],
];
