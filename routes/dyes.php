<?php

use App\Http\Controllers\DyeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('dyes', DyeController::class)->except(['create']);
    Route::patch('dyes/{dye}/toggle-field', [DyeController::class, 'toggleField'])->name('dyes.toggle-field');
    Route::patch('dyes/{dye}/notes', [DyeController::class, 'updateNotes'])->name('dyes.update-notes');
});
