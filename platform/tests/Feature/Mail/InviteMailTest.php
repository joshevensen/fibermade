<?php

use App\Enums\InviteType;
use App\Mail\StoreInviteAcceptedMail;
use App\Models\Creator;
use App\Models\Invite;
use Illuminate\Support\Facades\Mail;

test('invite accepted email dispatched when store accepts invite', function () {
    Mail::fake();

    $creator = Creator::factory()->create(['email' => 'creator@example.com']);
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

    Mail::assertQueued(StoreInviteAcceptedMail::class, function (StoreInviteAcceptedMail $mail) {
        return $mail->hasTo('creator@example.com')
            && str_contains($mail->envelope()->subject, 'New Store');
    });
});

test('invite accepted email not sent when creator and account owner both have no email', function () {
    Mail::fake();

    $creator = Creator::factory()->create(['email' => null]);
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

    Mail::assertNotQueued(StoreInviteAcceptedMail::class);
});
