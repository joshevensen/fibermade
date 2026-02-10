<?php

namespace App\Http\Requests;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\OrderItem::class);
    }

    /**
     * Prepare the data for validation. For nested API routes, order_id comes from the URL.
     */
    protected function prepareForValidation(): void
    {
        $order = $this->route('order');
        if ($order !== null) {
            $this->merge(['order_id' => $order->id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists(Order::class, 'id')],
            'colorway_id' => ['required', 'integer', Rule::exists(Colorway::class, 'id')],
            'base_id' => ['required', 'integer', Rule::exists(Base::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'line_total' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
