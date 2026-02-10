<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Resources\Api\V1\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;

class CollectionController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Collection::class);

        $query = Collection::query()->withCount('colorways');
        $collections = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(CollectionResource::collection($collections));
    }

    public function show(Collection $collection): JsonResponse
    {
        $this->authorize('view', $collection);

        $collection->load(['colorways']);

        return $this->successResponse(new CollectionResource($collection));
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = Collection::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return $this->createdResponse(new CollectionResource($collection));
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $collection->update($request->validated());

        return $this->successResponse(new CollectionResource($collection->load(['colorways'])));
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return response()->json(null, 204);
    }
}
