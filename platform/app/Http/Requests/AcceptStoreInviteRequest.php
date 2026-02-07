<?php

namespace App\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\Invite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AcceptStoreInviteRequest extends FormRequest
{
    use PasswordValidationRules;

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
            'store_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'address_line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_region' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invite = Invite::where('token', $this->route('token'))->first();

            if (! $invite) {
                abort(404);
            }

            if (! $invite->isPending()) {
                $validator->errors()->add(
                    'invite',
                    'This invite has expired or was already used.'
                );
            }
        });
    }

    /**
     * Get the URL to redirect to on validation failure.
     */
    protected function getRedirectUrl(): string
    {
        return route('invites.accept', ['token' => $this->route('token')]);
    }
}
