<?php

namespace App\Http\Requests;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\IntegrationLog::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'integration_id' => ['required', 'integer', Rule::exists(Integration::class, 'id')],
            'loggable_type' => ['required', 'string', 'max:255'],
            'loggable_id' => ['required', 'integer'],
            'status' => ['required', Rule::enum(IntegrationLogStatus::class)],
            'message' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'synced_at' => ['nullable', 'date'],
        ];
    }
}
