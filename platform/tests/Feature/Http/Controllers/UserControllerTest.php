<?php

use App\Enums\IntegrationType;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Integration;
use App\Models\User;

test('resetConnectToken generates a new token', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id, 'role' => UserRole::Owner]);
    $originalToken = $account->shopify_connect_token;

    $response = $this->actingAs($user)->postJson(route('shopify-connect-token.reset'));

    $response->assertOk();
    expect($response->json('connect_token'))->not->toBe($originalToken);
    expect($account->fresh()->shopify_connect_token)->toBe($response->json('connect_token'));
});

test('resetConnectToken deactivates the active Shopify integration', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id, 'role' => UserRole::Owner]);
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
    ]);

    $this->actingAs($user)->postJson(route('shopify-connect-token.reset'));

    expect($integration->fresh()->active)->toBeFalse();
});

test('resetConnectToken requires authentication', function () {
    $response = $this->post(route('shopify-connect-token.reset'));

    $response->assertRedirectToRoute('login');
});
