<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home');

require __DIR__.'/creator.php';
require __DIR__.'/store.php';
