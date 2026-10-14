<?php

use App\Http\Controllers\Api\MtrConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/mtr')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            MtrConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');

        Route::get('/consultas', [
            MtrConsultaController::class,
            'index',
        ])->middleware('throttle:60,1');

        Route::get('/consultas/{id}', [
            MtrConsultaController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');
    });
