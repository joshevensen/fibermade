<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\StoreIntegrationLogRequest;
use App\Http\Resources\Api\V1\IntegrationLogResource;
use App\Models\Integration;
use App\Models\IntegrationLog;
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

    /**
     * Create a log entry for the given integration.
     */
    public function store(StoreIntegrationLogRequest $request, Integration $integration): JsonResponse
    {
        $log = IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => $request->validated('loggable_type'),
            'loggable_id' => $request->validated('loggable_id'),
            'status' => $request->validated('status'),
            'message' => $request->validated('message'),
            'metadata' => $request->validated('metadata'),
            'synced_at' => $request->validated('synced_at'),
        ]);

        return $this->successResponse(IntegrationLogResource::make($log), 201);
    }
}
