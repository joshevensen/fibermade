<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Customer;
use App\Models\Show;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type', $this->route('order')->type?->value);
        $orderableIdRule = ['sometimes', 'nullable', 'integer'];

        if ($type === OrderType::Wholesale->value) {
            $orderableIdRule[] = Rule::exists(Store::class, 'id');
        } elseif ($type === OrderType::Retail->value) {
            $orderableIdRule[] = Rule::exists(Customer::class, 'id');
        } elseif ($type === OrderType::Show->value) {
            $orderableIdRule[] = Rule::exists(Show::class, 'id');
        }

        return [
            'type' => ['sometimes', Rule::enum(OrderType::class)],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'orderable_id' => $orderableIdRule,
            'order_date' => ['sometimes', 'date'],
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
