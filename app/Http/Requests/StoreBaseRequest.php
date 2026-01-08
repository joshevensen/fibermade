<?php

namespace App\Http\Requests;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Base::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(BaseStatus::class)],
            'weight' => ['nullable', Rule::enum(Weight::class)],
            'descriptor' => ['required', 'string', 'max:255'],
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
