<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreColorwayRequest;
use App\Http\Requests\UpdateColorwayRequest;
use App\Http\Resources\Api\V1\ColorwayResource;
use App\Models\Colorway;
use Illuminate\Http\JsonResponse;

class ColorwayController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Colorway::class);

        $query = Colorway::query()
            ->with(['collections', 'inventories.base', 'media']);
        $colorways = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(ColorwayResource::collection($colorways));
    }

    public function show(Colorway $colorway): JsonResponse
    {
        $this->authorize('view', $colorway);

        $colorway->load(['collections', 'inventories.base', 'media']);

        return $this->successResponse(new ColorwayResource($colorway));
    }

    public function store(StoreColorwayRequest $request): JsonResponse
    {
        $colorway = Colorway::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
            'created_by' => $request->user()->id,
        ]);

        return $this->createdResponse(new ColorwayResource($colorway));
    }

    public function update(UpdateColorwayRequest $request, Colorway $colorway): JsonResponse
    {
        $colorway->update(array_merge($request->validated(), [
            'updated_by' => $request->user()->id,
        ]));

        return $this->successResponse(new ColorwayResource($colorway->load(['collections', 'inventories.base', 'media'])));
    }

    public function destroy(Colorway $colorway): JsonResponse
    {
        $this->authorize('delete', $colorway);

        $colorway->delete();

        return response()->json(null, 204);
    }
}
