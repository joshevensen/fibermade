<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;

// ---------------------------------------------------------------------------
// connect
// ---------------------------------------------------------------------------

test('connect with valid token creates integration and returns integration_id', function () {
    $account = Account::factory()->create();

    $response = $this->postJson('/api/v1/shopify/connect', [
        'connect_token' => $account->shopify_connect_token,
        'shop' => 'example.myshopify.com',
        'shopify_access_token' => 'shpat_abc123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['integration_id']]);

    $this->assertDatabaseHas('integrations', [
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify->value,
        'active' => true,
    ]);
});

test('connect with invalid token returns 422', function () {
    $response = $this->postJson('/api/v1/shopify/connect', [
        'connect_token' => 'not-a-real-token',
        'shop' => 'example.myshopify.com',
        'shopify_access_token' => 'shpat_abc123',
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Invalid connect token']);
});

test('connect replaces existing integration and re-associates external identifiers', function () {
    $account = Account::factory()->create();
    $oldIntegration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    ExternalIdentifier::create([
        'integration_id' => $oldIntegration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/999',
    ]);

    $response = $this->postJson('/api/v1/shopify/connect', [
        'connect_token' => $account->shopify_connect_token,
        'shop' => 'newstore.myshopify.com',
        'shopify_access_token' => 'shpat_new',
    ]);

    $response->assertStatus(201);
    $newId = $response->json('data.integration_id');

    $this->assertSoftDeleted('integrations', ['id' => $oldIntegration->id]);
    expect(
        ExternalIdentifier::where('integration_id', $newId)
            ->where('external_id', 'gid://shopify/Product/999')
            ->exists()
    )->toBeTrue();
    expect(
        ExternalIdentifier::where('integration_id', $oldIntegration->id)->exists()
    )->toBeFalse();
});

test('connect validates required fields', function () {
    $response = $this->postJson('/api/v1/shopify/connect', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['connect_token', 'shop', 'shopify_access_token']);
});

// ---------------------------------------------------------------------------
// disconnect
// ---------------------------------------------------------------------------

test('disconnect with valid token and shop sets integration to inactive', function () {
    $account = Account::factory()->create();
    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'settings' => ['shop' => 'example.myshopify.com'],
    ]);

    $response = $this->postJson('/api/v1/shopify/disconnect', [
        'connect_token' => $account->shopify_connect_token,
        'shop' => 'example.myshopify.com',
    ]);

    $response->assertStatus(204);
    $this->assertDatabaseHas('integrations', [
        'account_id' => $account->id,
        'active' => false,
    ]);
});

test('disconnect with unknown token returns 204 silently', function () {
    $response = $this->postJson('/api/v1/shopify/disconnect', [
        'connect_token' => 'unknown-token-uuid',
        'shop' => 'example.myshopify.com',
    ]);

    $response->assertStatus(204);
});

test("disconnect does not deactivate another account's integration", function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    Integration::factory()->create([
        'account_id' => $accountB->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'settings' => ['shop' => 'example.myshopify.com'],
    ]);

    $this->postJson('/api/v1/shopify/disconnect', [
        'connect_token' => $accountA->shopify_connect_token,
        'shop' => 'example.myshopify.com',
    ])->assertStatus(204);

    $this->assertDatabaseHas('integrations', [
        'account_id' => $accountB->id,
        'active' => true,
    ]);
});

// ---------------------------------------------------------------------------
// status
// ---------------------------------------------------------------------------

test('status returns active true when integration exists and is active', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'settings' => ['shop' => 'example.myshopify.com'],
    ]);

    $response = $this->getJson('/api/v1/shopify/status?connect_token='.$account->shopify_connect_token.'&shop=example.myshopify.com');

    $response->assertStatus(200)
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.integration_id', $integration->id);
});

test('status returns active false when integration is inactive', function () {
    $account = Account::factory()->create();
    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => false,
        'settings' => ['shop' => 'example.myshopify.com'],
    ]);

    $response = $this->getJson('/api/v1/shopify/status?connect_token='.$account->shopify_connect_token.'&shop=example.myshopify.com');

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

test('status returns active false when no integration found for shop', function () {
    $account = Account::factory()->create();

    $response = $this->getJson('/api/v1/shopify/status?connect_token='.$account->shopify_connect_token.'&shop=notconnected.myshopify.com');

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.integration_id', null);
});

test('status returns active false when connect token is unknown', function () {
    $response = $this->getJson('/api/v1/shopify/status?connect_token=unknown-token&shop=example.myshopify.com');

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

test('status validates required query parameters', function () {
    $response = $this->getJson('/api/v1/shopify/status');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['connect_token', 'shop']);
});
