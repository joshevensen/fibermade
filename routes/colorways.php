<?php

use App\Http\Controllers\ColorwayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('colorways', ColorwayController::class)->except(['create']);
});
