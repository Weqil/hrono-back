<?php

/*
| При supports_credentials=true нельзя использовать allowed_origins => ['*'].
| По умолчанию отражаем любой Origin через allowed_origins_patterns (Capacitor: https://localhost и т.д.).
| Для жёсткого whitelist задайте CORS_ALLOWED_ORIGINS в .env (через запятую).
*/
$allowedOrigins = array_values(array_filter(array_map(
    trim(...),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
)));

return [

    'paths' => ['arrivals', 'arrivals/*', 'arrival-types', 'races', 'races/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    // Всегда отражаем Origin запроса (https://localhost, capacitor://localhost, …).
    'allowed_origins_patterns' => ['#^.+$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
