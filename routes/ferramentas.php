<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::view('/ferramentas/dns', 'ferramentas.dns')
        ->name('ferramentas.dns');

    Route::view('/ferramentas/tcp', 'ferramentas.tcp')
        ->name('ferramentas.tcp');
    Route::view('/ferramentas/traceroute', 'ferramentas.traceroute')
        ->name('ferramentas.traceroute');
    Route::view('/ferramentas/mtr', 'ferramentas.mtr')
        ->name('ferramentas.mtr');

    Route::view('/ferramentas/email', 'ferramentas.email')
        ->name('ferramentas.email');

    Route::view('/ferramentas/dnssec', 'ferramentas.dnssec')
        ->name('ferramentas.dnssec');
});
