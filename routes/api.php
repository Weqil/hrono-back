<?php

use App\Presentation\Http\Arrival\ArrivalController;
use App\Presentation\Http\Arrival\CreateArrivalController;
use App\Presentation\Http\Arrival\CloseArrivalStreamController;
use App\Presentation\Http\Arrival\OpenArrivalStreamController;
use App\Presentation\Http\Arrival\GetArrivalFinalResultsController;
use App\Presentation\Http\Arrival\GetRaceFinalResultsController;
use App\Presentation\Http\Arrival\SaveArrivalFinalResultsController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.secret')->group(function (): void {
    Route::post('arrivals', CreateArrivalController::class)
        ->name('arrivals.create');

    Route::post('arrivals/{id}/stream/open', OpenArrivalStreamController::class)
        ->name('arrivals.stream.open');

    Route::post('arrivals/{id}/stream/close', CloseArrivalStreamController::class)
        ->name('arrivals.stream.close');

    Route::post('arrivals/{id}/results', ArrivalController::class)
        ->name('arrivals.results.store');

    Route::get('races/{id}/final-results', GetRaceFinalResultsController::class)
        ->name('races.final-results.show');

    Route::get('arrivals/{id}/final-results', GetArrivalFinalResultsController::class)
        ->name('arrivals.final-results.show');

    Route::post('arrivals/{id}/final-results', SaveArrivalFinalResultsController::class)
        ->name('arrivals.final-results.store');
});
