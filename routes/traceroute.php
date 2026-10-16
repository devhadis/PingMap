<?php

use App\Http\Controllers\Api\GeoIpSaltosController;

use App\Http\Controllers\Api\TracerouteConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/traceroute')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            TracerouteConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');

        Route::get('/consultas', [
            TracerouteConsultaController::class,
            'index',
        ])->middleware('throttle:60,1');

        Route::get('/consultas/{id}', [
            TracerouteConsultaController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');

        Route::get('/consultas/{id}/geo', [
            GeoIpSaltosController::class,
            'traceroute',
        ])->whereNumber('id')
          ->middleware('throttle:10,1');
});
