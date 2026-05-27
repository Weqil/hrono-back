<?php

namespace App\Application\WebSocket\Actions;

final class ConnectWsHronoAction
{
    /**
     * Заглушка: пока просто отдаем параметры для подключения.
     */
    public function execute(): array
    {
        return [
            'message' => __('ws_hrono.connect'),
        ];
    }
}
