<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('store')->middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('store.dashboard');

    Route::get('vendors', [StoreController::class, 'index'])
        ->name('store.vendors');

    Route::get('settings', [UserController::class, 'edit'])
        ->name('store.settings');
});
