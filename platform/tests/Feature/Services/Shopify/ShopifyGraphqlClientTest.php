<?php

use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifyRateLimitException;
use App\Services\Shopify\ShopifyTokenExpiredException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->shop = 'test.myshopify.com';
    $this->token = 'shpat_test';
    $this->client = new ShopifyGraphqlClient($this->shop, $this->token);
    $this->graphqlUrl = "https://{$this->shop}/admin/api/2025-01/graphql.json";
});

// ─── getProducts ─────────────────────────────────────────────────────────────

it('getProducts returns normalized product list with pagination info', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'products' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 'gid://shopify/Product/1',
                                'title' => 'Ocean Mist',
                                'descriptionHtml' => '<p>Beautiful</p>',
                                'status' => 'ACTIVE',
                                'handle' => 'ocean-mist',
                                'featuredImage' => ['url' => 'https://cdn.shopify.com/image.jpg'],
                                'variants' => [
                                    'edges' => [
                                        [
                                            'node' => [
                                                'id' => 'gid://shopify/ProductVariant/10',
                                                'title' => 'Fingering',
                                                'price' => '28.00',
                                                'sku' => 'OM-FNG',
                                                'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/100'],
                                                'inventoryQuantity' => 5,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pageInfo' => [
                        'hasNextPage' => false,
                        'endCursor' => null,
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getProducts();

    expect($result['hasNextPage'])->toBeFalse();
    expect($result['nextCursor'])->toBeNull();
    expect($result['products'])->toHaveCount(1);

    $product = $result['products'][0];
    expect($product['gid'])->toBe('gid://shopify/Product/1');
    expect($product['title'])->toBe('Ocean Mist');
    expect($product['descriptionHtml'])->toBe('<p>Beautiful</p>');
    expect($product['status'])->toBe('ACTIVE');
    expect($product['handle'])->toBe('ocean-mist');
    expect($product['featuredImage'])->toBe(['url' => 'https://cdn.shopify.com/image.jpg']);

    $variant = $product['variants'][0];
    expect($variant['gid'])->toBe('gid://shopify/ProductVariant/10');
    expect($variant['title'])->toBe('Fingering');
    expect($variant['price'])->toBe('28.00');
    expect($variant['sku'])->toBe('OM-FNG');
    expect($variant['inventoryItem'])->toBe(['gid' => 'gid://shopify/InventoryItem/100']);
    expect($variant['inventoryQuantity'])->toBe(5);
});

it('getProducts passes cursor and first=50 in request variables', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'products' => [
                    'edges' => [],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
            ],
        ], 200),
    ]);

    $this->client->getProducts('cursor_abc');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $body['variables']['first'] === 50
            && $body['variables']['after'] === 'cursor_abc';
    });
});

it('getProducts returns nextCursor and hasNextPage when more pages exist', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'products' => [
                    'edges' => [],
                    'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor_xyz'],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getProducts();

    expect($result['hasNextPage'])->toBeTrue();
    expect($result['nextCursor'])->toBe('cursor_xyz');
});

it('getProducts normalizes null featuredImage to null', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'products' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 'gid://shopify/Product/2',
                                'title' => 'No Image',
                                'descriptionHtml' => null,
                                'status' => 'DRAFT',
                                'handle' => 'no-image',
                                'featuredImage' => null,
                                'variants' => ['edges' => []],
                            ],
                        ],
                    ],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getProducts();
    expect($result['products'][0]['featuredImage'])->toBeNull();
});

// ─── getProduct ───────────────────────────────────────────────────────────────

it('getProduct returns a single normalized product', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'product' => [
                    'id' => 'gid://shopify/Product/42',
                    'title' => 'Single Product',
                    'descriptionHtml' => '<p>Desc</p>',
                    'status' => 'ACTIVE',
                    'handle' => 'single-product',
                    'featuredImage' => null,
                    'variants' => ['edges' => []],
                ],
            ],
        ], 200),
    ]);

    $product = $this->client->getProduct('gid://shopify/Product/42');

    expect($product['gid'])->toBe('gid://shopify/Product/42');
    expect($product['title'])->toBe('Single Product');
    expect($product['status'])->toBe('ACTIVE');
});

it('getProduct passes the GID as id variable', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'product' => [
                    'id' => 'gid://shopify/Product/99',
                    'title' => 'Test',
                    'descriptionHtml' => null,
                    'status' => 'ACTIVE',
                    'handle' => 'test',
                    'featuredImage' => null,
                    'variants' => ['edges' => []],
                ],
            ],
        ], 200),
    ]);

    $this->client->getProduct('gid://shopify/Product/99');

    Http::assertSent(function (Request $request) {
        return $request->data()['variables']['id'] === 'gid://shopify/Product/99';
    });
});

// ─── getCollections ───────────────────────────────────────────────────────────

it('getCollections returns normalized collection list with pagination info', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'collections' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 'gid://shopify/Collection/5',
                                'title' => 'Autumn Colors',
                                'descriptionHtml' => '<p>Fall</p>',
                                'handle' => 'autumn-colors',
                            ],
                        ],
                    ],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getCollections();

    expect($result['hasNextPage'])->toBeFalse();
    expect($result['nextCursor'])->toBeNull();
    expect($result['collections'])->toHaveCount(1);

    $collection = $result['collections'][0];
    expect($collection['gid'])->toBe('gid://shopify/Collection/5');
    expect($collection['title'])->toBe('Autumn Colors');
    expect($collection['descriptionHtml'])->toBe('<p>Fall</p>');
    expect($collection['handle'])->toBe('autumn-colors');
});

it('getCollections passes cursor in variables when provided', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'collections' => [
                    'edges' => [],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
            ],
        ], 200),
    ]);

    $this->client->getCollections('col_cursor_123');

    Http::assertSent(function (Request $request) {
        $vars = $request->data()['variables'];

        return $vars['first'] === 50 && $vars['after'] === 'col_cursor_123';
    });
});

it('getCollections returns nextCursor when more pages exist', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'collections' => [
                    'edges' => [],
                    'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next_col_cursor'],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getCollections();

    expect($result['hasNextPage'])->toBeTrue();
    expect($result['nextCursor'])->toBe('next_col_cursor');
});

// ─── getCollectionProducts ────────────────────────────────────────────────────

it('getCollectionProducts returns product GIDs for a collection', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'collection' => [
                    'products' => [
                        'edges' => [
                            ['node' => ['id' => 'gid://shopify/Product/10']],
                            ['node' => ['id' => 'gid://shopify/Product/20']],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getCollectionProducts('gid://shopify/Collection/5');

    expect($result['products'])->toBe([
        ['gid' => 'gid://shopify/Product/10'],
        ['gid' => 'gid://shopify/Product/20'],
    ]);
    expect($result['hasNextPage'])->toBeFalse();
    expect($result['nextCursor'])->toBeNull();
});

it('getCollectionProducts passes collectionGid, first=50, and cursor in variables', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'collection' => [
                    'products' => [
                        'edges' => [],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->client->getCollectionProducts('gid://shopify/Collection/5', 'prod_cursor');

    Http::assertSent(function (Request $request) {
        $vars = $request->data()['variables'];

        return $vars['id'] === 'gid://shopify/Collection/5'
            && $vars['first'] === 50
            && $vars['after'] === 'prod_cursor';
    });
});

// ─── getVariantInventory ──────────────────────────────────────────────────────

it('getVariantInventory returns on-hand quantity from inventory levels', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'productVariant' => [
                    'id' => 'gid://shopify/ProductVariant/10',
                    'inventoryItem' => [
                        'id' => 'gid://shopify/InventoryItem/100',
                        'inventoryLevels' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'quantities' => [
                                            ['name' => 'on_hand', 'quantity' => 12],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getVariantInventory('gid://shopify/ProductVariant/10');

    expect($result['variantGid'])->toBe('gid://shopify/ProductVariant/10');
    expect($result['onHandQuantity'])->toBe(12);
    expect($result['inventoryItemGid'])->toBe('gid://shopify/InventoryItem/100');
});

it('getVariantInventory returns zero when no inventory levels are present', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'productVariant' => [
                    'id' => 'gid://shopify/ProductVariant/11',
                    'inventoryItem' => [
                        'id' => 'gid://shopify/InventoryItem/101',
                        'inventoryLevels' => ['edges' => []],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getVariantInventory('gid://shopify/ProductVariant/11');

    expect($result['onHandQuantity'])->toBe(0);
    expect($result['inventoryItemGid'])->toBe('gid://shopify/InventoryItem/101');
});

it('getVariantInventory returns zero and null itemGid when inventoryItem is absent', function () {
    Http::fake([
        $this->graphqlUrl => Http::response([
            'data' => [
                'productVariant' => [
                    'id' => 'gid://shopify/ProductVariant/12',
                    'inventoryItem' => null,
                ],
            ],
        ], 200),
    ]);

    $result = $this->client->getVariantInventory('gid://shopify/ProductVariant/12');

    expect($result['onHandQuantity'])->toBe(0);
    expect($result['inventoryItemGid'])->toBeNull();
});

// ─── Rate limit retry ─────────────────────────────────────────────────────────

it('retries on 429 and succeeds on subsequent attempt', function () {
    $callCount = 0;

    Http::fake(function () use (&$callCount) {
        $callCount++;
        if ($callCount === 1) {
            return Http::response(null, 429, ['Retry-After' => '0']);
        }

        return Http::response([
            'data' => [
                'products' => [
                    'edges' => [],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
            ],
        ], 200);
    });

    $result = $this->client->getProducts();

    expect($callCount)->toBe(2);
    expect($result['products'])->toBeEmpty();
});

it('throws ShopifyApiException after exhausting retries on 429', function () {
    Http::fake([
        $this->graphqlUrl => Http::response(null, 429, ['Retry-After' => '0']),
    ]);

    expect(fn () => $this->client->getProducts())->toThrow(ShopifyRateLimitException::class);
});

// ─── Cursor pagination round-trip ─────────────────────────────────────────────

it('can paginate through multiple pages of products', function () {
    $page1Response = [
        'data' => [
            'products' => [
                'edges' => [
                    [
                        'node' => [
                            'id' => 'gid://shopify/Product/1',
                            'title' => 'Product 1',
                            'descriptionHtml' => null,
                            'status' => 'ACTIVE',
                            'handle' => 'product-1',
                            'featuredImage' => null,
                            'variants' => ['edges' => []],
                        ],
                    ],
                ],
                'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'page2_cursor'],
            ],
        ],
    ];

    $page2Response = [
        'data' => [
            'products' => [
                'edges' => [
                    [
                        'node' => [
                            'id' => 'gid://shopify/Product/2',
                            'title' => 'Product 2',
                            'descriptionHtml' => null,
                            'status' => 'ACTIVE',
                            'handle' => 'product-2',
                            'featuredImage' => null,
                            'variants' => ['edges' => []],
                        ],
                    ],
                ],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ],
        ],
    ];

    Http::fake([
        $this->graphqlUrl => Http::sequence()
            ->push($page1Response, 200)
            ->push($page2Response, 200),
    ]);

    $allProducts = [];

    $page1 = $this->client->getProducts();
    $allProducts = array_merge($allProducts, $page1['products']);

    expect($page1['hasNextPage'])->toBeTrue();
    expect($page1['nextCursor'])->toBe('page2_cursor');

    $page2 = $this->client->getProducts($page1['nextCursor']);
    $allProducts = array_merge($allProducts, $page2['products']);

    expect($page2['hasNextPage'])->toBeFalse();
    expect($page2['nextCursor'])->toBeNull();
    expect($allProducts)->toHaveCount(2);
    expect($allProducts[0]['gid'])->toBe('gid://shopify/Product/1');
    expect($allProducts[1]['gid'])->toBe('gid://shopify/Product/2');
});

// ─── request variables serialization ─────────────────────────────────────────

it('omits variables key from request body when no variables are passed', function () {
    Http::fake([
        $this->graphqlUrl => Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]]], 200),
    ]);

    $this->client->request('query { locations(first: 1) { edges { node { id } } } }');

    Http::assertSent(function (Request $request) {
        return ! array_key_exists('variables', $request->data());
    });
});

it('includes variables key in request body when variables are passed', function () {
    Http::fake([
        $this->graphqlUrl => Http::response(['data' => ['productVariant' => ['inventoryItem' => ['id' => 'gid://shopify/InventoryItem/1']]]], 200),
    ]);

    $this->client->request('query getVariant($id: ID!) { productVariant(id: $id) { inventoryItem { id } } }', ['id' => 'gid://shopify/ProductVariant/1']);

    Http::assertSent(function (Request $request) {
        return $request->data()['variables'] === ['id' => 'gid://shopify/ProductVariant/1'];
    });
});

// ─── Token refresh on 401 ─────────────────────────────────────────────────────

it('refreshes the access token and retries when 401 is received and a refresh token is present', function () {
    $refreshUrl = "https://{$this->shop}/admin/oauth/access_token";
    $callCount = 0;

    Http::fake(function (Request $request) use (&$callCount, $refreshUrl) {
        $callCount++;
        if ($request->url() === $refreshUrl) {
            return Http::response(['access_token' => 'new_token', 'scope' => 'write_products'], 200);
        }
        if ($callCount === 1) {
            return Http::response(['errors' => 'Invalid API key'], 401);
        }

        return Http::response([
            'data' => ['products' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]],
        ], 200);
    });

    $refreshedToken = null;
    $client = new ShopifyGraphqlClient(
        shop: $this->shop,
        accessToken: $this->token,
        refreshToken: 'shprt_original_refresh',
        onTokenRefreshed: function (string $newAccessToken) use (&$refreshedToken): void {
            $refreshedToken = $newAccessToken;
        },
    );

    $result = $client->getProducts();

    expect($result['products'])->toBeEmpty();
    expect($refreshedToken)->toBe('new_token');
});

it('calls onTokenRefreshed with new refresh token when Shopify rotates it', function () {
    $refreshUrl = "https://{$this->shop}/admin/oauth/access_token";
    $callCount = 0;

    Http::fake(function (Request $request) use (&$callCount, $refreshUrl) {
        $callCount++;
        if ($request->url() === $refreshUrl) {
            return Http::response([
                'access_token' => 'new_access',
                'refresh_token' => 'shprt_rotated',
                'scope' => 'write_products',
            ], 200);
        }
        if ($callCount === 1) {
            return Http::response(['errors' => 'Invalid API key'], 401);
        }

        return Http::response([
            'data' => ['products' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]],
        ], 200);
    });

    $capturedAccess = null;
    $capturedRefresh = null;
    $client = new ShopifyGraphqlClient(
        shop: $this->shop,
        accessToken: $this->token,
        refreshToken: 'shprt_original',
        onTokenRefreshed: function (string $newAccessToken, ?string $newRefreshToken) use (&$capturedAccess, &$capturedRefresh): void {
            $capturedAccess = $newAccessToken;
            $capturedRefresh = $newRefreshToken;
        },
    );

    $client->getProducts();

    expect($capturedAccess)->toBe('new_access');
    expect($capturedRefresh)->toBe('shprt_rotated');
});

it('throws ShopifyTokenExpiredException when 401 received and no refresh token is set', function () {
    Http::fake([
        $this->graphqlUrl => Http::response(['errors' => 'Invalid API key'], 401),
    ]);

    expect(fn () => $this->client->getProducts())->toThrow(ShopifyTokenExpiredException::class);
});

it('throws ShopifyTokenExpiredException when refresh token is rejected by Shopify', function () {
    $refreshUrl = "https://{$this->shop}/admin/oauth/access_token";

    Http::fake([
        $this->graphqlUrl => Http::response(['errors' => 'Invalid API key'], 401),
        $refreshUrl => Http::response(['error' => 'invalid_grant'], 401),
    ]);

    $client = new ShopifyGraphqlClient(
        shop: $this->shop,
        accessToken: $this->token,
        refreshToken: 'shprt_expired',
    );

    expect(fn () => $client->getProducts())->toThrow(ShopifyTokenExpiredException::class);
});

it('does not retry token refresh more than once per request call', function () {
    $refreshCallCount = 0;
    $refreshUrl = "https://{$this->shop}/admin/oauth/access_token";

    Http::fake(function (Request $request) use (&$refreshCallCount, $refreshUrl) {
        if ($request->url() === $refreshUrl) {
            $refreshCallCount++;

            return Http::response(['access_token' => 'new_token', 'scope' => 'write_products'], 200);
        }

        // Always return 401 so we can verify refresh is only attempted once
        return Http::response(['errors' => 'Unauthorized'], 401);
    });

    $client = new ShopifyGraphqlClient(
        shop: $this->shop,
        accessToken: $this->token,
        refreshToken: 'shprt_valid',
    );

    expect(fn () => $client->getProducts())->toThrow(ShopifyTokenExpiredException::class);
    expect($refreshCallCount)->toBe(1);
});
