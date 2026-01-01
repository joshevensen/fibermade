<?php

namespace App\Http\Requests;

use App\Enums\StoreVendorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Store::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:stores'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_region' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'allows_preorders' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(StoreVendorStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
