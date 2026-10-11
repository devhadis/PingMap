<?php

use App\Http\Controllers\Api\DiagnosticoController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->middleware('auth')
    ->group(function () {
        Route::post('/diagnosticos', [
            DiagnosticoController::class,
            'store',
        ])->middleware('throttle:10,1');

        Route::get('/diagnosticos/{id}', [
            DiagnosticoController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');
    });
