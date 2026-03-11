<?php

namespace App\Http\Requests\Api;

use App\Models\Colorway;
use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Authorize that the user can create media and the mediable belongs to their account.
     */
    public function authorize(): bool
    {
        if (! $this->user()->can('create', Media::class)) {
            return false;
        }

        $mediableType = $this->input('mediable_type');
        $mediableId = (int) $this->input('mediable_id');

        if ($mediableType === Colorway::class) {
            $colorway = Colorway::find($mediableId);
            if ($colorway === null) {
                return false;
            }

            return $this->user()->can('view', $colorway);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mediable_type' => ['required', 'string', 'max:255', 'in:'.Colorway::class],
            'mediable_id' => ['required', 'integer', 'min:1'],
            'file_path' => ['required', 'string', 'max:1024'],
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'in:image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml'],
            'size' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['required', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
