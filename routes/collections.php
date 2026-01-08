<?php

use App\Http\Controllers\CollectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('collections/{collection}/colorways', [CollectionController::class, 'updateColorways'])->name('collections.colorways.update');
    Route::resource('collections', CollectionController::class)->except(['create']);
});
