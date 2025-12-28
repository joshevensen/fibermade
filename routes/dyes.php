<?php

use App\Http\Controllers\DyeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('dyes', DyeController::class)->except(['create']);
});
