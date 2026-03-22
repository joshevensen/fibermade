<?php

use App\Http\Controllers\InviteController;
use App\Http\Controllers\RegisterCheckoutController;
use App\Http\Controllers\RegisterSuccessController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('website/HomePage');
})->name('home')->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
]);

Route::get('terms', function () {
    return Inertia::render('website/TermsPage');
})->name('terms')->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
]);

Route::get('privacy', function () {
    return Inertia::render('website/PrivacyPage');
})->name('privacy')->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
]);

Route::get('invites/accept/{token}', [InviteController::class, 'accept'])->name('invites.accept');
Route::post('invites/accept/{token}', [InviteController::class, 'acceptStore'])->middleware('throttle:10,1')->name('invites.accept.store');

Route::post('register/checkout', RegisterCheckoutController::class)->middleware('throttle:10,1')->name('register.checkout');
Route::get('register/success', RegisterSuccessController::class)->name('register.success');
Route::get('register/cancel', fn () => redirect()->route('register'))->name('register.cancel');

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::post('webhooks/shopify/inventory', [ShopifyWebhookController::class, 'inventory'])->name('webhooks.shopify.inventory');
Route::post('webhooks/shopify/products/create', [ShopifyWebhookController::class, 'productCreate'])->name('webhooks.shopify.products.create');
Route::post('webhooks/shopify/products/update', [ShopifyWebhookController::class, 'productUpdate'])->name('webhooks.shopify.products.update');
Route::post('webhooks/shopify/products/delete', [ShopifyWebhookController::class, 'productDelete'])->name('webhooks.shopify.products.delete');
Route::post('webhooks/shopify/collections/create', [ShopifyWebhookController::class, 'collectionCreate'])->name('webhooks.shopify.collections.create');
Route::post('webhooks/shopify/collections/update', [ShopifyWebhookController::class, 'collectionUpdate'])->name('webhooks.shopify.collections.update');
Route::post('webhooks/shopify/collections/delete', [ShopifyWebhookController::class, 'collectionDelete'])->name('webhooks.shopify.collections.delete');

require __DIR__.'/creator.php';
require __DIR__.'/store.php';
