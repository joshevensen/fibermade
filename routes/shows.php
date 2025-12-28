<?php

use App\Http\Controllers\ShowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('shows', ShowController::class)->except(['create']);
});
