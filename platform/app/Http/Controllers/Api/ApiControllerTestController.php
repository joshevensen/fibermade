<?php

namespace App\Http\Controllers\Api;

use App\Models\Base;
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
}
