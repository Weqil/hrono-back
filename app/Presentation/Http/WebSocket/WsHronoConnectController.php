<?php

namespace App\Presentation\Http\WebSocket;

use App\Application\WebSocket\Actions\ConnectWsHronoAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class WsHronoConnectController extends Controller
{
    public function __invoke(ConnectWsHronoAction $action): JsonResponse
    {
        return response()->json($action->execute());
    }
}
