<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    public function successResponse(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public function createdResponse(mixed $data): JsonResponse
    {
        return response()->json(['data' => $data], 201);
    }

    public function errorResponse(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }

    public function accountId(): ?int
    {
        return request()->user()?->account_id;
    }

    /**
     * Scope the query to the authenticated user's account. Admins see all; others see only their account or nothing.
     *
     * @param  Builder<object>  $query
     * @return Builder<object>
     */
    public function scopeToAccount(Builder $query): Builder
    {
        $user = request()->user();

        if ($user->is_admin === true) {
            return $query;
        }

        if ($user->account_id !== null) {
            return $query->where('account_id', $user->account_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
