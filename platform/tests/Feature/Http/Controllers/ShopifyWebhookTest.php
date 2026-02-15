<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\Shopify\ShopifyGraphqlClient;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.shopify.webhook_secret', 'test-webhook-secret');
});

function validHmacForWebhook(string $body, string $secret): string
{
    return base64_encode(hash_hmac('sha256', $body, $secret, true));
}

test('webhook rejects invalid HMAC signature', function () {
    $payload = ['inventory_item_id' => 12345, 'available' => 10];
    $body = json_encode($payload);

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => 'invalid-signature',
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertStatus(401);
});

test('webhook rejects missing HMAC header', function () {
    $payload = ['inventory_item_id' => 12345, 'available' => 10];

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertStatus(401);
});

test('webhook returns 400 when payload missing required fields', function () {
    $payload = ['inventory_item_id' => 12345];
    $hmac = validHmacForWebhook(json_encode($payload), 'test-webhook-secret');

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertStatus(400);
});

test('webhook returns 200 when no integration for shop domain', function () {
    $payload = ['inventory_item_id' => 12345, 'available' => 10];
    $hmac = validHmacForWebhook(json_encode($payload), 'test-webhook-secret');

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'nonexistent-shop.myshopify.com',
        ]
    );

    $response->assertStatus(200);
});

test('webhook ignores non inventory_levels topic', function () {
    $payload = ['id' => 1];
    $hmac = validHmacForWebhook(json_encode($payload), 'test-webhook-secret');

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertStatus(200);
});

test('webhook updates Fibermade inventory from Shopify', function () {
    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
    ]);
    $variantGid = 'gid://shopify/ProductVariant/9001';
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('getVariantIdFromInventoryItemId')
        ->with(\Mockery::type('string'))
        ->andReturn($variantGid);

    $this->app->bind('shopify.graphql_client_resolver', fn () => fn () => $mockClient);

    $payload = ['inventory_item_id' => 99999, 'available' => 12];
    $body = json_encode($payload);
    $hmac = validHmacForWebhook($body, 'test-webhook-secret');

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertStatus(200);
    $inventory->refresh();
    expect($inventory->quantity)->toBe(12);
});

test('webhook finds correct Inventory via ExternalIdentifier', function () {
    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
    ]);
    $variantGid = 'gid://shopify/ProductVariant/9002';
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('getVariantIdFromInventoryItemId')->andReturn($variantGid);

    $this->app->bind('shopify.graphql_client_resolver', fn () => fn () => $mockClient);

    $payload = ['inventory_item_id' => 88888, 'available' => 7];
    $hmac = validHmacForWebhook(json_encode($payload), 'test-webhook-secret');

    $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $log = IntegrationLog::where('loggable_type', Inventory::class)
        ->where('loggable_id', $inventory->id)
        ->where('integration_id', $integration->id)
        ->latest()
        ->first();
    expect($log)->not->toBeNull();
    expect($log->metadata['shopify_variant_id'] ?? null)->toBe($variantGid);
    expect(Inventory::find($inventory->id)->quantity)->toBe(7);
});

test('webhook prevents sync loops by only pulling never pushing', function () {
    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 1,
    ]);
    $variantGid = 'gid://shopify/ProductVariant/9003';
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('getVariantIdFromInventoryItemId')->andReturn($variantGid);

    $this->app->bind('shopify.graphql_client_resolver', fn () => fn () => $mockClient);

    $payload = ['inventory_item_id' => 77777, 'available' => 9];
    $hmac = validHmacForWebhook(json_encode($payload), 'test-webhook-secret');

    $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $inventory->refresh();
    expect($inventory->quantity)->toBe(9);
    $log = IntegrationLog::where('loggable_id', $inventory->id)->where('loggable_type', Inventory::class)->latest()->first();
    expect($log->metadata['direction'])->toBe('pull');
});
