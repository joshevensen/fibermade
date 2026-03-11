<?php

namespace App\Http\Requests;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('medium'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mediable_type' => ['sometimes', 'string', 'max:255', Rule::in(Media::mediableTypes())],
            'mediable_id' => ['sometimes', 'integer'],
            'file_path' => ['sometimes', 'string', 'max:255'],
            'file_name' => ['sometimes', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'created_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'updated_by' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
