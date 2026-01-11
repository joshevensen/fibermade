<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use Illuminate\Support\Facades\Route;

// TODO: Re-enable order write operations (store, update, destroy) when ready to work on orders.
// These routes are currently disabled via OrderPolicy.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('orders', OrderController::class)->except(['create']);
    Route::resource('order-items', OrderItemController::class);
});
