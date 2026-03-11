<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionSyncService
{
    /**
     * Reconcile subscription_status for the account from Stripe.
     * No-op if account has no stripe_id or Stripe API fails (logged).
     */
    public function syncAccount(Account $account): bool
    {
        if (! $account->stripe_id) {
            return false;
        }

        $status = $this->resolveStatusFromStripe($account->stripe_id);

        if ($status === null) {
            return false;
        }

        if ($account->subscription_status === $status) {
            return false;
        }

        $account->update(['subscription_status' => $status]);

        return true;
    }

    /**
     * Resolve subscription status from Stripe for a customer ID.
     *
     * @return SubscriptionStatus|null null on Stripe API error
     */
    public function resolveStatusFromStripe(string $stripeCustomerId): ?SubscriptionStatus
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $subscriptions = Subscription::all([
                'customer' => $stripeCustomerId,
                'status' => 'all',
                'limit' => 1,
            ]);
            $sub = $subscriptions->data[0] ?? null;

            if (! $sub) {
                return SubscriptionStatus::Inactive;
            }

            return match ($sub->status) {
                'active', 'trialing' => SubscriptionStatus::Active,
                'past_due', 'unpaid' => SubscriptionStatus::PastDue,
                'canceled', 'cancelled' => $sub->cancel_at_period_end ? SubscriptionStatus::Cancelled : SubscriptionStatus::Inactive,
                'incomplete', 'incomplete_expired' => SubscriptionStatus::Inactive,
                default => SubscriptionStatus::Inactive,
            };
        } catch (ApiErrorException $e) {
            Log::warning('Subscription sync failed for Stripe customer', [
                'stripe_customer_id' => $stripeCustomerId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
