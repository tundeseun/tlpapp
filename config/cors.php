<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    | Because your API is accessed via /public/api/*, we must include it here.
    */
    'paths' => [
        'api/*',
        'public/api/*',
        'sanctum/csrf-cookie',
        'public/sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    | NOTE: Origins cannot include /public.
    */
    'allowed_origins' => [
        'https://app.thelightersplace.co.uk',
        'https://admin.thelightersplace.co.uk',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow any localhost port for dev (Flutter web runs on random ports)
    */
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    // Using Bearer token auth (NOT cookies), so false is correct.
    'supports_credentials' => false,
];
