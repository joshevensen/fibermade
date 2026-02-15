<?php

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifySyncService;

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
    ]);

    $captured = [];
    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with(\Mockery::type('string'), \Mockery::on(function ($vars) use (&$captured) {
            $captured = $vars;

            return isset($vars['product']);
        }))
        ->andReturn([
            'data' => [
                'productCreate' => [
                    'product' => [
                        'id' => 'gid://shopify/Product/1',
                        'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/1']]]],
                    ],
                    'userErrors' => [],
                ],
            ],
        ]);

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
    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
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
    });

    $service = new ShopifySyncService($mockClient);

    $colorwayActive = Colorway::factory()->create(['account_id' => $this->account->id, 'status' => ColorwayStatus::Active]);
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering']);
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
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering']);

    $captured = [];
    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
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
    Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering']);

    $captured = [];
    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')->andReturnUsing(function ($query, $vars) use (&$captured) {
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

    $mockClient = \Mockery::mock(ShopifyGraphqlClient::class);
    $mockClient->shouldReceive('request')
        ->with(\Mockery::type('string'), \Mockery::on(function ($vars) {
            return isset($vars['id']);
        }))
        ->andReturn(['data' => ['product' => ['media' => ['edges' => []]]]]);
    $mockClient->shouldReceive('request')
        ->with(\Mockery::type('string'), \Mockery::on(function ($vars) {
            return isset($vars['productId']) && isset($vars['media']);
        }))
        ->andReturn(['data' => ['productCreateMedia' => ['mediaUserErrors' => []]]]);

    $service = new ShopifySyncService($mockClient);
    $service->syncImages($colorway->fresh(), 'gid://shopify/Product/1');

    $mediaOrder = $colorway->fresh()->media()->orderByDesc('is_primary')->orderBy('id')->pluck('file_path')->all();
    expect($mediaOrder[0])->toBe('colorways/first.jpg');
    expect($mediaOrder[1])->toBe('colorways/second.jpg');
});
