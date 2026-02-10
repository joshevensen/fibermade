<?php

namespace App\Http\Requests;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalIdentifierRequest extends FormRequest
{
    /**
     * Allowed morph types for identifiable_type.
     *
     * @var list<string>
     */
    private const ALLOWED_IDENTIFIABLE_TYPES = [
        Base::class,
        Colorway::class,
        Customer::class,
        Inventory::class,
        Order::class,
    ];

    /**
     * Short name to class mapping for a friendlier API.
     *
     * @var array<string, string>
     */
    private const IDENTIFIABLE_TYPE_ALIASES = [
        'base' => Base::class,
        'colorway' => Colorway::class,
        'customer' => Customer::class,
        'inventory' => Inventory::class,
        'order' => Order::class,
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $integrationId = $this->input('integration_id');
        if (! $integrationId) {
            return false;
        }

        $integration = Integration::find($integrationId);

        return $integration && $this->user()->can('view', $integration);
    }

    /**
     * Prepare the data for validation. Map short identifiable_type to full class name.
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('identifiable_type');
        if (is_string($type) && isset(self::IDENTIFIABLE_TYPE_ALIASES[strtolower($type)])) {
            $this->merge(['identifiable_type' => self::IDENTIFIABLE_TYPE_ALIASES[strtolower($type)]]);
        }
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
            'identifiable_type' => ['required', 'string', Rule::in(self::ALLOWED_IDENTIFIABLE_TYPES)],
            'identifiable_id' => ['required', 'integer', 'min:1'],
            'external_type' => ['required', 'string', 'max:255'],
            'external_id' => ['required', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
        ];
    }
}
