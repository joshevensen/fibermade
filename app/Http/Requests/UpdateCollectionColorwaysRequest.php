<?php

namespace App\Http\Requests;

use App\Models\Colorway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionColorwaysRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('collection'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'colorway_ids' => ['required', 'array'],
            'colorway_ids.*' => ['integer', Rule::exists(Colorway::class, 'id')],
        ];
    }
}
