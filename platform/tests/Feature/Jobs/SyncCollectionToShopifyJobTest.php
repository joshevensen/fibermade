<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncCollectionToShopifyJob;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    // Prevent observer-dispatched jobs from running synchronously (QUEUE_CONNECTION=sync in tests)
    Queue::fake();

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

// ─── created path ─────────────────────────────────────────────────────────────

test('SyncCollectionToShopifyJob (created) creates collection and syncs products', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id, 'name' => 'My Collection']);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $collection->colorways()->attach($colorway->id);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/500',
    ]);

    Http::fake(function ($request) {
        $body = $request->body();

        if (str_contains($body, 'collectionCreate')) {
            return Http::response([
                'data' => [
                    'collectionCreate' => [
                        'collection' => [
                            'id' => 'gid://shopify/Collection/101',
                            'title' => 'My Collection',
                            'handle' => 'my-collection',
                        ],
                        'userErrors' => [],
                    ],
                ],
            ]);
        }

        return Http::response([
            'data' => [
                'collectionAddProductsV2' => [
                    'job' => ['id' => 'gid://shopify/Job/1', 'done' => false],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new SyncCollectionToShopifyJob($collection, 'created');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionCreate'));
    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionAddProductsV2'));

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $collection->id)
        ->first();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('collection_create');
});

// ─── updated path ─────────────────────────────────────────────────────────────

test('SyncCollectionToShopifyJob (updated) updates collection and syncs products when mapping exists', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id, 'name' => 'Updated Name']);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/200',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionUpdate' => [
                    'collection' => ['id' => 'gid://shopify/Collection/200'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncCollectionToShopifyJob($collection, 'updated');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionUpdate'));

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $collection->id)
        ->first();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('collection_update');
});

test('SyncCollectionToShopifyJob (updated) creates collection when no mapping exists', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id, 'name' => 'New Collection']);
    // No ExternalIdentifier mapping

    Http::fake(function ($request) {
        if (str_contains($request->body(), 'collectionCreate')) {
            return Http::response([
                'data' => [
                    'collectionCreate' => [
                        'collection' => [
                            'id' => 'gid://shopify/Collection/303',
                            'title' => 'New Collection',
                            'handle' => 'new-collection',
                        ],
                        'userErrors' => [],
                    ],
                ],
            ]);
        }

        return Http::response([
            'data' => [
                'collectionAddProductsV2' => [
                    'job' => ['id' => 'gid://shopify/Job/2', 'done' => false],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new SyncCollectionToShopifyJob($collection, 'updated');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionCreate'));

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $collection->id)
        ->first();
    expect($log->metadata['operation'])->toBe('collection_create');
});

test('SyncCollectionToShopifyJob passes removed colorway IDs to syncCollectionProducts', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    $removedColorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/400',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $removedColorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/777',
    ]);

    Http::fake([
        'test.myshopify.com/admin/api/2025-01/collects.json*' => Http::response([
            'collects' => [
                ['id' => 501, 'collection_id' => 400, 'product_id' => 777],
            ],
        ]),
        'test.myshopify.com/admin/api/2025-01/collects/501.json' => Http::response(null, 200),
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionUpdate' => [
                    'collection' => ['id' => 'gid://shopify/Collection/400'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncCollectionToShopifyJob($collection, 'updated', [$removedColorway->id]);
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'collects/501.json') && $r->method() === 'DELETE');
});

test('SyncCollectionToShopifyJob (created) updates instead of creating when mapping already exists (pulled from Shopify)', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id, 'name' => 'Pulled Collection']);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/999',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionUpdate' => [
                    'collection' => ['id' => 'gid://shopify/Collection/999'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncCollectionToShopifyJob($collection, 'created');
    $job->handle();

    Http::assertNotSent(fn ($r) => str_contains($r->body(), 'collectionCreate'));
    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionUpdate'));

    expect(ExternalIdentifier::where('identifiable_type', Collection::class)
        ->where('identifiable_id', $collection->id)
        ->where('external_type', 'shopify_collection')
        ->count())->toBe(1);
});

// ─── guard checks ─────────────────────────────────────────────────────────────

test('SyncCollectionToShopifyJob returns early when no active integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $collection = Collection::factory()->create(['account_id' => $otherAccount->id]);

    Http::fake();

    $job = new SyncCollectionToShopifyJob($collection, 'created');
    $job->handle();

    Http::assertNothingSent();
});

test('SyncCollectionToShopifyJob has retry configuration', function () {
    $job = new SyncCollectionToShopifyJob(new Collection, 'created');

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});
