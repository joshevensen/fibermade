<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user->account_id) {
            return false;
        }

        $account = \App\Models\Account::find($user->account_id);

        return $account && $this->user()->can('update', $account);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(BaseStatus::class)],
            'type' => ['sometimes', Rule::enum(AccountType::class)],
            'onboarded_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
