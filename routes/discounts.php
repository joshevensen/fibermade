<?php

use App\Http\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('discounts', DiscountController::class)->except(['create']);
});
