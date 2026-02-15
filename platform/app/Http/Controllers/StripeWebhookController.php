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
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret || ! $signature) {
            return response('', 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Exception) {
            return response('', 400);
        }

        $webhookEvent = WebhookEvent::firstOrCreate(
            ['stripe_id' => $event->id],
            [
                'type' => $event->type,
                'payload' => $event->toArray(),
            ]
        );

        if ($webhookEvent->processed_at !== null) {
            return response('', 200);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'charge.refunded' => $this->handleChargeRefunded($event),
                default => null,
            };
        } catch (\Throwable) {
            return response('', 500);
        }

        $webhookEvent->update(['processed_at' => now()]);

        return response('', 200);
    }

    private function handleCheckoutSessionCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;
        $metadata = $session->metadata ?? new \stdClass;
        $email = $metadata->email ?? null;
        $name = $metadata->name ?? null;
        $businessName = $metadata->business_name ?? null;
        $passwordHash = $metadata->password_hash ?? null;

        if (! $email || ! $name || ! $businessName || ! $passwordHash) {
            return;
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        DB::transaction(function () use ($session, $email, $name, $businessName, $passwordHash) {
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

            Mail::to($user->email)->send(new WelcomeMail($user));
        });
    }

    private function handleInvoicePaymentSucceeded(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        $customerId = is_string($invoice->customer) ? $invoice->customer : $invoice->customer->id ?? null;
        if (! $customerId) {
            return;
        }
        Account::where('stripe_id', $customerId)->update([
            'subscription_status' => SubscriptionStatus::Active,
        ]);
    }

    private function handleInvoicePaymentFailed(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        $customerId = is_string($invoice->customer) ? $invoice->customer : $invoice->customer->id ?? null;
        if (! $customerId) {
            return;
        }
        Account::where('stripe_id', $customerId)->update([
            'subscription_status' => SubscriptionStatus::PastDue,
        ]);
    }

    private function handleSubscriptionDeleted(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        $customerId = is_string($subscription->customer) ? $subscription->customer : $subscription->customer->id ?? null;
        if (! $customerId) {
            return;
        }
        $account = Account::where('stripe_id', $customerId)->first();
        if (! $account) {
            return;
        }
        $account->update([
            'subscription_status' => SubscriptionStatus::Inactive,
        ]);
    }

    private function handleChargeRefunded(\Stripe\Event $event): void
    {
        $charge = $event->data->object;
        $created = $charge->created ?? 0;
        $thirtyDaysAgo = now()->subDays(30)->timestamp;
        if ($created < $thirtyDaysAgo) {
            return;
        }
        $customerId = is_string($charge->customer) ? $charge->customer : $charge->customer->id ?? null;
        if (! $customerId) {
            return;
        }
        Account::where('stripe_id', $customerId)->update([
            'subscription_status' => SubscriptionStatus::Refunded,
        ]);
    }
}
