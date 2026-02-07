<?php

use App\Enums\AccountType;
use App\Enums\InviteType;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Store;
use App\Models\User;

test('store can accept invite and complete registration', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'newstore@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [
            'store_name' => 'Cozy Yarn Shop',
            'owner_name' => 'Sarah Johnson',
        ],
    ]);

    $page = visit("/invites/accept/{$invite->token}");

    $page->assertSee($creator->name.' invited you to connect as a store')
        ->assertNoJavascriptErrors()
        ->assertValue('input[name="store_name"]', 'Cozy Yarn Shop')
        ->assertValue('input[name="owner_name"]', 'Sarah Johnson')
        ->assertValue('input[name="email"]', 'newstore@example.com')
        ->fill('input[name="address_line1"]', '123 Main Street')
        ->fill('input[name="city"]', 'Portland')
        ->fill('input[name="state_region"]', 'OR')
        ->fill('input[name="postal_code"]', '97201')
        ->fill('input[name="password"]', 'SecurePassword123')
        ->fill('input[name="password_confirmation"]', 'SecurePassword123')
        ->check('input[name="terms_accepted"]')
        ->check('input[name="privacy_accepted"]')
        ->click('button[type="submit"]')
        ->wait(1)
        ->assertPathIs('/store')
        ->assertNoJavascriptErrors();

    // Verify database state
    $user = User::where('email', 'newstore@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->account->type)->toBe(AccountType::Store);

    $store = Store::where('account_id', $user->account_id)->first();
    expect($store)->not->toBeNull();
    expect($store->name)->toBe('Cozy Yarn Shop');
    expect($store->owner_name)->toBe('Sarah Johnson');
    expect($store->address_line1)->toBe('123 Main Street');

    // Verify creator-store relationship
    $relatedStore = $creator->stores()->where('stores.id', $store->id)->first();
    expect($relatedStore)->not->toBeNull();

    // Verify invite was marked as accepted
    expect($invite->fresh()->accepted_at)->not->toBeNull();
});

test('store invite shows validation errors for missing required fields', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Test Store', 'owner_name' => 'Jane'],
    ]);

    $page = visit("/invites/accept/{$invite->token}");

    $page->click('button[type="submit"]')
        ->wait(0.5)
        ->assertNoJavascriptErrors();

    expect(Store::count())->toBe(0);
});

test('expired invite redirects to home page', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->subDay(),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);

    $page = visit("/invites/accept/{$invite->token}");

    $page->wait(1)
        ->assertPathIs('/')
        ->assertNoJavascriptErrors();
});
