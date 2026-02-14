<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Creator;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderBuilderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || $user->account?->type !== AccountType::Store || ! $user->account->store) {
            return false;
        }

        $creator = $this->route('creator');

        if (! $creator instanceof Creator) {
            return false;
        }

        return $user->account->store->creators()->where('creator_id', $creator->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orderIdRule = $this->routeIs('store.creator.order.submit')
            ? ['required', 'integer', Rule::exists(Order::class, 'id')]
            : ['nullable', 'integer', Rule::exists(Order::class, 'id')];

        $rules = [
            'order_id' => $orderIdRule,
            'notes' => ['nullable', 'string'],
        ];

        if ($this->routeIs('store.creator.order.save')) {
            $rules['items'] = ['required', 'array'];
            $rules['items.*.colorway_id'] = ['required', 'integer'];
            $rules['items.*.base_id'] = ['required', 'integer'];
            $rules['items.*.quantity'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }
}
