<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'type' => $this->type->value,
            'status' => $this->status->value,
            'order_date' => $this->order_date,
            'subtotal_amount' => $this->formatDecimal($this->subtotal_amount),
            'shipping_amount' => $this->formatDecimal($this->shipping_amount),
            'discount_amount' => $this->formatDecimal($this->discount_amount),
            'tax_amount' => $this->formatDecimal($this->tax_amount),
            'total_amount' => $this->formatDecimal($this->total_amount),
            'refunded_amount' => $this->formatDecimal($this->refunded_amount),
            'payment_method' => $this->payment_method,
            'source' => $this->source,
            'notes' => $this->notes,
            'orderable_type' => $this->orderable_type,
            'orderable_id' => $this->orderable_id,
            'taxes' => $this->taxes,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order_items' => $this->whenLoaded('orderItems', fn () => OrderItemResource::collection($this->orderItems)),
            'orderable' => $this->whenLoaded('orderable', fn () => $this->orderable?->toArray()),
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
