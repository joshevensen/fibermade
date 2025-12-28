<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customers', CustomerController::class)->except(['create']);
    Route::patch('customers/{customer}/notes', [CustomerController::class, 'updateNotes'])->name('customers.update-notes');
});
