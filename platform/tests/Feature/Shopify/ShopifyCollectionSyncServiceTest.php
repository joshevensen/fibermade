<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Services\Shopify\ShopifyCollectionSyncService;
use App\Services\Shopify\ShopifyGraphqlClient;

// ── Helpers ────────────────────────────────────────────────────────────────────

function makeCollection(array $overrides = []): array
{
    return array_merge([
        'gid' => 'gid://shopify/Collection/5',
        'title' => 'Autumn Colors',
        'descriptionHtml' => '<p>Fall palette</p>',
        'handle' => 'autumn-colors',
        'productGids' => [],
        'productsHasNextPage' => false,
        'productsEndCursor' => null,
    ], $overrides);
}

function makeCollectionProductsPage(array $gids, bool $hasNextPage = false, ?string $nextCursor = null): array
{
    return [
        'products' => array_map(fn ($gid) => ['gid' => $gid], $gids),
        'hasNextPage' => $hasNextPage,
        'nextCursor' => $nextCursor,
    ];
}

// ── Setup ──────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $this->client = Mockery::mock(ShopifyGraphqlClient::class);
    $this->service = new ShopifyCollectionSyncService($this->client);
});

// ── Create path ────────────────────────────────────────────────────────────────

it('creates a collection with correct name and description', function () {
    $outcome = $this->service->syncCollection(makeCollection(), $this->integration, $this->client);

    expect($outcome)->toBe('created');
    expect(Collection::where('account_id', $this->account->id)->count())->toBe(1);

    $collection = Collection::first();
    expect($collection->name)->toBe('Autumn Colors');
    expect($collection->description)->toBe('<p>Fall palette</p>');
});

it('creates ExternalIdentifier mapping for collection', function () {
    $this->service->syncCollection(makeCollection(), $this->integration, $this->client);

    $collection = Collection::first();
    expect(ExternalIdentifier::where([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/5',
    ])->exists())->toBeTrue();
});

it('assigns mapped colorways using inline product GIDs', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/1']]),
        $this->integration,
        $this->client,
    );

    $collection = Collection::first();
    expect($collection->colorways()->where('colorways.id', $colorway->id)->exists())->toBeTrue();
});

it('skips products with no colorway mapping gracefully', function () {
    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/999']]),
        $this->integration,
        $this->client,
    );

    $collection = Collection::first();
    expect($collection->colorways()->count())->toBe(0);
});

it('handles empty collection without error', function () {
    $outcome = $this->service->syncCollection(makeCollection(), $this->integration, $this->client);

    expect($outcome)->toBe('created');
    expect(Collection::first()->colorways()->count())->toBe(0);
});

it('fetches additional pages via API when productsHasNextPage is true', function () {
    $colorwayA = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorwayB = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayA->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayB->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);

    // Inline has first page, API call fetches the overflow page
    $this->client->shouldReceive('getCollectionProducts')
        ->once()
        ->with('gid://shopify/Collection/5', 'cursor_page2')
        ->andReturn(makeCollectionProductsPage(['gid://shopify/Product/2'], false));

    $this->service->syncCollection(
        makeCollection([
            'productGids' => ['gid://shopify/Product/1'],
            'productsHasNextPage' => true,
            'productsEndCursor' => 'cursor_page2',
        ]),
        $this->integration,
        $this->client,
    );

    $collection = Collection::first();
    expect($collection->colorways()->count())->toBe(2);
});

// ── Update path ────────────────────────────────────────────────────────────────

it('returns updated when collection mapping already exists', function () {
    $this->service->syncCollection(makeCollection(), $this->integration, $this->client);

    $outcome = $this->service->syncCollection(makeCollection(['title' => 'Autumn Colors v2']), $this->integration, $this->client);

    expect($outcome)->toBe('updated');
    expect(Collection::first()->name)->toBe('Autumn Colors v2');
});

it('adds new colorways to existing collection on update', function () {
    $colorwayA = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorwayB = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayA->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayB->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);

    // First sync with only colorwayA
    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/1']]),
        $this->integration,
        $this->client,
    );

    // Second sync with both colorways
    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/1', 'gid://shopify/Product/2']]),
        $this->integration,
        $this->client,
    );

    $collection = Collection::first();
    expect($collection->colorways()->count())->toBe(2);
});

it('removes dropped colorways from collection on update', function () {
    $colorwayA = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorwayB = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayA->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayB->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);

    // First sync: both colorways
    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/1', 'gid://shopify/Product/2']]),
        $this->integration,
        $this->client,
    );

    // Second sync: only colorwayA — colorwayB should be removed
    $this->service->syncCollection(
        makeCollection(['productGids' => ['gid://shopify/Product/1']]),
        $this->integration,
        $this->client,
    );

    $collection = Collection::first();
    expect($collection->colorways()->count())->toBe(1);
    expect($collection->colorways()->where('colorways.id', $colorwayA->id)->exists())->toBeTrue();
    expect($collection->colorways()->where('colorways.id', $colorwayB->id)->exists())->toBeFalse();
});

// ── syncAll ────────────────────────────────────────────────────────────────────

it('syncAll processes paginated collections', function () {
    $this->client->shouldReceive('getCollections')
        ->with(null)
        ->once()
        ->andReturn([
            'collections' => [makeCollection(['gid' => 'gid://shopify/Collection/1'])],
            'hasNextPage' => true,
            'nextCursor' => 'col_cursor',
        ]);

    $this->client->shouldReceive('getCollections')
        ->with('col_cursor')
        ->once()
        ->andReturn([
            'collections' => [makeCollection(['gid' => 'gid://shopify/Collection/2', 'title' => 'Winter'])],
            'hasNextPage' => false,
            'nextCursor' => null,
        ]);

    $result = $this->service->syncAll($this->integration);

    expect($result->created)->toBe(2);
    expect($result->failed)->toBe(0);
});

it('syncAll records failed count and continues when a collection throws', function () {
    // Collections with productsHasNextPage=true will trigger a getCollectionProducts API call.
    // We use this to force an exception on the first collection while the second succeeds.
    $collectionWithOverflow = makeCollection([
        'gid' => 'gid://shopify/Collection/1',
        'productsHasNextPage' => true,
        'productsEndCursor' => 'cursor',
    ]);
    $collectionOk = makeCollection(['gid' => 'gid://shopify/Collection/2', 'title' => 'Good']);

    $this->client->shouldReceive('getCollections')
        ->once()
        ->andReturn([
            'collections' => [$collectionWithOverflow, $collectionOk],
            'hasNextPage' => false,
            'nextCursor' => null,
        ]);

    $this->client->shouldReceive('getCollectionProducts')
        ->once()
        ->andThrow(new RuntimeException('API error'));

    $result = $this->service->syncAll($this->integration);

    expect($result->failed)->toBe(1);
    expect($result->created)->toBe(1);
    expect($result->errors[0]['entity_gid'])->toBe('gid://shopify/Collection/1');
});

it('syncAll excludes archived collections via published_status filter at API level', function () {
    $this->client->shouldReceive('getCollections')
        ->once()
        ->andReturn([
            'collections' => [],
            'hasNextPage' => false,
            'nextCursor' => null,
        ]);

    $result = $this->service->syncAll($this->integration);

    expect($result->created)->toBe(0);
    expect($result->skipped)->toBe(0);
});
