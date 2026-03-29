<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
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

test('createProduct sends correct Colorway to Product field mapping', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Mountain Mist',
        'description' => '<p>Hand-dyed yarn</p>',
        'status' => ColorwayStatus::Active,
        'technique' => Technique::Variegated,
        'colors' => ['blue', 'green'],
    ]);
    Base::factory()->create([
        'account_id' => $this->account->id,
        'descriptor' => 'Fingering',
        'retail_price' => 28.00,
        'status' => BaseStatus::Active,
    ]);

    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
        if (isset($vars['product'])) {
            $captured = $vars;

            return [
                'data' => [
                    'productCreate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/1']]]],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        return [
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/1']],
                    'userErrors' => [],
                ],
            ],
        ];
    });

    $service = new ShopifySyncService($mockClient);
    $service->createProduct($colorway, $this->integration);

    expect($captured)->toHaveKey('product');
    $product = $captured['product'];
    expect($product['title'])->toBe('Mountain Mist');
    expect($product['descriptionHtml'])->toBe('<p>Hand-dyed yarn</p>');
    expect($product['productType'])->toBe('Yarn');
    expect($product['vendor'])->toBe('Fibermade');
    expect($product['tags'])->toContain('blue');
    expect($product['tags'])->toContain('green');
    expect($product['tags'])->toContain('variegated');
});

test('createProduct maps ColorwayStatus to Shopify status', function () {
    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
        if (isset($vars['product'])) {
            $captured = $vars;

            return [
                'data' => [
                    'productCreate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/1']]]],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        return [
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/1']],
                    'userErrors' => [],
                ],
            ],
        ];
    });

    $service = new ShopifySyncService($mockClient);

    $colorwayActive = Colorway::factory()->create(['account_id' => $this->account->id, 'status' => ColorwayStatus::Active]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'status' => BaseStatus::Active]);
    $service->createProduct($colorwayActive, $this->integration);
    expect($captured['product']['status'])->toBe('ACTIVE');

    $colorwayRetired = Colorway::factory()->create(['account_id' => $this->account->id, 'status' => ColorwayStatus::Retired]);
    $service->createProduct($colorwayRetired, $this->integration);
    expect($captured['product']['status'])->toBe('ARCHIVED');

    $colorwayIdea = Colorway::factory()->create(['account_id' => $this->account->id, 'status' => ColorwayStatus::Idea]);
    $service->createProduct($colorwayIdea, $this->integration);
    expect($captured['product']['status'])->toBe('DRAFT');
});

test('createProduct adds per_pan metafield when per_pan greater than zero', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'per_pan' => 4,
    ]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'status' => BaseStatus::Active]);

    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
        if (isset($vars['product'])) {
            $captured = $vars;

            return [
                'data' => [
                    'productCreate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/1']]]],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        return [
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/1']],
                    'userErrors' => [],
                ],
            ],
        ];
    });

    $service = new ShopifySyncService($mockClient);
    $service->createProduct($colorway, $this->integration);

    expect($captured['product']['metafields'])->toBeArray();
    expect($captured['product']['metafields'])->toHaveCount(1);
    expect($captured['product']['metafields'][0])->toMatchArray([
        'namespace' => 'fibermade',
        'key' => 'per_pan',
        'value' => '4',
        'type' => 'number_integer',
    ]);
});

test('createProduct omits per_pan metafield when per_pan is zero', function () {
    $colorway = Colorway::factory()->create([
        'account_id' => $this->account->id,
        'per_pan' => 0,
    ]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'status' => BaseStatus::Active]);

    $captured = [];
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
        if (isset($vars['product'])) {
            $captured = $vars;

            return [
                'data' => [
                    'productCreate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/1']]]],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        return [
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/1']],
                    'userErrors' => [],
                ],
            ],
        ];
    });

    $service = new ShopifySyncService($mockClient);
    $service->createProduct($colorway, $this->integration);

    expect($captured['product'])->not->toHaveKey('metafields');
});

test('syncImages orders media with is_primary first', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway->media()->create([
        'file_path' => 'colorways/second.jpg',
        'file_name' => 'second.jpg',
        'is_primary' => false,
    ]);
    $colorway->media()->create([
        'file_path' => 'colorways/first.jpg',
        'file_name' => 'first.jpg',
        'is_primary' => true,
    ]);

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->with(Mockery::type('string'), Mockery::on(function ($vars) {
            return isset($vars['id']);
        }))
        ->andReturn(['data' => ['product' => ['media' => ['edges' => []]]]]);
    $mockClient->shouldReceive('request')
        ->with(Mockery::type('string'), Mockery::on(function ($vars) {
            return isset($vars['product']) && isset($vars['media']);
        }))
        ->andReturn(['data' => ['productUpdate' => ['product' => ['id' => 'gid://shopify/Product/1', 'media' => ['edges' => []]], 'userErrors' => []]]]);

    $service = new ShopifySyncService($mockClient);
    $service->syncImages($colorway->fresh(), 'gid://shopify/Product/1');

    $mediaOrder = $colorway->fresh()->media()->orderByDesc('is_primary')->orderBy('id')->pluck('file_path')->all();
    expect($mediaOrder[0])->toBe('colorways/first.jpg');
    expect($mediaOrder[1])->toBe('colorways/second.jpg');
});

test('createProduct creates all variants when account has multiple bases', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'status' => BaseStatus::Active]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'DK', 'retail_price' => 30.00, 'status' => BaseStatus::Active]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Worsted', 'retail_price' => 32.00, 'status' => BaseStatus::Active]);

    $bulkCreateVars = null;
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);

    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$bulkCreateVars) {
        if (str_contains($query, 'productCreate(')) {
            return [
                'data' => [
                    'productCreate' => [
                        'product' => [
                            'id' => 'gid://shopify/Product/1',
                            'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/10']]]],
                        ],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        if (str_contains($query, 'productVariantsBulkUpdate')) {
            return [
                'data' => [
                    'productVariantsBulkUpdate' => [
                        'productVariants' => [['id' => 'gid://shopify/ProductVariant/10']],
                        'userErrors' => [],
                    ],
                ],
            ];
        }

        // productVariantsBulkCreate (bulkCreateVariantsForProduct)
        $bulkCreateVars = $vars;

        return [
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [
                        ['id' => 'gid://shopify/ProductVariant/20'],
                        ['id' => 'gid://shopify/ProductVariant/30'],
                    ],
                    'userErrors' => [],
                ],
            ],
        ];
    });

    $service = new ShopifySyncService($mockClient);
    $result = $service->createProduct($colorway, $this->integration);

    expect($result['variant_ids'])->toHaveCount(3);
    expect($result['variant_ids'][0])->toBe('gid://shopify/ProductVariant/10');
    expect($result['variant_ids'][1])->toBe('gid://shopify/ProductVariant/20');
    expect($result['variant_ids'][2])->toBe('gid://shopify/ProductVariant/30');

    expect($bulkCreateVars['variants'])->toHaveCount(2);
    expect($bulkCreateVars['variants'][0]['optionValues'])->toEqual([['optionName' => 'Base', 'name' => 'DK']]);
    expect($bulkCreateVars['variants'][0]['price'])->toBe('30.00');
    expect($bulkCreateVars['variants'][1]['optionValues'])->toEqual([['optionName' => 'Base', 'name' => 'Worsted']]);
    expect($bulkCreateVars['variants'][1]['price'])->toBe('32.00');
});

test('archiveProduct sends productUpdate mutation with status ARCHIVED', function () {
    $productGid = 'gid://shopify/Product/99';
    $captured = [];

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return isset($vars['input']['id']) && isset($vars['input']['status']);
        }))
        ->andReturn([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => $productGid, 'status' => 'ARCHIVED'],
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->archiveProduct($productGid);

    expect($captured['input']['id'])->toBe($productGid);
    expect($captured['input']['status'])->toBe('ARCHIVED');
});

test('archiveProduct throws ShopifyApiException on userErrors', function () {
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productUpdate' => [
                    'product' => null,
                    'userErrors' => [['field' => 'id', 'message' => 'Product not found']],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);

    expect(fn () => $service->archiveProduct('gid://shopify/Product/99'))
        ->toThrow(ShopifyApiException::class, 'Product not found');
});

test('deleteProduct sends productDelete mutation with product ID', function () {
    $productGid = 'gid://shopify/Product/99';
    $captured = [];

    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(Mockery::type('string'), Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return isset($vars['input']['id']);
        }))
        ->andReturn([
            'data' => [
                'productDelete' => [
                    'deletedProductId' => $productGid,
                    'userErrors' => [],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);
    $service->deleteProduct($productGid);

    expect($captured['input']['id'])->toBe($productGid);
});

test('deleteProduct throws ShopifyApiException on userErrors', function () {
    $mockClient = Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn([
            'data' => [
                'productDelete' => [
                    'deletedProductId' => null,
                    'userErrors' => [['field' => 'id', 'message' => 'Product not found']],
                ],
            ],
        ]);

    $service = new ShopifySyncService($mockClient);

    expect(fn () => $service->deleteProduct('gid://shopify/Product/99'))
        ->toThrow(ShopifyApiException::class, 'Product not found');
});
