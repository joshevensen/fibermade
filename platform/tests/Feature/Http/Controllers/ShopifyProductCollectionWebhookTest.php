<?php

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Jobs\ProcessShopifyCollectionWebhookJob;
use App\Jobs\ProcessShopifyProductWebhookJob;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Services\Shopify\ShopifyCollectionSyncService;
use App\Services\Shopify\ShopifyProductSyncService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;

// ── Helpers ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Config::set('services.shopify.webhook_secret', 'test-webhook-secret');
});

function webhookHmac(string $body): string
{
    return base64_encode(hash_hmac('sha256', $body, 'test-webhook-secret', true));
}

function shopifyIntegration(array $settingsOverrides = []): Integration
{
    $account = Account::factory()->creator()->create();

    return Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => array_merge(['shop' => 'test.myshopify.com', 'auto_sync' => true], $settingsOverrides),
    ]);
}

function restProductPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 1234567890,
        'title' => 'Ocean Mist',
        'body_html' => '<p>Beautiful colorway</p>',
        'status' => 'active',
        'handle' => 'ocean-mist',
        'images' => [['src' => 'https://cdn.shopify.com/image.jpg']],
        'variants' => [
            ['id' => 9876543210, 'title' => 'Fingering', 'price' => '28.00'],
        ],
    ], $overrides);
}

function restCollectionPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 555000111,
        'title' => 'Fall 2024',
        'body_html' => '<p>Seasonal collection</p>',
        'handle' => 'fall-2024',
    ], $overrides);
}

// ── HMAC verification ──────────────────────────────────────────────────────────

test('products/create rejects invalid HMAC', function () {
    $payload = restProductPayload();
    $body = json_encode($payload);

    $response = $this->postJson(
        route('webhooks.shopify.products.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => 'bad-signature', 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(401);
});

test('products/update rejects invalid HMAC', function () {
    $payload = restProductPayload();
    $body = json_encode($payload);

    $response = $this->postJson(
        route('webhooks.shopify.products.update'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => 'bad-signature', 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(401);
});

test('products/delete rejects invalid HMAC', function () {
    $payload = ['id' => 123];
    $body = json_encode($payload);

    $response = $this->postJson(
        route('webhooks.shopify.products.delete'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => 'bad-signature', 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(401);
});

test('collections/create rejects invalid HMAC', function () {
    $payload = restCollectionPayload();

    $response = $this->postJson(
        route('webhooks.shopify.collections.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => 'bad-signature', 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(401);
});

test('collections/delete rejects invalid HMAC', function () {
    $payload = ['id' => 123];

    $response = $this->postJson(
        route('webhooks.shopify.collections.delete'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => 'bad-signature', 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(401);
});

// ── Unknown shop returns 200 silently ─────────────────────────────────────────

test('products/create returns 200 for unknown shop domain', function () {
    Bus::fake();

    $payload = restProductPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.products.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'unknown-shop.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyProductWebhookJob::class);
});

test('collections/update returns 200 for unknown shop domain', function () {
    Bus::fake();

    $payload = restCollectionPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.collections.update'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'unknown-shop.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyCollectionWebhookJob::class);
});

// ── auto_sync = false skips processing ────────────────────────────────────────

test('products/create skips job dispatch when auto_sync is disabled', function () {
    Bus::fake();
    shopifyIntegration(['auto_sync' => false]);

    $payload = restProductPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.products.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyProductWebhookJob::class);
});

test('collections/create skips job dispatch when auto_sync is disabled', function () {
    Bus::fake();
    shopifyIntegration(['auto_sync' => false]);

    $payload = restCollectionPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.collections.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertNotDispatched(ProcessShopifyCollectionWebhookJob::class);
});

// ── products/create dispatches job ────────────────────────────────────────────

test('products/create dispatches ProcessShopifyProductWebhookJob with create action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = restProductPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.products.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyProductWebhookJob::class, function ($job) {
        return $job->action === 'create'
            && $job->normalizedProduct['gid'] === 'gid://shopify/Product/1234567890'
            && $job->normalizedProduct['title'] === 'Ocean Mist'
            && $job->normalizedProduct['status'] === 'ACTIVE';
    });
});

// ── products/update dispatches job ────────────────────────────────────────────

test('products/update dispatches ProcessShopifyProductWebhookJob with update action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = restProductPayload(['status' => 'draft']);
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.products.update'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyProductWebhookJob::class, function ($job) {
        return $job->action === 'update'
            && $job->normalizedProduct['status'] === 'DRAFT';
    });
});

// ── products/delete dispatches job ────────────────────────────────────────────

test('products/delete dispatches ProcessShopifyProductWebhookJob with delete action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = ['id' => 1234567890];
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.products.delete'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyProductWebhookJob::class, function ($job) {
        return $job->action === 'delete'
            && $job->normalizedProduct['gid'] === 'gid://shopify/Product/1234567890';
    });
});

// ── collections/create dispatches job ─────────────────────────────────────────

test('collections/create dispatches ProcessShopifyCollectionWebhookJob with create action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = restCollectionPayload();
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.collections.create'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyCollectionWebhookJob::class, function ($job) {
        return $job->action === 'create'
            && $job->normalizedCollection['gid'] === 'gid://shopify/Collection/555000111'
            && $job->normalizedCollection['title'] === 'Fall 2024';
    });
});

// ── collections/update dispatches job ─────────────────────────────────────────

test('collections/update dispatches ProcessShopifyCollectionWebhookJob with update action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = restCollectionPayload(['title' => 'Spring 2025']);
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.collections.update'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyCollectionWebhookJob::class, function ($job) {
        return $job->action === 'update'
            && $job->normalizedCollection['title'] === 'Spring 2025';
    });
});

// ── collections/delete dispatches job ─────────────────────────────────────────

test('collections/delete dispatches ProcessShopifyCollectionWebhookJob with delete action', function () {
    Bus::fake();
    shopifyIntegration();

    $payload = ['id' => 555000111];
    $body = json_encode($payload);
    $hmac = webhookHmac($body);

    $response = $this->postJson(
        route('webhooks.shopify.collections.delete'),
        $payload,
        ['X-Shopify-Hmac-Sha256' => $hmac, 'X-Shopify-Shop-Domain' => 'test.myshopify.com']
    );

    $response->assertStatus(200);
    Bus::assertDispatched(ProcessShopifyCollectionWebhookJob::class, function ($job) {
        return $job->action === 'delete'
            && $job->normalizedCollection['gid'] === 'gid://shopify/Collection/555000111';
    });
});

// ── Product delete job retires colorway ───────────────────────────────────────

test('product delete job retires the mapped colorway', function () {
    $integration = shopifyIntegration();
    $colorway = Colorway::factory()->create([
        'account_id' => $integration->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/999',
    ]);

    $job = new ProcessShopifyProductWebhookJob(
        $integration,
        'delete',
        ['gid' => 'gid://shopify/Product/999']
    );
    $job->handle(app(ShopifyProductSyncService::class));

    $colorway->refresh();
    expect($colorway->status)->toBe(ColorwayStatus::Retired);
});

test('product delete job silently skips unknown product GIDs', function () {
    $integration = shopifyIntegration();

    $job = new ProcessShopifyProductWebhookJob(
        $integration,
        'delete',
        ['gid' => 'gid://shopify/Product/99999999']
    );

    // Should not throw
    $job->handle(app(ShopifyProductSyncService::class));

    expect(true)->toBeTrue();
});

// ── Collection delete job soft-deletes ────────────────────────────────────────

test('collection delete job soft-deletes the mapped collection', function () {
    $integration = shopifyIntegration();
    $collection = Collection::factory()->create([
        'account_id' => $integration->account_id,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/888',
    ]);

    $job = new ProcessShopifyCollectionWebhookJob(
        $integration,
        'delete',
        ['gid' => 'gid://shopify/Collection/888']
    );
    $job->handle(app(ShopifyCollectionSyncService::class));

    expect(Collection::find($collection->id))->toBeNull();
    expect(Collection::withTrashed()->find($collection->id))->not->toBeNull();
});

test('collection delete job silently skips unknown collection GIDs', function () {
    $integration = shopifyIntegration();

    $job = new ProcessShopifyCollectionWebhookJob(
        $integration,
        'delete',
        ['gid' => 'gid://shopify/Collection/99999999']
    );

    // Should not throw
    $job->handle(app(ShopifyCollectionSyncService::class));

    expect(true)->toBeTrue();
});
