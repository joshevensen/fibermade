<?php

namespace App\Http\Requests;

use App\Enums\IntegrationType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
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
            'user_id' => ['sometimes', 'integer', Rule::exists(User::class, 'id')],
            'type' => ['sometimes', Rule::enum(IntegrationType::class)],
            'credentials' => ['sometimes', 'string'],
            'settings' => ['nullable', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
