<?php

namespace App\Http\Requests;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Media::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mediable_type' => ['required', 'string', 'max:255', Rule::in(Media::mediableTypes())],
            'mediable_id' => ['required', 'integer'],
            'file_path' => ['required', 'string', 'max:255'],
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['required', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'created_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'updated_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
