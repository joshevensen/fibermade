<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationLogResource extends JsonResource
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
            'loggable_type' => $this->loggable_type,
            'loggable_id' => $this->loggable_id,
            'status' => $this->status->value,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'synced_at' => $this->synced_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
