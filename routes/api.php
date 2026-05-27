<?php

use App\Presentation\Http\WebSocket\WsHronoConnectController;
use Illuminate\Support\Facades\Route;

Route::get('ws-hrono/connect', WsHronoConnectController::class)
    ->name('ws-hrono.connect');
