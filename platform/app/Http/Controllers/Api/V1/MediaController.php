<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\StoreMediaRequest;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

class MediaController extends ApiController
{
    /**
     * Store a newly created media (e.g. image URL reference from Shopify sync).
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $media = Media::create($validated);

        return $this->createdResponse(MediaResource::make($media));
    }
}
