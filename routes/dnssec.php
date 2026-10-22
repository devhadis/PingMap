<?php

use App\Http\Controllers\Api\DnssecController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/dnssec')
    ->middleware('auth')
    ->group(function () {
        Route::post('/analises', [
            DnssecController::class,
            'store',
        ])->middleware('throttle:10,1');
    });
