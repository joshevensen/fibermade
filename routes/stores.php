<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
});
