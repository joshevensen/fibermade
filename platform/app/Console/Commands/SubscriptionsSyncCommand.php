<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionsSyncCommand extends Command
{
    protected $signature = 'subscriptions:sync';

    protected $description = 'Reconcile subscription_status on Creator accounts from Stripe (manual recovery).';

    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $accounts = Account::query()
            ->where('type', AccountType::Creator)
            ->whereNotNull('stripe_id')
            ->get();

        foreach ($accounts as $account) {
            $status = $this->resolveStatusFromStripe($account->stripe_id);
            if ($status !== null && $account->subscription_status !== $status) {
                $account->update(['subscription_status' => $status]);
                $this->line("Updated account {$account->id} to {$status->value}");
            }
        }

        $this->info('Sync complete.');

        return self::SUCCESS;
    }

    private function resolveStatusFromStripe(string $stripeCustomerId): ?SubscriptionStatus
    {
        try {
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
        } catch (ApiErrorException) {
            return null;
        }
    }
}
