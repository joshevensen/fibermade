<?php

namespace App\Http\Requests;

use App\Models\Base;
use App\Models\Colorway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryQuantityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Inventory::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'colorway_id' => ['required', 'integer', Rule::exists(Colorway::class, 'id')],
            'base_id' => ['required', 'integer', Rule::exists(Base::class, 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
