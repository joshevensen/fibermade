<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('home/HomePage');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
