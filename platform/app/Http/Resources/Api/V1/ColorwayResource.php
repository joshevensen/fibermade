<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ColorwayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'technique' => $this->technique?->value,
            'colors' => $this->colors?->map(fn ($c) => $c->value)->values()->all() ?? [],
            'per_pan' => $this->per_pan,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'collections' => $this->whenLoaded('collections', fn () => CollectionResource::collection($this->collections)),
            'inventories' => $this->whenLoaded('inventories', fn () => InventoryResource::collection($this->inventories)),
            'primary_image_url' => $this->whenLoaded('media', fn () => $this->primaryImageUrlFromLoadedMedia()),
        ];
    }

    private function primaryImageUrlFromLoadedMedia(): ?string
    {
        $primaryMedia = $this->media->where('is_primary', true)->first();
        $media = $primaryMedia ?? $this->media->first();

        if ($media) {
            return Storage::disk('public')->url($media->file_path);
        }

        return null;
    }
}
