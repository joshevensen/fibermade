<?php

namespace App\Http\Requests;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('integrationLog'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'integration_id' => ['sometimes', 'integer', Rule::exists(Integration::class, 'id')],
            'loggable_type' => ['sometimes', 'string', 'max:255'],
            'loggable_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', Rule::enum(IntegrationLogStatus::class)],
            'message' => ['sometimes', 'string'],
            'metadata' => ['nullable', 'array'],
            'synced_at' => ['nullable', 'date'],
        ];
    }
}
