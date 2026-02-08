<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalIdentifierResource extends JsonResource
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
            'integration_id' => $this->integration_id,
            'identifiable_type' => $this->identifiable_type,
            'identifiable_id' => $this->identifiable_id,
            'external_type' => $this->external_type,
            'external_id' => $this->external_id,
            'data' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
