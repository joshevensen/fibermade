<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// TODO: In Stage 2, re-enable customer write operations (store, update, destroy, update-notes).
// These routes are currently disabled via CustomerPolicy in Stage 1.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customers', CustomerController::class)->except(['create']);
    Route::patch('customers/{customer}/notes', [CustomerController::class, 'updateNotes'])->name('customers.update-notes');
});
