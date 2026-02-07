<?php

use App\Enums\AccountType;
use App\Enums\InviteType;
use App\Mail\StoreInviteMail;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('creator can send store invite', function () {
    Mail::fake();

    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $response = $this->actingAs($user)->post(route('invites.store'), [
        'type' => InviteType::Store->value,
        'email' => 'store@example.com',
        'store_name' => 'Test Store',
        'owner_name' => 'Jane Doe',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('invites', [
        'email' => 'store@example.com',
        'invite_type' => InviteType::Store->value,
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
    ]);

    $invite = Invite::where('email', 'store@example.com')->first();
    expect($invite->metadata)->toEqual([
        'store_name' => 'Test Store',
        'owner_name' => 'Jane Doe',
    ]);
    expect($invite->token)->not->toBeEmpty();
    expect($invite->expires_at)->not->toBeNull();

    Mail::assertQueued(StoreInviteMail::class, function (StoreInviteMail $mail) use ($invite, $creator) {
        return $mail->email === 'store@example.com'
            && $mail->creatorName === $creator->name
            && $mail->inviteToken === $invite->token
            && $mail->hasTo('store@example.com');
    });
});

test('store invite validation requires email', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $response = $this->actingAs($user)->post(route('invites.store'), [
        'type' => InviteType::Store->value,
        'email' => '',
        'store_name' => null,
        'owner_name' => null,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('invites', 0);
});

test('store invite validation rejects invalid email', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $response = $this->actingAs($user)->post(route('invites.store'), [
        'type' => InviteType::Store->value,
        'email' => 'not-an-email',
        'store_name' => null,
        'owner_name' => null,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('invites', 0);
});

test('non-creator cannot create store invite', function () {
    $account = Account::factory()->storeType()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('invites.store'), [
        'type' => InviteType::Store->value,
        'email' => 'store@example.com',
        'store_name' => null,
        'owner_name' => null,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('invites', 0);
});

test('user without creator cannot create store invite', function () {
    $account = Account::factory()->creator()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    // No Creator model for this account

    $response = $this->actingAs($user)->post(route('invites.store'), [
        'type' => InviteType::Store->value,
        'email' => 'store@example.com',
        'store_name' => null,
        'owner_name' => null,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('invites', 0);
});

test('accept invite renders acceptance page when pending', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => 'test-token-123',
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Test Store', 'owner_name' => 'Jane Doe'],
    ]);

    $response = $this->get(route('invites.accept', ['token' => $invite->token]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/invites/AcceptInvitePage')
        ->where('token', $invite->token)
        ->has('invite', fn ($inv) => $inv
            ->where('store_name', 'Test Store')
            ->where('owner_name', 'Jane Doe')
            ->where('email', 'store@example.com')
        )
        ->where('creator_name', $creator->name)
    );
});

test('accept invite redirects with error when expired', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => 'expired-token',
        'expires_at' => now()->subDay(),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);

    $response = $this->get(route('invites.accept', ['token' => $invite->token]));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error');
});

test('creator can resend pending store invite', function () {
    Mail::fake();

    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => 'resend-token-123',
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Test Store', 'owner_name' => 'Jane'],
    ]);

    $response = $this->actingAs($user)->post(route('invites.resend', $invite));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invite resent.');
    Mail::assertQueued(StoreInviteMail::class, function (StoreInviteMail $mail) use ($invite, $creator) {
        return $mail->email === 'store@example.com'
            && $mail->creatorName === $creator->name
            && $mail->inviteToken === $invite->token
            && $mail->hasTo('store@example.com');
    });
});

test('non-creator cannot resend store invite', function () {
    Mail::fake();

    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);
    $account = Account::factory()->storeType()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('invites.resend', $invite));

    $response->assertForbidden();
    Mail::assertNotQueued(StoreInviteMail::class);
});

test('creator cannot resend expired invite', function () {
    Mail::fake();

    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->subDay(),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);

    $response = $this->actingAs($user)->post(route('invites.resend', $invite));

    $response->assertForbidden();
    Mail::assertNotQueued(StoreInviteMail::class);
});

test('accept store invite creates account and redirects to store dashboard', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'newstore@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'New Store', 'owner_name' => 'Jane Doe'],
    ]);

    $response = $this->post(route('invites.accept.store', ['token' => $invite->token]), [
        'store_name' => 'New Store',
        'owner_name' => 'Jane Doe',
        'email' => 'newstore@example.com',
        'address_line1' => '123 Main St',
        'city' => 'Portland',
        'state_region' => 'OR',
        'postal_code' => '97201',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $response->assertRedirect(route('store.home'));
    $this->assertAuthenticated();

    $user = User::where('email', 'newstore@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->account->type)->toBe(AccountType::Store);

    $store = Store::where('account_id', $user->account_id)->first();
    expect($store)->not->toBeNull();
    expect($store->name)->toBe('New Store');
    expect($store->email)->toBe('newstore@example.com');
    expect($store->owner_name)->toBe('Jane Doe');
    expect($store->address_line1)->toBe('123 Main St');
    expect($store->city)->toBe('Portland');
    expect($store->country_code)->toBe('US');

    $invite->refresh();
    expect($invite->accepted_at)->not->toBeNull();

    $relatedStore = $creator->stores()->where('stores.id', $store->id)->first();
    expect($relatedStore)->not->toBeNull();
    expect($relatedStore->pivot->status)->toBe('active');
});

test('accept store invite validation fails with invalid data', function () {
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

    $response = $this->post(route('invites.accept.store', ['token' => $invite->token]), [
        'store_name' => '',
        'owner_name' => null,
        'email' => 'not-an-email',
        'address_line1' => '',
        'city' => '',
        'state_region' => '',
        'postal_code' => '',
        'password' => 'short',
        'password_confirmation' => 'short',
        'terms_accepted' => false,
        'privacy_accepted' => false,
    ]);

    $response->assertSessionHasErrors(['store_name', 'email', 'password', 'terms_accepted', 'privacy_accepted']);
    $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
    $this->assertDatabaseCount('stores', 0);
});

test('accept store invite rejects expired token', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->subDay(),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Test Store', 'owner_name' => 'Jane'],
    ]);

    $response = $this->post(route('invites.accept.store', ['token' => $invite->token]), [
        'store_name' => 'Test Store',
        'owner_name' => 'Jane',
        'email' => 'store@example.com',
        'address_line1' => '123 Main St',
        'city' => 'Portland',
        'state_region' => 'OR',
        'postal_code' => '97201',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $response->assertSessionHasErrors('invite');
    $this->assertDatabaseMissing('users', ['email' => 'store@example.com']);
    $this->assertDatabaseCount('stores', 0);
});

test('accept store invite rejects already-used token', function () {
    $creator = Creator::factory()->create();
    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'accepted_at' => now(),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Test Store', 'owner_name' => 'Jane'],
    ]);

    $response = $this->post(route('invites.accept.store', ['token' => $invite->token]), [
        'store_name' => 'Test Store',
        'owner_name' => 'Jane',
        'email' => 'store@example.com',
        'address_line1' => '123 Main St',
        'city' => 'Portland',
        'state_region' => 'OR',
        'postal_code' => '97201',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $response->assertSessionHasErrors('invite');
    $this->assertDatabaseCount('stores', 0);
});
