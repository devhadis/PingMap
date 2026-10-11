<?php

use App\Http\Controllers\Api\DnsConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/dns')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            DnsConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');

        Route::get('/consultas', [
            DnsConsultaController::class,
            'index',
        ])->middleware('throttle:60,1');

        Route::get('/consultas/{id}', [
            DnsConsultaController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');
    });
