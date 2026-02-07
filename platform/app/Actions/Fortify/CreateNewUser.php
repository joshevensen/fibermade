<?php

namespace App\Actions\Fortify;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $whitelist = config('auth.registration_email_whitelist', []);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                function ($attribute, $value, $fail) use ($whitelist) {
                    if (! empty($whitelist) && ! in_array($value, $whitelist, true)) {
                        $fail('This email address is not authorized to register.');
                    }
                },
            ],
            'password' => $this->passwordRules(),
            'business_name' => ['required', 'string', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $account = Account::create([
                'status' => BaseStatus::Active,
                'type' => AccountType::Creator,
            ]);

            Creator::create([
                'account_id' => $account->id,
                'name' => $input['business_name'],
                'email' => $input['email'],
            ]);

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'account_id' => $account->id,
                'role' => UserRole::Owner,
                'terms_accepted_at' => $input['terms_accepted'] ? now() : null,
                'privacy_accepted_at' => $input['privacy_accepted'] ? now() : null,
                'marketing_opt_in' => $input['marketing_opt_in'] ?? false,
            ]);

            return $user;
        });
    }
}
