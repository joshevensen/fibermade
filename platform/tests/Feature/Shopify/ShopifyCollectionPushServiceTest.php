<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyCollectionPushService;
use App\Services\Shopify\ShopifyGraphqlClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $this->client = new ShopifyGraphqlClient('test.myshopify.com', 'test-token');
    $this->service = new ShopifyCollectionPushService($this->client);
});

// ─── createCollection ─────────────────────────────────────────────────────────

test('createCollection sends correct collectionCreate mutation', function () {
    $collection = Collection::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Fall Collection',
        'description' => 'Autumn yarns',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionCreate' => [
                    'collection' => [
                        'id' => 'gid://shopify/Collection/111',
                        'title' => 'Fall Collection',
                        'handle' => 'fall-collection',
                    ],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $gid = $this->service->createCollection($collection, $this->integration);

    expect($gid)->toBe('gid://shopify/Collection/111');
    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionCreate'));
});

test('createCollection creates ExternalIdentifier after success', function () {
    $collection = Collection::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Fall Collection',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionCreate' => [
                    'collection' => [
                        'id' => 'gid://shopify/Collection/222',
                        'title' => 'Fall Collection',
                        'handle' => 'fall-collection',
                    ],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $this->service->createCollection($collection, $this->integration);

    $identifier = ExternalIdentifier::where('integration_id', $this->integration->id)
        ->where('identifiable_type', Collection::class)
        ->where('identifiable_id', $collection->id)
        ->where('external_type', 'shopify_collection')
        ->first();

    expect($identifier)->not->toBeNull();
    expect($identifier->external_id)->toBe('gid://shopify/Collection/222');
    expect($identifier->data['handle'])->toBe('fall-collection');
});

test('createCollection throws ShopifyApiException on userErrors', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionCreate' => [
                    'collection' => null,
                    'userErrors' => [['field' => 'title', 'message' => 'Title is too long']],
                ],
            ],
        ]),
    ]);

    expect(fn () => $this->service->createCollection($collection, $this->integration))
        ->toThrow(ShopifyApiException::class, 'Title is too long');
});

// ─── updateCollection ─────────────────────────────────────────────────────────

test('updateCollection sends correct collectionUpdate mutation', function () {
    $collection = Collection::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Winter Collection',
        'description' => 'Cold weather yarns',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionUpdate' => [
                    'collection' => ['id' => 'gid://shopify/Collection/333'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $this->service->updateCollection($collection, 'gid://shopify/Collection/333');

    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionUpdate'));
});

test('updateCollection throws ShopifyApiException on userErrors', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionUpdate' => [
                    'collection' => null,
                    'userErrors' => [['field' => 'id', 'message' => 'Collection not found']],
                ],
            ],
        ]),
    ]);

    expect(fn () => $this->service->updateCollection($collection, 'gid://shopify/Collection/999'))
        ->toThrow(ShopifyApiException::class, 'Collection not found');
});

// ─── syncCollectionProducts ───────────────────────────────────────────────────

test('syncCollectionProducts resolves colorway GIDs and calls collectionAddProductsV2', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $collection->colorways()->attach($colorway->id);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/500',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionAddProductsV2' => [
                    'job' => ['id' => 'gid://shopify/Job/1', 'done' => false],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $this->service->syncCollectionProducts(
        $collection,
        'gid://shopify/Collection/100',
        $this->integration
    );

    Http::assertSent(fn ($r) => str_contains($r->body(), 'collectionAddProductsV2'));
});

test('syncCollectionProducts calls removeProductsFromCollection for removed colorways', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    $removedColorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $removedColorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/999',
    ]);

    Http::fake([
        'test.myshopify.com/admin/api/2025-01/collects.json*' => Http::response([
            'collects' => [
                ['id' => 101, 'collection_id' => 42, 'product_id' => 999],
            ],
        ]),
        'test.myshopify.com/admin/api/2025-01/collects/101.json' => Http::response(null, 200),
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionAddProductsV2' => [
                    'job' => ['id' => 'gid://shopify/Job/1', 'done' => false],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $this->service->syncCollectionProducts(
        $collection,
        'gid://shopify/Collection/42',
        $this->integration,
        [$removedColorway->id]
    );

    Http::assertSent(fn ($r) => str_contains($r->url(), 'collects/101.json') && $r->method() === 'DELETE');
});

test('syncCollectionProducts skips colorways with no Shopify mapping and logs warning', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $collection->colorways()->attach($colorway->id);
    // No ExternalIdentifier for this colorway

    Http::fake();

    $this->service->syncCollectionProducts(
        $collection,
        'gid://shopify/Collection/100',
        $this->integration
    );

    Http::assertNothingSent();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('status', IntegrationLogStatus::Warning)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->metadata['operation'])->toBe('collection_products_sync');
    expect($log->metadata['colorway_id'])->toBe($colorway->id);
});

test('syncCollectionProducts returns early when no product GIDs and no removals', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    // No colorways attached

    Http::fake();

    $this->service->syncCollectionProducts(
        $collection,
        'gid://shopify/Collection/100',
        $this->integration
    );

    Http::assertNothingSent();
});

// ─── removeProductsFromCollection ────────────────────────────────────────────

test('removeProductsFromCollection fetches collects and deletes matching ones via REST', function () {
    Http::fake([
        'test.myshopify.com/admin/api/2025-01/collects.json*' => Http::response([
            'collects' => [
                ['id' => 201, 'collection_id' => 55, 'product_id' => 1001],
                ['id' => 202, 'collection_id' => 55, 'product_id' => 1002],
            ],
        ]),
        'test.myshopify.com/admin/api/2025-01/collects/201.json' => Http::response(null, 200),
    ]);

    $this->service->removeProductsFromCollection(
        'gid://shopify/Collection/55',
        ['gid://shopify/Product/1001']
    );

    Http::assertSent(fn ($r) => str_contains($r->url(), 'collects/201.json') && $r->method() === 'DELETE');
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'collects/202.json'));
});
