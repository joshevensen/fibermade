<?php

namespace App\Http\Controllers\Api;

use App\Models\Base;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Test-only controller to exercise ApiController response helpers and account scoping.
 * Routes are registered only when app()->environment('testing').
 */
class ApiControllerTestController extends ApiController
{
    public function success(Request $request): JsonResponse
    {
        return $this->successResponse(['key' => 'value']);
    }

    public function created(Request $request): JsonResponse
    {
        return $this->createdResponse(['id' => 1]);
    }

    public function error(Request $request): JsonResponse
    {
        return $this->errorResponse('Validation failed', ['field' => ['Error message']], 422);
    }

    public function notFound(Request $request): JsonResponse
    {
        return $this->notFoundResponse('Resource not found');
    }

    public function showAccountId(Request $request): JsonResponse
    {
        return $this->successResponse(['account_id' => $this->accountId()]);
    }

    public function scope(Request $request): JsonResponse
    {
        $query = Base::query();
        $scoped = $this->scopeToAccount($query);
        $count = $scoped->count();

        return $this->successResponse(['count' => $count]);
    }

    public function validateFail(Request $request): JsonResponse
    {
        $request->validate(['required_field' => 'required']);

        return $this->successResponse([]);
    }

    public function modelNotFound(Base $base): JsonResponse
    {
        return $this->successResponse(['id' => $base->id]);
    }

    public function authorizeFail(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Base::class);

        return $this->successResponse([]);
    }

    public function serverError(Request $request): never
    {
        throw new Exception('test');
    }
}
