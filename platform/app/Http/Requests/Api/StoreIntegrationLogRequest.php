<?php

namespace App\Http\Requests\Api;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationLogRequest extends FormRequest
{
    /**
     * Authorize that the authenticated user can view the integration (and thus create logs for it).
     */
    public function authorize(): bool
    {
        $integration = $this->route('integration');
        if (! $integration instanceof Integration) {
            return false;
        }

        return $this->user()->can('view', $integration);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loggable_type' => ['required', 'string', 'max:255'],
            'loggable_id' => ['required', 'integer'],
            'status' => ['required', Rule::enum(IntegrationLogStatus::class)],
            'message' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'synced_at' => ['nullable', 'date'],
        ];
    }
}
