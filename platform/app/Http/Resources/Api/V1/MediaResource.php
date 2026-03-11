<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'mediable_type' => $this->mediable_type,
            'mediable_id' => $this->mediable_id,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'is_primary' => $this->is_primary,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
