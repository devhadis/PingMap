<?php

use App\Http\Controllers\Api\BgpConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/bgp')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            BgpConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');
    });
