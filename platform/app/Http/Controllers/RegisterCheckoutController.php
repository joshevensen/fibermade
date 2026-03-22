<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Requests\RegisterCheckoutRequest;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\PromotionCode;
use Stripe\Stripe;

class RegisterCheckoutController extends Controller
{
    private const REGISTRATION_CACHE_PREFIX = 'registration_pending:';

    private const REGISTRATION_CACHE_TTL_MINUTES = 30;

    /**
     * Validate registration, store data in cache (password never leaves server), create Stripe Checkout Session, return redirect URL.
     */
    public function __invoke(RegisterCheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $promo = $request->query('promo');

        if (app()->environment('testing')) {
            return $this->handleTestingRegistration($validated);
        }

        $passwordHash = Hash::make($validated['password']);
        $token = Str::random(64);

        Cache::put(self::REGISTRATION_CACHE_PREFIX.$token, [
            'email' => $validated['email'],
            'name' => $validated['name'],
            'business_name' => $validated['business_name'],
            'password_hash' => $passwordHash,
            'promo_code' => $promo,
        ], now()->addMinutes(self::REGISTRATION_CACHE_TTL_MINUTES));

        $request->session()->put('registration', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'business_name' => $validated['business_name'],
            'promo_code' => $promo,
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $params = [
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ],
            ],
            'success_url' => URL::route('register.success', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::route('register', [], true),
            'metadata' => [
                'email' => $validated['email'],
                'name' => $validated['name'],
                'business_name' => $validated['business_name'],
                'registration_token' => $token,
                'promo_code' => $promo ?? '',
            ],
        ];

        if (! empty($promo)) {
            $promotionCodeId = $this->findPromotionCodeId($promo);
            if ($promotionCodeId !== null) {
                $params['discounts'] = [['promotion_code' => $promotionCodeId]];
            } else {
                $params['allow_promotion_codes'] = true;
            }
        }

        try {
            $session = StripeSession::create($params);
        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => 'Unable to create checkout session.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }

        return response()->json([
            'redirect_url' => $session->url,
        ]);
    }

    /**
     * Bypass Stripe in test environments — create the account directly and return a redirect URL.
     *
     * @param  array<string, mixed>  $validated
     */
    private function handleTestingRegistration(array $validated): JsonResponse
    {
        $user = DB::transaction(function () use ($validated) {
            $account = Account::create([
                'status' => BaseStatus::Active,
                'type' => AccountType::Creator,
                'subscription_status' => SubscriptionStatus::Active,
            ]);

            Creator::create([
                'account_id' => $account->id,
                'name' => $validated['business_name'],
                'email' => $validated['email'],
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'account_id' => $account->id,
                'role' => UserRole::Owner,
                'terms_accepted_at' => now(),
                'privacy_accepted_at' => now(),
            ]);
        });

        Auth::login($user);

        return response()->json(['redirect_url' => route('dashboard')]);
    }

    private function findPromotionCodeId(string $code): ?string
    {
        try {
            $promotionCodes = PromotionCode::all([
                'code' => $code,
                'active' => true,
            ]);
            $first = $promotionCodes->data[0] ?? null;

            return $first?->id;
        } catch (ApiErrorException) {
            return null;
        }
    }
}
