<?php

namespace App\Http\Requests;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('base'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['sometimes', 'integer', Rule::exists(Account::class, 'id')],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(BaseStatus::class)],
            'weight' => ['nullable', Rule::enum(Weight::class)],
            'descriptor' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'wool_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nylon_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alpaca_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'yak_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'camel_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cotton_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bamboo_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'silk_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'linen_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
