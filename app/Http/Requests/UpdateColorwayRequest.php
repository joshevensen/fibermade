<?php

namespace App\Http\Requests;

use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColorwayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'technique' => ['nullable', Rule::enum(Technique::class)],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string'],
            'status' => ['sometimes', Rule::enum(ColorwayStatus::class)],
            'shopify_product_id' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'updated_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
