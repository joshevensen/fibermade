<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
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
            'descriptor' => $this->descriptor,
            'description' => $this->description,
            'code' => $this->code,
            'status' => $this->status->value,
            'weight' => $this->weight?->value,
            'size' => $this->size,
            'cost' => $this->formatDecimal($this->cost),
            'retail_price' => $this->formatDecimal($this->retail_price),
            'wool_percent' => $this->formatDecimal($this->wool_percent),
            'nylon_percent' => $this->formatDecimal($this->nylon_percent),
            'alpaca_percent' => $this->formatDecimal($this->alpaca_percent),
            'yak_percent' => $this->formatDecimal($this->yak_percent),
            'camel_percent' => $this->formatDecimal($this->camel_percent),
            'cotton_percent' => $this->formatDecimal($this->cotton_percent),
            'bamboo_percent' => $this->formatDecimal($this->bamboo_percent),
            'silk_percent' => $this->formatDecimal($this->silk_percent),
            'linen_percent' => $this->formatDecimal($this->linen_percent),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
