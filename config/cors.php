<?php

$allowedOrigins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200');

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | При supports_credentials=true нельзя использовать allowed_origins => ['*'].
    | Укажите точные URL фронта в CORS_ALLOWED_ORIGINS (.env).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', $allowedOrigins),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Origin',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'X-Api-Secret',
        'Cache-Control',
        'Pragma',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
