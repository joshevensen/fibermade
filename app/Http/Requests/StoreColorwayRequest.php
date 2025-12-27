<?php

namespace App\Http\Requests;

use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColorwayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Colorway::class);
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
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'technique' => ['nullable', Rule::enum(Technique::class)],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string'],
            'status' => ['required', Rule::enum(ColorwayStatus::class)],
            'shopify_product_id' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'updated_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
