<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\BillingPortalController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ColorwayController;
use App\Http\Controllers\Creator\ShopifySyncController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DyeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SubscriptionReactivationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveSubscriptionMiddleware;
use App\Http\Middleware\EnsureCreatorCanWriteMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('creator')->middleware(['auth', 'verified', EnsureActiveSubscriptionMiddleware::class, EnsureCreatorCanWriteMiddleware::class])->group(function () {

    // Dashboard route
    Route::get('', [DashboardController::class, 'index']);
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Settings routes
    Route::get('settings', [UserController::class, 'edit'])->name('user.edit');
    Route::patch('settings/profile', [UserController::class, 'update'])->name('user.update');
    Route::put('settings/password', [UserController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
    Route::delete('settings/profile', [UserController::class, 'destroy'])->name('user.destroy');
    Route::delete('settings/account', [UserController::class, 'destroyAccount'])->name('account.destroy');
    Route::patch('settings/account', [AccountController::class, 'update'])->name('account.update');
    Route::post('settings/shopify-connect-token/reset', [UserController::class, 'resetConnectToken'])->name('shopify-connect-token.reset');

    // Colorways routes
    Route::patch('colorways/{colorway}/collections', [ColorwayController::class, 'updateCollections'])->name('colorways.collections.update');
    Route::post('colorways/{colorway}/push-to-shopify', [ColorwayController::class, 'pushToShopify'])->name('colorways.push-to-shopify');
    Route::post('colorways/{colorway}/media', [ColorwayController::class, 'storeMedia'])->name('colorways.media.store');
    Route::delete('colorways/{colorway}/media/{media}', [ColorwayController::class, 'destroyMedia'])->name('colorways.media.destroy');
    Route::resource('colorways', ColorwayController::class)->except(['create']);

    // Bases routes
    Route::resource('bases', BaseController::class)
        ->except(['create'])
        ->parameters(['bases' => 'base']);

    // Customers routes
    // TODO: In Stage 2, re-enable customer write operations (store, update, destroy, update-notes).
    // These routes are currently disabled via CustomerPolicy in Stage 1.
    Route::resource('customers', CustomerController::class)->except(['create']);
    Route::patch('customers/{customer}/notes', [CustomerController::class, 'updateNotes'])->name('customers.update-notes');

    // Dyes routes
    Route::resource('dyes', DyeController::class)->except(['create']);
    Route::patch('dyes/{dye}/toggle-field', [DyeController::class, 'toggleField'])->name('dyes.toggle-field');
    Route::patch('dyes/{dye}/notes', [DyeController::class, 'updateNotes'])->name('dyes.update-notes');

    // Collections routes
    Route::patch('collections/{collection}/colorways', [CollectionController::class, 'updateColorways'])->name('collections.colorways.update');
    Route::post('collections/{collection}/push-to-shopify', [CollectionController::class, 'pushToShopify'])->name('collections.push-to-shopify');
    Route::resource('collections', CollectionController::class)->except(['create']);

    // Media routes
    Route::resource('media', MediaController::class)->except(['create']);

    // Inventory routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/push-to-shopify', [InventoryController::class, 'pushToShopify'])->name('inventory.pushToShopify');
    Route::patch('inventory/quantity', [InventoryController::class, 'updateQuantity'])->name('inventory.updateQuantity');

    // Stores routes
    Route::patch('stores/{store}/status', [StoreController::class, 'updateStatus'])->name('stores.status');
    Route::resource('stores', StoreController::class)->except(['create']);

    // Invites routes
    Route::post('invites', [InviteController::class, 'store'])->name('invites.store');
    Route::post('invites/{invite}/resend', [InviteController::class, 'resend'])->name('invites.resend');

    // Orders routes
    // TODO: Re-enable order write operations (store, update, destroy) when ready to work on orders.
    // These routes are currently disabled via OrderPolicy.
    Route::resource('orders', OrderController::class)->except(['create']);
    Route::patch('orders/{order}/submit', [OrderController::class, 'submit'])->name('orders.submit');
    Route::patch('orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::patch('orders/{order}/fulfill', [OrderController::class, 'fulfill'])->name('orders.fulfill');
    Route::patch('orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::resource('order-items', OrderItemController::class);

    // Shows routes
    Route::resource('shows', ShowController::class)->except(['create']);

    // Shopify sync routes
    Route::prefix('shopify')->name('shopify.')->group(function () {
        Route::post('pull/all', [ShopifySyncController::class, 'pullAll'])->name('pull.all');
        Route::post('pull/colorways', [ShopifySyncController::class, 'pullColorways'])->name('pull.colorways');
        Route::post('pull/collections', [ShopifySyncController::class, 'pullCollections'])->name('pull.collections');
        Route::post('pull/inventory', [ShopifySyncController::class, 'pullInventory'])->name('pull.inventory');
        Route::post('push/bases', [ShopifySyncController::class, 'pushBases'])->name('push.bases');
        Route::get('pull/status', [ShopifySyncController::class, 'status'])->name('pull.status');
        Route::patch('settings', [ShopifySyncController::class, 'updateSettings'])->name('settings.update');
        Route::post('push/colorways', [ShopifySyncController::class, 'pushColorways'])->name('push.colorways');
        Route::post('push/collections', [ShopifySyncController::class, 'pushCollections'])->name('push.collections');
        Route::post('push/all', [ShopifySyncController::class, 'pushAll'])->name('push.all');
        Route::post('{integration}/errors/dismiss', [ShopifySyncController::class, 'dismissErrors'])->name('errors.dismiss');
    });

    // Billing
    Route::get('subscription/expired', fn () => Inertia::render('creator/SubscriptionExpiredPage'))->name('subscription.expired');
    Route::get('subscription/reactivate', SubscriptionReactivationController::class)->name('subscription.reactivate');
    Route::get('billing/portal', BillingPortalController::class)->name('billing.portal');
});
