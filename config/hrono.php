<?php

return [

    /*
    | Общий секрет для запросов с фронта (заголовок X-Api-Secret или Bearer).
    | Можно задать через API_SECRET; если пусто — используется WS_SECRET_KEY (обратная совместимость).
    */
    'api_secret' => env('API_SECRET') ?: env('WS_SECRET_KEY'),

    /*
    | Базовый URL API Moto (с /api/ в конце), например https://moto.example.com/api/
    | Результаты: POST {moto_api_url}hrono/races/{moto_race_id}/results
    */
    'moto_api_url' => env('MOTO_API_URL', ''),

];
