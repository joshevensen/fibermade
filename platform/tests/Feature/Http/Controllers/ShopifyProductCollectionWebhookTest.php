<?php

use App\Jobs\ProcessShopifyCollectionWebhookJob;
use App\Jobs\ProcessShopifyProductWebhookJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;

// ── Helpers ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Config::set('services.shopify.webhook_secret', 'test-webhook-secret');
});

// Fibermade is the source of truth — product and collection webhooks from
// Shopify are no-ops to prevent echo loops. All endpoints return 200 with no
// side effects regardless of HMAC validity or payload contents.

// ── products/create is a no-op ────────────────────────────────────────────────

test('products/create returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.products.create'),
        ['id' => 1234567890, 'title' => 'Ocean Mist'],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyProductWebhookJob::class);
});

// ── products/update is a no-op ────────────────────────────────────────────────

test('products/update returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.products.update'),
        ['id' => 1234567890, 'title' => 'Ocean Mist Updated'],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyProductWebhookJob::class);
});

// ── products/delete is a no-op ────────────────────────────────────────────────

test('products/delete returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.products.delete'),
        ['id' => 1234567890],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyProductWebhookJob::class);
});

// ── collections/create is a no-op ─────────────────────────────────────────────

test('collections/create returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.collections.create'),
        ['id' => 555000111, 'title' => 'Fall 2024'],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyCollectionWebhookJob::class);
});

// ── collections/update is a no-op ─────────────────────────────────────────────

test('collections/update returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.collections.update'),
        ['id' => 555000111, 'title' => 'Fall 2024 Updated'],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyCollectionWebhookJob::class);
});

// ── collections/delete is a no-op ─────────────────────────────────────────────

test('collections/delete returns 200 with no side effects', function () {
    Bus::fake();

    $response = $this->postJson(
        route('webhooks.shopify.collections.delete'),
        ['id' => 555000111],
        ['X-Shopify-Hmac-Sha256' => 'any-value', 'X-Shopify-Shop-Domain' => 'test.myshopify.com'],
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyCollectionWebhookJob::class);
});
