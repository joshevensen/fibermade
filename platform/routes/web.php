<?php

use App\Http\Controllers\InviteController;
use App\Http\Controllers\RegisterCheckoutController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home')->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
]);

Route::get('invites/accept/{token}', [InviteController::class, 'accept'])->name('invites.accept');
Route::post('invites/accept/{token}', [InviteController::class, 'acceptStore'])->name('invites.accept.store');

Route::post('register/checkout', RegisterCheckoutController::class)->name('register.checkout');
Route::get('register/success', fn () => Inertia::render('auth/RegisterSuccessPage'))->name('register.success');
Route::get('register/cancel', fn () => redirect()->route('register'))->name('register.cancel');

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
Route::post('webhooks/shopify/inventory', ShopifyWebhookController::class)->name('webhooks.shopify.inventory');

require __DIR__.'/creator.php';
require __DIR__.'/store.php';
