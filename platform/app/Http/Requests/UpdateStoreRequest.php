<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
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
        $user = $this->user();
        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            return [
                'discount_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
                'minimum_order_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'minimum_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'payment_terms' => ['sometimes', 'nullable', 'string'],
                'lead_time_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'allows_preorders' => ['sometimes', 'boolean'],
                'notes' => ['sometimes', 'nullable', 'string'],
            ];
        }

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('stores')->ignore($this->route('store'))],
            'owner_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state_region' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'string', 'max:255'],
            'country_code' => ['sometimes', 'string', 'size:2'],
        ];
    }
}
