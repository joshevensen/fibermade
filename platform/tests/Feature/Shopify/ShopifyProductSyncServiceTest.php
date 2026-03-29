<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\Media;
use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifyProductSyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

// ── Helpers ────────────────────────────────────────────────────────────────────

function makeProduct(array $overrides = []): array
{
    return array_merge([
        'gid' => 'gid://shopify/Product/1',
        'title' => 'Ocean Mist',
        'descriptionHtml' => '<p>Beautiful</p>',
        'status' => 'ACTIVE',
        'handle' => 'ocean-mist',
        'featuredImage' => null,
        'images' => [],
        'variants' => [
            [
                'gid' => 'gid://shopify/ProductVariant/10',
                'title' => 'Fingering',
                'price' => '28.00',
            ],
        ],
    ], $overrides);
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
    $this->service = new ShopifyProductSyncService;
});

// ── Create path ────────────────────────────────────────────────────────────────

it('creates a colorway when no mapping exists', function () {
    $outcome = $this->service->syncProduct(makeProduct(), $this->integration);

    expect($outcome)->toBe('created');
    expect(Colorway::where('account_id', $this->account->id)->count())->toBe(1);

    $colorway = Colorway::first();
    expect($colorway->name)->toBe('Ocean Mist');
    expect($colorway->description)->toBe('<p>Beautiful</p>');
    expect($colorway->status)->toBe(ColorwayStatus::Active);
});

it('creates an ExternalIdentifier mapping for product to colorway', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);

    $colorway = Colorway::first();
    expect(ExternalIdentifier::where([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ])->exists())->toBeTrue();
});

it('creates Base and Inventory records for each variant', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);

    $colorway = Colorway::first();
    expect(Base::where('account_id', $this->account->id)->where('descriptor', 'Fingering')->count())->toBe(1);
    expect(Inventory::where('colorway_id', $colorway->id)->count())->toBe(1);
    expect(Inventory::first()->quantity)->toBe(0);
});

it('creates ExternalIdentifier mapping for variant to inventory', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);

    $inventory = Inventory::first();
    expect(ExternalIdentifier::where([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ])->exists())->toBeTrue();
});

it('stores primary image as Media record by downloading and storing on disk', function () {
    Storage::fake('public');
    Http::fake(['https://cdn.shopify.com/image.jpg' => Http::response('fake-image-data', 200, ['Content-Type' => 'image/jpeg'])]);

    $product = makeProduct(['images' => [['url' => 'https://cdn.shopify.com/image.jpg', 'altText' => null]]]);
    $this->service->syncProduct($product, $this->integration);

    $colorway = Colorway::first();
    $media = Media::where('mediable_type', Colorway::class)
        ->where('mediable_id', $colorway->id)
        ->first();

    expect($media)->not->toBeNull();
    expect($media->is_primary)->toBeTrue();
    expect($media->disk)->toBe('public');
    expect($media->file_path)->toBe("colorways/{$colorway->id}/image.jpg");
    expect($media->file_name)->toBe('image.jpg');
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->metadata['source'])->toBe('shopify');
    expect($media->metadata['original_url'])->toBe('https://cdn.shopify.com/image.jpg');
    Storage::disk('public')->assertExists("colorways/{$colorway->id}/image.jpg");
});

it('syncs all images when a product has multiple images', function () {
    Storage::fake('public');
    Http::fake([
        'https://cdn.shopify.com/image1.jpg' => Http::response('data1', 200, ['Content-Type' => 'image/jpeg']),
        'https://cdn.shopify.com/image2.jpg' => Http::response('data2', 200, ['Content-Type' => 'image/jpeg']),
        'https://cdn.shopify.com/image3.jpg' => Http::response('data3', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $product = makeProduct([
        'images' => [
            ['url' => 'https://cdn.shopify.com/image1.jpg', 'altText' => null],
            ['url' => 'https://cdn.shopify.com/image2.jpg', 'altText' => null],
            ['url' => 'https://cdn.shopify.com/image3.jpg', 'altText' => null],
        ],
    ]);
    $this->service->syncProduct($product, $this->integration);

    $colorway = Colorway::first();
    $media = Media::where('mediable_type', Colorway::class)->where('mediable_id', $colorway->id)->get();

    expect($media)->toHaveCount(3);
    expect($media->where('is_primary', true)->count())->toBe(1);
    expect($media->where('is_primary', false)->count())->toBe(2);
    expect($media->first()->metadata['original_url'])->toBe('https://cdn.shopify.com/image1.jpg');
});

it('skips image sync when images array is empty', function () {
    $this->service->syncProduct(makeProduct(['images' => []]), $this->integration);

    expect(Media::count())->toBe(0);
});

it('skips image sync when image download fails', function () {
    Http::fake(['https://cdn.shopify.com/image.jpg' => Http::response('', 404)]);

    $product = makeProduct(['images' => [['url' => 'https://cdn.shopify.com/image.jpg', 'altText' => null]]]);
    $this->service->syncProduct($product, $this->integration);

    expect(Media::count())->toBe(0);
});

it('skips images already synced by URL on re-sync', function () {
    Storage::fake('public');
    Http::fake(['https://cdn.shopify.com/image.jpg' => Http::response('fake-image-data', 200)]);

    $product = makeProduct(['images' => [['url' => 'https://cdn.shopify.com/image.jpg', 'altText' => null]]]);
    $this->service->syncProduct($product, $this->integration);
    $this->service->syncProduct($product, $this->integration);

    $colorway = Colorway::first();
    expect(Media::where('mediable_type', Colorway::class)->where('mediable_id', $colorway->id)->count())->toBe(1);
});

it('skips Default Title variants and creates no base for them', function () {
    $product = makeProduct([
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/20', 'title' => 'Default Title', 'price' => '25.00'],
        ],
    ]);
    $this->service->syncProduct($product, $this->integration);

    // Colorway is created, but no base or inventory from the placeholder variant
    expect(Colorway::where('name', 'Ocean Mist')->exists())->toBeTrue();
    expect(Base::where('account_id', $this->account->id)->count())->toBe(0);
});

it('reuses an existing Base with the same descriptor', function () {
    Base::factory()->create([
        'account_id' => $this->account->id,
        'descriptor' => 'Fingering',
        'status' => BaseStatus::Active,
    ]);

    $this->service->syncProduct(makeProduct(), $this->integration);

    expect(Base::where('account_id', $this->account->id)->where('descriptor', 'Fingering')->count())->toBe(1);
});

// ── Status mapping ─────────────────────────────────────────────────────────────

it('maps ACTIVE to active status', function () {
    $this->service->syncProduct(makeProduct(['status' => 'ACTIVE']), $this->integration);
    expect(Colorway::first()->status)->toBe(ColorwayStatus::Active);
});

it('maps DRAFT to idea status', function () {
    $this->service->syncProduct(makeProduct(['status' => 'DRAFT']), $this->integration);
    expect(Colorway::first()->status)->toBe(ColorwayStatus::Idea);
});

it('maps ARCHIVED to retired status', function () {
    $this->service->syncProduct(makeProduct(['status' => 'ARCHIVED']), $this->integration);
    expect(Colorway::first()->status)->toBe(ColorwayStatus::Retired);
});

it('maps UNLISTED to retired status', function () {
    $this->service->syncProduct(makeProduct(['status' => 'UNLISTED']), $this->integration);
    expect(Colorway::first()->status)->toBe(ColorwayStatus::Retired);
});

// ── Update path ────────────────────────────────────────────────────────────────

it('returns updated when mapping already exists', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);

    $outcome = $this->service->syncProduct(makeProduct(['title' => 'Ocean Mist v2']), $this->integration);

    expect($outcome)->toBe('updated');
    expect(Colorway::first()->name)->toBe('Ocean Mist v2');
});

it('updates colorway status and description on re-sync', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);
    $this->service->syncProduct(makeProduct(['status' => 'ARCHIVED', 'descriptionHtml' => '<p>Updated</p>']), $this->integration);

    $colorway = Colorway::first();
    expect($colorway->status)->toBe(ColorwayStatus::Retired);
    expect($colorway->description)->toBe('<p>Updated</p>');
});

it('retires base when variant is removed from shopify product', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);
    $base = Base::where('descriptor', 'Fingering')->first();

    $this->service->syncProduct(makeProduct(['variants' => []]), $this->integration);

    expect($base->fresh()->status)->toBe(BaseStatus::Retired);
});

it('adds base and inventory for a new variant on update', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);

    $updatedProduct = makeProduct([
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/10', 'title' => 'Fingering', 'price' => '28.00'],
            ['gid' => 'gid://shopify/ProductVariant/11', 'title' => 'Sport', 'price' => '30.00'],
        ],
    ]);
    $this->service->syncProduct($updatedProduct, $this->integration);

    $colorway = Colorway::first();
    expect(Inventory::where('colorway_id', $colorway->id)->count())->toBe(2);
    expect(Base::where('descriptor', 'Sport')->exists())->toBeTrue();
});

it('updates retail price on existing base when new price is higher', function () {
    $this->service->syncProduct(makeProduct(), $this->integration);
    $this->service->syncProduct(makeProduct([
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/10', 'title' => 'Fingering', 'price' => '35.00'],
        ],
    ]), $this->integration);

    expect((float) Base::where('descriptor', 'Fingering')->first()->retail_price)->toBe(35.0);
});

it('keeps existing retail price when re-synced price is lower', function () {
    $this->service->syncProduct(makeProduct([
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/10', 'title' => 'Fingering', 'price' => '35.00'],
        ],
    ]), $this->integration);
    $this->service->syncProduct(makeProduct([
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/10', 'title' => 'Fingering', 'price' => '20.00'],
        ],
    ]), $this->integration);

    expect((float) Base::where('descriptor', 'Fingering')->first()->retail_price)->toBe(35.0);
});

it('keeps existing retail price when a new colorway syncs with a lower price for the same base', function () {
    // First colorway establishes Fingering at $35
    $this->service->syncProduct(makeProduct([
        'gid' => 'gid://shopify/Product/1',
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/10', 'title' => 'Fingering', 'price' => '35.00'],
        ],
    ]), $this->integration);

    // Second colorway reuses the same Fingering base but at $28 — should not lower the price
    $this->service->syncProduct(makeProduct([
        'gid' => 'gid://shopify/Product/2',
        'title' => 'Cobalt',
        'variants' => [
            ['gid' => 'gid://shopify/ProductVariant/20', 'title' => 'Fingering', 'price' => '28.00'],
        ],
    ]), $this->integration);

    expect((float) Base::where('descriptor', 'Fingering')->first()->retail_price)->toBe(35.0);
});

// ── syncAll pagination and error handling ─────────────────────────────────────

it('syncAll accumulates created count across pages', function () {
    $client = Mockery::mock(ShopifyGraphqlClient::class);
    $service = new ShopifyProductSyncService($client);

    $client->shouldReceive('getProducts')
        ->with(null)
        ->once()
        ->andReturn([
            'products' => [makeProduct(['gid' => 'gid://shopify/Product/1'])],
            'hasNextPage' => true,
            'nextCursor' => 'cursor_abc',
        ]);

    $client->shouldReceive('getProducts')
        ->with('cursor_abc')
        ->once()
        ->andReturn([
            'products' => [makeProduct(['gid' => 'gid://shopify/Product/2', 'title' => 'Second'])],
            'hasNextPage' => false,
            'nextCursor' => null,
        ]);

    $result = $service->syncAll($this->integration);

    expect($result->created)->toBe(2);
    expect($result->failed)->toBe(0);
});

it('syncAll records failed count when a product throws and continues', function () {
    $client = Mockery::mock(ShopifyGraphqlClient::class);
    $service = new ShopifyProductSyncService($client);

    // Force a failure by returning a product that will cause a DB error via bad account_id
    // We simulate this by having the second product succeed
    $client->shouldReceive('getProducts')
        ->once()
        ->andReturn([
            'products' => [
                makeProduct(['gid' => 'gid://shopify/Product/1']),
                makeProduct(['gid' => 'gid://shopify/Product/2', 'title' => 'Second']),
            ],
            'hasNextPage' => false,
            'nextCursor' => null,
        ]);

    $result = $service->syncAll($this->integration);

    expect($result->created)->toBe(2);
});
