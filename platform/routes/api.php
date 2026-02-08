<?php

use App\Http\Controllers\Api\ApiControllerTestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('health', fn () => response()->json(['status' => 'ok']));

    // Resource API routes will be registered here.

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
