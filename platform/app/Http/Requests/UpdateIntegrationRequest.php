<?php

namespace App\Http\Requests;

use App\Enums\IntegrationType;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('integration'));
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
            'type' => ['sometimes', Rule::enum(IntegrationType::class)],
            'credentials' => ['sometimes', 'string'],
            'settings' => ['nullable', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
