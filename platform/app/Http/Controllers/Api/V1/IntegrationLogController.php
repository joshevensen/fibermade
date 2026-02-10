<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\IntegrationLogResource;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationLogController extends ApiController
{
    /**
     * List logs for the given integration, newest first.
     */
    public function index(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        $limit = min((int) $request->input('limit', 50), 100);

        $logs = $integration->logs()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $this->successResponse(IntegrationLogResource::collection($logs));
    }
}
