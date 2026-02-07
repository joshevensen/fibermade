<?php

use App\Http\Controllers\InviteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home');

Route::get('invites/accept/{token}', [InviteController::class, 'accept'])->name('invites.accept');
Route::post('invites/accept/{token}', [InviteController::class, 'acceptStore'])->name('invites.accept.store');

require __DIR__.'/creator.php';
require __DIR__.'/store.php';
