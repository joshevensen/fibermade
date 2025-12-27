<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Order::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OrderType::class)],
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'shopify_order_id' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'subtotal_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'updated_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
