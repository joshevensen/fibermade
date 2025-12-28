<?php

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bases', BaseController::class)->except(['create']);
});
