<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
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
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'colorways' => $this->whenLoaded('colorways', fn () => ColorwayResource::collection($this->colorways)),
        ];
    }
}
