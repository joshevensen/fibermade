<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('settings', [UserController::class, 'edit'])->name('user.edit');
    Route::patch('settings/profile', [UserController::class, 'update'])->name('user.update');
    Route::put('settings/password', [UserController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
    Route::delete('settings/profile', [UserController::class, 'destroy'])->name('user.destroy');
    Route::delete('settings/account', [UserController::class, 'destroyAccount'])->name('account.destroy');
});
