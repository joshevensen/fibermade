<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('orders', OrderController::class)->except(['create']);
    Route::resource('order-items', OrderItemController::class);
});
