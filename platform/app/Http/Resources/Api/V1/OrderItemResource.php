<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'colorway_id' => $this->colorway_id,
            'base_id' => $this->base_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->formatDecimal($this->unit_price),
            'line_total' => $this->formatDecimal($this->line_total),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'colorway' => $this->whenLoaded('colorway', fn () => new ColorwayResource($this->colorway)),
            'base' => $this->whenLoaded('base', fn () => new BaseResource($this->base)),
        ];
    }

    private function formatDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
