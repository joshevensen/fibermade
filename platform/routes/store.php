<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('store')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [StoreController::class, 'home'])
        ->name('store.home');

    Route::get('settings', [UserController::class, 'edit'])
        ->name('store.settings');

    // Import routes
    Route::post('settings/import/products', [ImportController::class, 'importProducts'])->name('store.import.products');
});
