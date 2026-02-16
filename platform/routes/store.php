<?php

use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('store')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [StoreController::class, 'home'])
        ->name('store.home');

    Route::get('settings', [UserController::class, 'edit'])
        ->name('store.settings');

    Route::get('{creator}/order', [StoreController::class, 'order'])
        ->name('store.creator.order.step1');

    Route::get('{creator}/order/review', [StoreController::class, 'review'])
        ->name('store.creator.order.review');
    Route::post('{creator}/order/save', [StoreController::class, 'saveOrder'])
        ->name('store.creator.order.save');
    Route::post('{creator}/order/submit', [StoreController::class, 'submitOrder'])
        ->name('store.creator.order.submit');

    Route::get('orders/{order}', [StoreController::class, 'showOrder'])
        ->name('store.orders.show');

    Route::get('{creator}/orders', [StoreController::class, 'orders'])
        ->name('store.creator.orders');

});
