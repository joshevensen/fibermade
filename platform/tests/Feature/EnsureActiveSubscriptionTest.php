<?php

use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\User;

test('creator with active subscription can access dashboard', function () {
    $account = Account::factory()->creator()->subscribed()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('creator with past_due subscription can access dashboard', function () {
    $account = Account::factory()->creator()->pastDue()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('creator with inactive subscription can access dashboard (read-only)', function () {
    $account = Account::factory()->creator()->inactive()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('store account bypasses subscription check on creator routes', function () {
    $account = Account::factory()->storeType()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('creator with refunded subscription is redirected to subscription expired', function () {
    $account = Account::factory()->creator()->create([
        'subscription_status' => SubscriptionStatus::Refunded,
    ]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('subscription.expired'));
});
