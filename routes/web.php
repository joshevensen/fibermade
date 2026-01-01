<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/colorways.php';
require __DIR__.'/bases.php';
require __DIR__.'/customers.php';
require __DIR__.'/dyes.php';
require __DIR__.'/collections.php';
require __DIR__.'/inventory.php';
require __DIR__.'/stores.php';
require __DIR__.'/orders.php';
require __DIR__.'/shows.php';
