<?php

use App\Http\Controllers\ColorwayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('colorways/{colorway}/collections', [ColorwayController::class, 'updateCollections'])->name('colorways.collections.update');
    Route::resource('colorways', ColorwayController::class)->except(['create']);
});
