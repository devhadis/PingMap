<?php

use App\Http\Controllers\Api\EmailDnsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/email')
    ->middleware('auth')
    ->group(function () {
        Route::post('/analises', [
            EmailDnsController::class,
            'store',
        ])->middleware('throttle:10,1');
    });
