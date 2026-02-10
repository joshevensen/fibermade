<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreIntegrationRequest;
use App\Http\Requests\UpdateIntegrationRequest;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;

class IntegrationController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        $query = Integration::query();
        $integrations = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(IntegrationResource::collection($integrations));
    }

    public function show(Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        $integration->load([
            'logs' => fn ($query) => $query->latest()->limit(10),
        ]);

        return $this->successResponse(new IntegrationResource($integration));
    }

    public function store(StoreIntegrationRequest $request): JsonResponse
    {
        $integration = Integration::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return $this->createdResponse(new IntegrationResource($integration));
    }

    public function update(UpdateIntegrationRequest $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        $integration->update($request->validated());

        return $this->successResponse(new IntegrationResource($integration));
    }

    public function destroy(Integration $integration): JsonResponse
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return response()->json(null, 204);
    }
}
