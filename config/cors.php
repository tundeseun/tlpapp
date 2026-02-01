<?php

return [

    // 'paths' => ['api/*', 'sanctum/csrf-cookie'],
      'paths' => [
        'api/*',
        'public/api/*',           // ✅ IMPORTANT for your /public/api URLs
        'sanctum/csrf-cookie',
        'public/sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // React dev (optional because patterns below already cover it)
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',

        // Production
        'https://app.thelightersplace.co.uk',
        'https://app.thelightersplace.co.uk/public',
    ],

    // ✅ Covers Flutter Web random ports like localhost:53837
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    // Bearer token auth (no cookies)
    'supports_credentials' => false,
];
