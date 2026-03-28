<?php

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncColorwayCatalogToShopifyJob;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('SyncColorwayCatalogToShopifyJob created path calls pushAllInventoryForColorway', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    $mock = $this->mock(InventorySyncService::class);
    $mock->shouldReceive('pushAllInventoryForColorway')
        ->once()
        ->with(
            Mockery::on(fn ($c) => $c->id === $colorway->id),
            Mockery::on(fn ($i) => $i->id === $this->integration->id),
            'observer'
        );

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'created');
    $job->handle();
});

test('SyncColorwayCatalogToShopifyJob created path returns early when no integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $otherAccount->id]);

    $mock = $this->mock(InventorySyncService::class);
    $mock->shouldNotReceive('pushAllInventoryForColorway');

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'created');
    $job->handle();
});

test('SyncColorwayCatalogToShopifyJob created path returns early when catalog sync disabled', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    $mock = $this->mock(InventorySyncService::class);
    $mock->shouldNotReceive('pushAllInventoryForColorway');

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'created');
    $job->handle();
});

test('SyncColorwayCatalogToShopifyJob calls archiveProduct when colorway status is retired', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Retired,
    ]);
    $productGid = 'gid://shopify/Product/1';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid, 'status' => 'ARCHIVED'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($body, 'productUpdate') && str_contains($body, 'ARCHIVED');
    });

    Http::assertNotSent(function ($request) {
        return str_contains($request->body(), 'productCreate');
    });
});

test('SyncColorwayCatalogToShopifyJob calls updateProduct when colorway status is active', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Active,
    ]);
    $productGid = 'gid://shopify/Product/2';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($body, 'productUpdate') && str_contains($body, 'ACTIVE');
    });
});

test('SyncColorwayCatalogToShopifyJob calls updateProduct when colorway status is idea', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Idea,
    ]);
    $productGid = 'gid://shopify/Product/3';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($body, 'productUpdate') && str_contains($body, 'DRAFT');
    });
});

test('SyncColorwayCatalogToShopifyJob calls updateProduct with ACTIVE when re-activating from retired', function () {
    Queue::fake();

    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Active,
    ]);
    $productGid = 'gid://shopify/Product/4';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'https://test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($body, 'productUpdate') && str_contains($body, 'ACTIVE');
    });

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $colorway->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('product_update');
});

test('SyncColorwayCatalogToShopifyJob updated path skips when no product mapping exists', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Active,
    ]);

    Http::fake();

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    Http::assertNothingSent();
});

test('SyncColorwayCatalogToShopifyJob logs success on archive', function () {
    Queue::fake();

    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Retired,
    ]);
    $productGid = 'gid://shopify/Product/5';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'https://test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid, 'status' => 'ARCHIVED'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $colorway->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('product_archive');
});

test('SyncColorwayCatalogToShopifyJob logs error when Shopify API fails', function () {
    Queue::fake();

    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'status' => ColorwayStatus::Active,
    ]);
    $productGid = 'gid://shopify/Product/6';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => $productGid,
    ]);

    Http::fake([
        'https://test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => null,
                    'userErrors' => [['field' => 'id', 'message' => 'Product not found']],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $colorway->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Error);
    expect($log->metadata['operation'])->toBe('product_update');
});

test('SyncColorwayCatalogToShopifyJob created path logs error when Shopify API fails', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    $mock = $this->mock(InventorySyncService::class);
    $mock->shouldReceive('pushAllInventoryForColorway')
        ->once()
        ->andThrow(new ShopifyApiException('HTTP request returned status code 401: {"errors":"[API] Invalid API key or access token"}'));

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'created');
    $job->handle();

    expect($this->integration->fresh()->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $colorway->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Error);
    expect($log->metadata['operation'])->toBe('product_create');
});
