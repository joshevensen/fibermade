<?php

use App\Models\ExternalIdentifier;
use App\Models\Inventory;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.shopify.webhook_secret', 'test-webhook-secret');
});

function validHmacForWebhook(string $body, string $secret): string
{
    return base64_encode(hash_hmac('sha256', $body, $secret, true));
}

// TODO: inventory webhook processing is disabled pre-launch — the handler returns 200
// immediately for all requests. Tests below reflect that. Re-enable and restore
// per-test assertions (HMAC validation, inventory updates, etc.) when inventory ships.

test('webhook returns 200 (processing disabled pre-launch)', function () {
    $payload = ['inventory_item_id' => 12345, 'available' => 10];
    $body = json_encode($payload);

    $response = $this->postJson(
        route('webhooks.shopify.inventory'),
        $payload,
        [
            'X-Shopify-Topic' => 'inventory_levels/update',
            'X-Shopify-Hmac-Sha256' => 'any-value',
            'X-Shopify-Shop-Domain' => 'test.myshopify.com',
        ]
    );

    $response->assertOk();
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

// TODO: when inventory ships, restore these tests with proper assertions:
// - webhook uses on_hand from API (not `available` from payload)
// - webhook finds correct Inventory via ExternalIdentifier and logs the sync
// - webhook only pulls, never pushes (no sync loop)
// These scenarios are fully implemented in processInventoryWebhook() and
// tested behavior is preserved there for re-enabling.
