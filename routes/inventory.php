<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::patch('inventory/quantity', [InventoryController::class, 'updateQuantity'])->name('inventory.updateQuantity');
});
