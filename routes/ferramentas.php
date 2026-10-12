<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::view('/ferramentas/dns', 'ferramentas.dns')
        ->name('ferramentas.dns');

    Route::view('/ferramentas/tcp', 'ferramentas.tcp')
        ->name('ferramentas.tcp');
});
