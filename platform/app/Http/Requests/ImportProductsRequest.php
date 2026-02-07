<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'collections_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'products_file.required' => 'The products file is required.',
            'products_file.file' => 'The products file must be a valid file.',
            'products_file.mimes' => 'The products file must be a CSV file.',
            'products_file.max' => 'The products file may not be greater than 10MB.',
            'collections_file.required' => 'The collections file is required.',
            'collections_file.file' => 'The collections file must be a valid file.',
            'collections_file.mimes' => 'The collections file must be a CSV file.',
            'collections_file.max' => 'The collections file may not be greater than 10MB.',
        ];
    }
}
