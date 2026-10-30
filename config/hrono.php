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

    /*
    | Idle-grace автозакрытия трансляции (минуты).
    | Старт: opened_at + duration(arrival.time) + grace.
    | Каждый live-results сдвигает deadline на max(конец заезда, now) + grace.
    | Если за grace после последней активности ничего не пришло — закрываем + сохраняем итоги.
    */
    'stream_auto_close_grace_minutes' => (int) env('STREAM_AUTO_CLOSE_GRACE_MINUTES', 10),

    /*
    | Жёсткий потолок автозавершения (часы от opened_at).
    | Live-активность не может продлить стрим дальше этого лимита.
    */
    'stream_auto_close_max_hours' => (int) env('STREAM_AUTO_CLOSE_MAX_HOURS', 24),

    /*
    | Fallback Bearer for Motо stream/close from worker/scheduler when the
    | encrypted moto_stream_bearer cannot be decrypted (APP_KEY mismatch).
    */
    'moto_service_bearer' => env('MOTO_SERVICE_BEARER', ''),

];

