<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryQuantityRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Resources\Api\V1\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;

class InventoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $query = Inventory::query()->with(['colorway', 'base']);
        $inventories = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(InventoryResource::collection($inventories));
    }

    public function show(Inventory $inventory): JsonResponse
    {
        $this->authorize('view', $inventory);

        $inventory->load(['colorway', 'base']);

        return $this->successResponse(new InventoryResource($inventory));
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $inventory = Inventory::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        $inventory->load(['colorway', 'base']);

        return $this->createdResponse(new InventoryResource($inventory));
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        $inventory->update($request->validated());

        return $this->successResponse(new InventoryResource($inventory->load(['colorway', 'base'])));
    }

    public function destroy(Inventory $inventory): JsonResponse
    {
        $this->authorize('delete', $inventory);

        $inventory->delete();

        return response()->json(null, 204);
    }

    public function updateQuantity(UpdateInventoryQuantityRequest $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('update', $inventory);

        $validated = $request->validated();
        $user = $request->user();

        $inventory = Inventory::updateOrCreate(
            [
                'account_id' => $user->account_id,
                'colorway_id' => $validated['colorway_id'],
                'base_id' => $validated['base_id'],
            ],
            [
                'quantity' => $validated['quantity'],
            ]
        );

        return $this->successResponse(new InventoryResource($inventory->load(['colorway', 'base'])));
    }
}
