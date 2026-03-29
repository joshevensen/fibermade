<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\Inventory;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifySyncService;

afterEach(fn () => Mockery::close());

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

// ─── createVariantsBulk ───────────────────────────────────────────────────────

test('createVariantsBulk sends correct GraphQL with all variant inputs', function () {
    $base1 = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $base2 = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'DK', 'retail_price' => 32.00, 'cost' => 12.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv1 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base1->id, 'quantity' => 5]);
    $inv2 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base2->id, 'quantity' => 10]);

    $capturedCreate = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    // First call: ensureBaseOptionExists (getProductOptions)
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getProductOptions')), Mockery::any())
        ->andReturn([
            'data' => ['product' => ['options' => [['name' => 'Base']]]],
        ]);

    // Second call: getDefaultLocationId
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getLocations')))
        ->andReturn([
            'data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]],
        ]);

    // Third call: productVariantsBulkCreate
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$capturedCreate) {
            $capturedCreate = $vars;

            return isset($vars['productId']) && isset($vars['variants']);
        }))
        ->andReturn([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [
                        ['id' => 'gid://shopify/ProductVariant/100'],
                        ['id' => 'gid://shopify/ProductVariant/200'],
                    ],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->createVariantsBulk('gid://shopify/Product/1', [
        ['inventory' => $inv1, 'base' => $base1, 'quantity' => 5],
        ['inventory' => $inv2, 'base' => $base2, 'quantity' => 10],
    ]);

    expect($capturedCreate['productId'])->toBe('gid://shopify/Product/1');
    expect($capturedCreate['variants'])->toHaveCount(2);

    expect($capturedCreate['variants'][0]['optionValues'])->toEqual([['optionName' => 'Base', 'name' => 'Fingering']]);
    expect($capturedCreate['variants'][0]['price'])->toBe('28.00');
    expect($capturedCreate['variants'][0]['inventoryItem']['cost'])->toBe('10.00');
    expect($capturedCreate['variants'][0]['inventoryItem']['tracked'])->toBeTrue();
    expect($capturedCreate['variants'][0]['inventoryQuantities'][0]['locationId'])->toBe('gid://shopify/Location/1');
    expect($capturedCreate['variants'][0]['inventoryQuantities'][0]['availableQuantity'])->toBe(5);

    expect($capturedCreate['variants'][1]['optionValues'])->toEqual([['optionName' => 'Base', 'name' => 'DK']]);
    expect($capturedCreate['variants'][1]['price'])->toBe('32.00');
    expect($capturedCreate['variants'][1]['inventoryQuantities'][0]['availableQuantity'])->toBe(10);
});

test('createVariantsBulk returns correct inventory_id to variant_gid map', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway1 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv1 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway1->id, 'base_id' => $base->id]);
    $inv2 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway2->id, 'base_id' => $base->id]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getProductOptions')), Mockery::any())
        ->andReturn([
            'data' => ['product' => ['options' => [['name' => 'Base']]]],
        ]);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getLocations')))
        ->andReturn([
            'data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]],
        ]);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [
                        ['id' => 'gid://shopify/ProductVariant/101'],
                        ['id' => 'gid://shopify/ProductVariant/202'],
                    ],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $map = $service->createVariantsBulk('gid://shopify/Product/1', [
        ['inventory' => $inv1, 'base' => $base, 'quantity' => 0],
        ['inventory' => $inv2, 'base' => $base, 'quantity' => 0],
    ]);

    expect($map)->toHaveCount(2);
    expect($map[$inv1->id])->toBe('gid://shopify/ProductVariant/101');
    expect($map[$inv2->id])->toBe('gid://shopify/ProductVariant/202');
});

test('createVariantsBulk throws ShopifyApiException on userErrors', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getProductOptions')), Mockery::any())
        ->andReturn([
            'data' => ['product' => ['options' => [['name' => 'Base']]]],
        ]);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getLocations')))
        ->andReturn([
            'data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]],
        ]);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [],
                    'userErrors' => [['field' => 'variants', 'message' => 'Invalid option value']],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);

    expect(fn () => $service->createVariantsBulk('gid://shopify/Product/1', [
        ['inventory' => $inv, 'base' => $base, 'quantity' => 0],
    ]))->toThrow(ShopifyApiException::class, 'Invalid option value');
});

test('createVariantsBulk calls productOptionsCreate when Base option is missing', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id, 'quantity' => 5]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    // getProductOptions — no "Base" option (product was pulled from Shopify)
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getProductOptions')), Mockery::any())
        ->andReturn([
            'data' => ['product' => ['options' => [['name' => 'Title']]]],
        ]);

    // productOptionsCreate
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'productOptionsCreate')), Mockery::any())
        ->andReturn([
            'data' => ['productOptionsCreate' => ['userErrors' => [], 'product' => ['id' => 'gid://shopify/Product/1']]],
        ]);

    // getDefaultLocationId
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::on(fn ($q) => str_contains($q, 'getLocations')))
        ->andReturn([
            'data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]],
        ]);

    // productVariantsBulkCreate
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/100']],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $map = $service->createVariantsBulk('gid://shopify/Product/1', [
        ['inventory' => $inv, 'base' => $base, 'quantity' => 5],
    ]);

    expect($map[$inv->id])->toBe('gid://shopify/ProductVariant/100');
});

// ─── updateVariantsBulk ───────────────────────────────────────────────────────

test('updateVariantsBulk sends correct GraphQL for all variants', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00]);

    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return isset($vars['productId']) && isset($vars['variants']);
        }))
        ->andReturn([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/100']],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->updateVariantsBulk('gid://shopify/Product/1', [
        ['variant_gid' => 'gid://shopify/ProductVariant/100', 'base' => $base],
    ]);

    expect($captured['productId'])->toBe('gid://shopify/Product/1');
    expect($captured['variants'])->toHaveCount(1);
    expect($captured['variants'][0]['id'])->toBe('gid://shopify/ProductVariant/100');
    expect($captured['variants'][0]['optionValues'])->toEqual([['optionName' => 'Base', 'name' => 'Fingering']]);
    expect($captured['variants'][0]['price'])->toBe('28.00');
});

test('updateVariantsBulk sends multiple variants in one call', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00]);

    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return true;
        }))
        ->andReturn([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->updateVariantsBulk('gid://shopify/Product/1', [
        ['variant_gid' => 'gid://shopify/ProductVariant/100', 'base' => $base],
        ['variant_gid' => 'gid://shopify/ProductVariant/200', 'base' => $base],
        ['variant_gid' => 'gid://shopify/ProductVariant/300', 'base' => $base],
    ]);

    expect($captured['variants'])->toHaveCount(3);
    expect($captured['variants'][0]['id'])->toBe('gid://shopify/ProductVariant/100');
    expect($captured['variants'][1]['id'])->toBe('gid://shopify/ProductVariant/200');
    expect($captured['variants'][2]['id'])->toBe('gid://shopify/ProductVariant/300');
});

test('updateVariantsBulk throws ShopifyApiException on userErrors', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [],
                    'userErrors' => [['field' => 'id', 'message' => 'Variant not found']],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);

    expect(fn () => $service->updateVariantsBulk('gid://shopify/Product/1', [
        ['variant_gid' => 'gid://shopify/ProductVariant/999', 'base' => $base],
    ]))->toThrow(ShopifyApiException::class, 'Variant not found');
});

// ─── deleteVariantsBulk ───────────────────────────────────────────────────────

test('deleteVariantsBulk sends correct GraphQL for all variant IDs', function () {
    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return isset($vars['productId']) && isset($vars['variantsIds']);
        }))
        ->andReturn([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->deleteVariantsBulk('gid://shopify/Product/1', [
        'gid://shopify/ProductVariant/100',
        'gid://shopify/ProductVariant/200',
        'gid://shopify/ProductVariant/300',
    ]);

    expect($captured['productId'])->toBe('gid://shopify/Product/1');
    expect($captured['variantsIds'])->toEqual([
        'gid://shopify/ProductVariant/100',
        'gid://shopify/ProductVariant/200',
        'gid://shopify/ProductVariant/300',
    ]);
});

test('deleteVariantsBulk throws ShopifyApiException on userErrors', function () {
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => null,
                    'userErrors' => [['field' => 'variantsIds', 'message' => 'Variant not found']],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);

    expect(fn () => $service->deleteVariantsBulk('gid://shopify/Product/1', ['gid://shopify/ProductVariant/999']))
        ->toThrow(ShopifyApiException::class, 'Variant not found');
});

// ─── syncImages (updated to use productUpdate) ────────────────────────────────

test('syncImages uses productUpdate with media parameter instead of productCreateMedia', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id, 'name' => 'Ocean Blue']);
    $colorway->media()->create([
        'file_path' => 'colorways/ocean.jpg',
        'file_name' => 'ocean.jpg',
        'is_primary' => true,
    ]);

    $capturedUpdate = null;
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    // getProductMedia query (returns empty — no existing media to delete)
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(fn ($v) => isset($v['id'])))
        ->andReturn(['data' => ['product' => ['media' => ['edges' => []]]]]);

    // productUpdate with media
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$capturedUpdate) {
            $capturedUpdate = $vars;

            return isset($vars['product']) && isset($vars['media']);
        }))
        ->andReturn([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => 'gid://shopify/Product/1', 'media' => ['edges' => []]],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->syncImages($colorway->fresh(), 'gid://shopify/Product/1');

    expect($capturedUpdate)->not->toBeNull();
    expect($capturedUpdate['product']['id'])->toBe('gid://shopify/Product/1');
    expect($capturedUpdate['media'])->toHaveCount(1);
    expect($capturedUpdate['media'][0]['mediaContentType'])->toBe('IMAGE');
    expect($capturedUpdate['media'][0]['alt'])->toBe('Ocean Blue');
    expect($capturedUpdate['media'][0])->toHaveKey('originalSource');
});

test('syncImages sends all images in a single productUpdate call', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id, 'name' => 'Multicolor']);
    $colorway->media()->create(['file_path' => 'colorways/a.jpg', 'file_name' => 'a.jpg', 'is_primary' => true]);
    $colorway->media()->create(['file_path' => 'colorways/b.jpg', 'file_name' => 'b.jpg', 'is_primary' => false]);
    $colorway->media()->create(['file_path' => 'colorways/c.jpg', 'file_name' => 'c.jpg', 'is_primary' => false]);

    $capturedMedia = null;
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(fn ($v) => isset($v['id'])))
        ->andReturn(['data' => ['product' => ['media' => ['edges' => []]]]]);

    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$capturedMedia) {
            if (isset($vars['media'])) {
                $capturedMedia = $vars['media'];
            }

            return true;
        }))
        ->andReturn([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => 'gid://shopify/Product/1', 'media' => ['edges' => []]],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->syncImages($colorway->fresh(), 'gid://shopify/Product/1');

    // All 3 images in one call, not 3 separate productCreateMedia calls
    expect($capturedMedia)->toHaveCount(3);
});

test('syncImages skips productUpdate call when colorway has no media', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    // Only getProductMedia query — no productUpdate
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn(['data' => ['product' => ['media' => ['edges' => []]]]]);

    $service = new ShopifySyncService($mockClient);
    $service->syncImages($colorway->fresh(), 'gid://shopify/Product/1');

    // Mockery will fail if any unexpected calls are made
});
