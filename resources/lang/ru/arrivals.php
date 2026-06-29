<?php

return [
    'created' => 'Заезд успешно создан.',
    'results_received' => 'Результаты заезда приняты.',
    'final_results_saved' => 'Финальные результаты заезда сохранены.',
    'final_results_fetched' => 'Финальные результаты заезда получены.',
    'race_final_results_fetched' => 'Финальные результаты гонки получены.',
    'final_results_not_found' => 'Финальные результаты заезда не найдены.',
    'server_arrival_id_mismatch' => 'server_arrival_id не совпадает с идентификатором заезда в URL.',
    'race_id_mismatch' => 'server_race_id не совпадает с заездом на сервере.',
    'not_found' => 'Заезд не найден.',
    'stream_opened' => 'Трансляция открыта.',
    'stream_closed' => 'Трансляция закрыта.',
    'stream_already_opened' => 'Трансляция уже открыта. Сначала закройте её.',
    'stream_already_closed' => 'Трансляция уже закрыта. Повторно открыть нельзя.',
    'stream_not_opened' => 'Трансляция ещё не была открыта.',
    'stream_close_before_final_results' => 'Нельзя закрыть трансляцию до сохранения финальных результатов.',
    'stream_bearer_missing' => 'Не передан токен Moto. Укажите Authorization: Bearer <токен> (для hrono — X-Api-Secret).',
    'stream_open_failed' => 'Не удалось открыть трансляцию в Moto.',
    'stream_close_failed' => 'Не удалось закрыть трансляцию в Moto.',
];
