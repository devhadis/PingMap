<?php

use App\Http\Controllers\Auth\ContaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [
        ContaController::class, 'cadastro',
    ])->name('cadastro');

    Route::post('/cadastro', [
        ContaController::class, 'registrar',
    ])->middleware('throttle:5,1');

    Route::get('/login', [
        ContaController::class, 'login',
    ])->name('login');

    Route::post('/login', [
        ContaController::class, 'autenticar',
    ])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [
        \App\Http\Controllers\DashboardController::class,
        'index',
    ])->name('dashboard');

    Route::post('/logout', [
        ContaController::class, 'sair',
    ])->name('logout');
});
