<?php

use App\Http\Controllers\Api\HttpTlsConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/http-tls')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            HttpTlsConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');
    });
