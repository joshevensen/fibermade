<?php

use App\Http\Controllers\Api\ApiControllerTestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('health', fn () => response()->json(['status' => 'ok']));
    Route::apiResource('colorways', \App\Http\Controllers\Api\V1\ColorwayController::class)
        ->names('api.v1.colorways');
    Route::apiResource('bases', \App\Http\Controllers\Api\V1\BaseController::class)
        ->parameters(['bases' => 'base'])
        ->names('api.v1.bases');
    Route::apiResource('collections', \App\Http\Controllers\Api\V1\CollectionController::class)
        ->names('api.v1.collections');
    Route::patch('inventory/{inventory}/quantity', [\App\Http\Controllers\Api\V1\InventoryController::class, 'updateQuantity'])
        ->name('api.v1.inventory.quantity');
    Route::apiResource('inventory', \App\Http\Controllers\Api\V1\InventoryController::class)
        ->names('api.v1.inventory');
    Route::apiResource('customers', \App\Http\Controllers\Api\V1\CustomerController::class)
        ->parameters(['customers' => 'customer'])
        ->names('api.v1.customers');
    Route::apiResource('integrations', \App\Http\Controllers\Api\V1\IntegrationController::class)
        ->parameters(['integrations' => 'integration'])
        ->names('api.v1.integrations');
    Route::apiResource('orders', \App\Http\Controllers\Api\V1\OrderController::class)
        ->parameters(['orders' => 'order'])
        ->names('api.v1.orders');
    Route::get('orders/{order}/items', [\App\Http\Controllers\Api\V1\OrderItemController::class, 'index'])
        ->name('api.v1.orders.items.index');
    Route::post('orders/{order}/items', [\App\Http\Controllers\Api\V1\OrderItemController::class, 'store'])
        ->name('api.v1.orders.items.store');
    Route::get('orders/{order}/items/{orderItem}', [\App\Http\Controllers\Api\V1\OrderItemController::class, 'show'])
        ->name('api.v1.orders.items.show');
    Route::patch('orders/{order}/items/{orderItem}', [\App\Http\Controllers\Api\V1\OrderItemController::class, 'update'])
        ->name('api.v1.orders.items.update');
    Route::delete('orders/{order}/items/{orderItem}', [\App\Http\Controllers\Api\V1\OrderItemController::class, 'destroy'])
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
