<?php

use App\Http\Controllers\Api\TcpConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/tcp')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            TcpConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');

        Route::get('/consultas', [
            TcpConsultaController::class,
            'index',
        ])->middleware('throttle:60,1');

        Route::get('/consultas/{id}', [
            TcpConsultaController::class,
            'show',
        ])->whereNumber('id')
          ->middleware('throttle:60,1');
    });
