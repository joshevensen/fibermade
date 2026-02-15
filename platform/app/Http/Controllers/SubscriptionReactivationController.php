<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\SubscriptionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionReactivationController extends Controller
{
    /**
     * Create a Stripe Checkout Session for reactivation and redirect the Creator.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $account = $request->user()?->account;

        if (! $account || $account->type !== AccountType::Creator) {
            abort(403, 'Reactivation is only available for Creator accounts.');
        }

        if (! in_array($account->subscription_status, [SubscriptionStatus::Inactive, SubscriptionStatus::Cancelled], true)) {
            return redirect()->route('dashboard');
        }

        if (! $account->stripe_id) {
            abort(403, 'No billing account found. Please contact support.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = StripeSession::create([
                'mode' => 'subscription',
                'customer' => $account->stripe_id,
                'line_items' => [
                    [
                        'price' => config('services.stripe.price_id'),
                        'quantity' => 1,
                    ],
                ],
                'success_url' => URL::route('dashboard', [], true).'?reactivated=1',
                'cancel_url' => URL::route('subscription.expired', [], true),
            ]);
        } catch (ApiErrorException $e) {
            abort(502, 'Unable to start reactivation. Please try again or contact support.');
        }

        return redirect()->away($session->url);
    }
}
