<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\PromotionCode;
use Stripe\Stripe;

class RegisterCheckoutController extends Controller
{
    /**
     * Validate registration, store data in session, create Stripe Checkout Session, return redirect URL.
     */
    public function __invoke(RegisterCheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $promo = $request->query('promo');

        $registration = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'business_name' => $validated['business_name'],
            'password' => Hash::make($validated['password']),
            'promo_code' => $promo,
        ];

        $request->session()->put('registration', $registration);

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
                'email' => $registration['email'],
                'name' => $registration['name'],
                'business_name' => $registration['business_name'],
                'password_hash' => $registration['password'],
                'promo_code' => $registration['promo_code'] ?? '',
            ],
        ];

        if (! empty($registration['promo_code'])) {
            $promotionCodeId = $this->findPromotionCodeId($registration['promo_code']);
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
