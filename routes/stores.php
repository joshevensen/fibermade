<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('stores', StoreController::class)->except(['create']);
});
