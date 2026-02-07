<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inventory'));
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
            'colorway_id' => ['sometimes', 'integer', Rule::exists(Colorway::class, 'id')],
            'base_id' => ['sometimes', 'integer', Rule::exists(Base::class, 'id')],
            'quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
