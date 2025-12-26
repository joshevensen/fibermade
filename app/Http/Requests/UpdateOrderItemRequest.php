<?php

namespace App\Http\Requests;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('orderItem'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'integer', Rule::exists(Order::class, 'id')],
            'colorway_id' => ['sometimes', 'integer', Rule::exists(Colorway::class, 'id')],
            'base_id' => ['sometimes', 'integer', Rule::exists(Base::class, 'id')],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'line_total' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
