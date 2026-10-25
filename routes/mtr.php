<?php

use App\Http\Controllers\Api\GeoIpSaltosController;

use App\Http\Controllers\Api\MtrConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/mtr')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            MtrConsultaController::class,
            'store',
        ])->middleware('throttle:10,1,mtr-criacao');

        Route::get('/consultas', [
            MtrConsultaController::class,
            'index',
        ])->middleware('throttle:60,1');

        Route::get('/consultas/{id}', [
            MtrConsultaController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');

        Route::get('/consultas/{id}/geo', [
            GeoIpSaltosController::class,
            'mtr',
        ])->whereNumber('id')
          ->middleware('throttle:10,1,mtr-geo');
});
