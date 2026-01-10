<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Account::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BaseStatus::class)],
            'type' => ['required', Rule::enum(AccountType::class)],
            'onboarded_at' => ['nullable', 'date'],
        ];
    }
}
