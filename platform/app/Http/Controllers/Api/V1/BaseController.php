<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreBaseRequest;
use App\Http\Requests\UpdateBaseRequest;
use App\Http\Resources\Api\V1\BaseResource;
use App\Models\Base;
use Illuminate\Http\JsonResponse;

class BaseController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Base::class);

        $query = Base::query();
        $bases = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(BaseResource::collection($bases));
    }

    public function show(Base $base): JsonResponse
    {
        $this->authorize('view', $base);

        $base->load(['inventories']);

        return $this->successResponse(new BaseResource($base));
    }

    public function store(StoreBaseRequest $request): JsonResponse
    {
        $base = Base::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return $this->createdResponse(new BaseResource($base));
    }

    public function update(UpdateBaseRequest $request, Base $base): JsonResponse
    {
        $base->update($request->validated());

        return $this->successResponse(new BaseResource($base->load(['inventories'])));
    }

    public function destroy(Base $base): JsonResponse
    {
        $this->authorize('delete', $base);

        $base->delete();

        return response()->json(null, 204);
    }
}
