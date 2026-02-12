<?php

use App\Http\Controllers\Api\ApiControllerTestController;
use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\ColorwayController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ExternalIdentifierController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\IntegrationLogController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('health', fn () => response()->json(['status' => 'ok']));
    Route::apiResource('colorways', ColorwayController::class)
        ->names('api.v1.colorways');
    Route::apiResource('bases', BaseController::class)
        ->parameters(['bases' => 'base'])
        ->names('api.v1.bases');
    Route::post('collections/{collection}/colorways', [CollectionController::class, 'updateColorways'])
        ->name('api.v1.collections.colorways.update');
    Route::apiResource('collections', CollectionController::class)
        ->names('api.v1.collections');
    Route::patch('inventory/{inventory}/quantity', [InventoryController::class, 'updateQuantity'])
        ->name('api.v1.inventory.quantity');
    Route::apiResource('inventory', InventoryController::class)
        ->names('api.v1.inventory');
    Route::apiResource('customers', CustomerController::class)
        ->parameters(['customers' => 'customer'])
        ->names('api.v1.customers');
    Route::apiResource('integrations', IntegrationController::class)
        ->parameters(['integrations' => 'integration'])
        ->names('api.v1.integrations');
    Route::get('integrations/{integration}/logs', [IntegrationLogController::class, 'index'])
        ->name('api.v1.integrations.logs.index');
    Route::post('integrations/{integration}/logs', [IntegrationLogController::class, 'store'])
        ->name('api.v1.integrations.logs.store');
    Route::get('external-identifiers', [ExternalIdentifierController::class, 'index'])
        ->name('api.v1.external-identifiers.index');
    Route::post('external-identifiers', [ExternalIdentifierController::class, 'store'])
        ->name('api.v1.external-identifiers.store');
    Route::apiResource('orders', OrderController::class)
        ->parameters(['orders' => 'order'])
        ->names('api.v1.orders');
    Route::get('orders/{order}/items', [OrderItemController::class, 'index'])
        ->name('api.v1.orders.items.index');
    Route::post('orders/{order}/items', [OrderItemController::class, 'store'])
        ->name('api.v1.orders.items.store');
    Route::get('orders/{order}/items/{orderItem}', [OrderItemController::class, 'show'])
        ->name('api.v1.orders.items.show');
    Route::patch('orders/{order}/items/{orderItem}', [OrderItemController::class, 'update'])
        ->name('api.v1.orders.items.update');
    Route::delete('orders/{order}/items/{orderItem}', [OrderItemController::class, 'destroy'])
        ->name('api.v1.orders.items.destroy');

    if (app()->environment('testing')) {
        Route::get('_test/success', [ApiControllerTestController::class, 'success']);
        Route::get('_test/created', [ApiControllerTestController::class, 'created']);
        Route::get('_test/error', [ApiControllerTestController::class, 'error']);
        Route::get('_test/not-found', [ApiControllerTestController::class, 'notFound']);
        Route::get('_test/account-id', [ApiControllerTestController::class, 'showAccountId']);
        Route::get('_test/scope', [ApiControllerTestController::class, 'scope']);
        Route::post('_test/validate', [ApiControllerTestController::class, 'validateFail']);
        Route::get('_test/model/{base}', [ApiControllerTestController::class, 'modelNotFound']);
        Route::get('_test/authorize', [ApiControllerTestController::class, 'authorizeFail']);
        Route::get('_test/server-error', [ApiControllerTestController::class, 'serverError']);
    }
});
