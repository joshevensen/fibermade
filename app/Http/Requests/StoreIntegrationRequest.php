<?php

namespace App\Http\Requests;

use App\Enums\IntegrationType;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Integration::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists(Account::class, 'id')],
            'type' => ['required', Rule::enum(IntegrationType::class)],
            'credentials' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
            'active' => ['required', 'boolean'],
        ];
    }
}
