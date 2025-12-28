<?php

namespace App\Http\Requests;

use App\Enums\StoreVendorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('store'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('stores')->ignore($this->route('store'))],
            'owner_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'zip' => ['sometimes', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:255'],
            'discount_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_order_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'minimum_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lead_time_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allows_preorders' => ['sometimes', 'nullable', 'boolean'],
            'status' => ['sometimes', 'nullable', Rule::enum(StoreVendorStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
