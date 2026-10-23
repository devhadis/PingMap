<?php

use App\Http\Controllers\Api\RdapConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/rdap')
    ->middleware('auth')
    ->group(function () {
        Route::post('/consultas', [
            RdapConsultaController::class,
            'store',
        ])->middleware('throttle:10,1');
    });
