<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Mail\WelcomeMail;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class RegisterSuccessController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('login');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = StripeSession::retrieve($sessionId);
        } catch (ApiErrorException) {
            return redirect()->route('login');
        }

        if ($session->payment_status !== 'paid') {
            return redirect()->route('login');
        }

        $metadata = $session->metadata ?? new \stdClass;
        $email = $metadata->email ?? null;
        $name = $metadata->name ?? null;
        $businessName = $metadata->business_name ?? null;
        $registrationToken = $metadata->registration_token ?? null;

        if (! $email || ! $name || ! $businessName || ! $registrationToken) {
            return redirect()->route('login');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $pending = Cache::get('registration_pending:'.$registrationToken);

            if (! is_array($pending) || empty($pending['password_hash'])) {
                return redirect()->route('login');
            }

            $passwordHash = $pending['password_hash'];

            DB::transaction(function () use ($session, $email, $name, $businessName, $passwordHash, $registrationToken, &$user) {
                if (User::where('email', $email)->exists()) {
                    $user = User::where('email', $email)->first();

                    return;
                }

                $account = Account::create([
                    'status' => BaseStatus::Active,
                    'type' => AccountType::Creator,
                    'subscription_status' => SubscriptionStatus::Active,
                    'onboarded_at' => null,
                    'stripe_id' => $session->customer,
                ]);

                Creator::create([
                    'account_id' => $account->id,
                    'name' => $businessName,
                    'email' => $email,
                ]);

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(32),
                    'account_id' => $account->id,
                    'role' => UserRole::Owner,
                    'terms_accepted_at' => now(),
                    'privacy_accepted_at' => now(),
                    'marketing_opt_in' => false,
                ]);
                DB::table('users')->where('id', $user->id)->update(['password' => $passwordHash]);

                Cache::forget('registration_pending:'.$registrationToken);

                Mail::to($user->email)->send(new WelcomeMail($user));
            });
        }

        if (! $user) {
            return redirect()->route('login');
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
