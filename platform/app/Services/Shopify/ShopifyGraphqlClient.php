<?php

namespace App\Services\Shopify;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Low-level HTTP client for Shopify Admin GraphQL API.
 *
 * Handles retries with exponential backoff and rate limit (429) handling.
 */
class ShopifyGraphqlClient
{
    private const API_VERSION = '2025-01';

    public function __construct(
        private readonly string $shop,
        private readonly string $accessToken
    ) {}

    /**
     * Execute a GraphQL query or mutation.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws ShopifyApiException
     */
    public function request(string $query, array $variables = []): array
    {
        $maxRetries = config('services.shopify.max_retries', 3);
        $initialBackoffMs = config('services.shopify.initial_backoff_ms', 1000);
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                $response = $this->client()
                    ->asJson()
                    ->post($this->graphqlUrl(), [
                        'query' => $query,
                        'variables' => $variables,
                    ]);

                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 2);
                    Log::warning('Shopify API rate limit (429)', [
                        'shop' => $this->shop,
                        'retry_after_seconds' => $retryAfter,
                        'attempt' => $attempt + 1,
                    ]);
                    $lastException = new ShopifyRateLimitException("Rate limited. Retry after {$retryAfter}s.");
                    sleep($retryAfter);
                    $attempt++;

                    continue;
                }

                $response->throw();
                $body = $response->json();

                if (isset($body['errors']) && ! empty($body['errors'])) {
                    $message = collect($body['errors'])->pluck('message')->implode('; ');
                    throw new ShopifyApiException($message, $body['errors']);
                }

                return $body;
            } catch (RequestException $e) {
                $lastException = new ShopifyApiException(
                    $e->getMessage(),
                    $e->response?->json() ?? []
                );

                if ($e->response?->status() === 429) {
                    $retryAfter = (int) $e->response->header('Retry-After', 2);
                    Log::warning('Shopify API rate limit (429)', [
                        'shop' => $this->shop,
                        'retry_after_seconds' => $retryAfter,
                        'attempt' => $attempt + 1,
                    ]);
                    sleep($retryAfter);
                    $attempt++;

                    continue;
                }

                $status = $e->response?->status();
                if ($status >= 500 || $status === 429) {
                    $backoffMs = $initialBackoffMs * (2 ** $attempt);
                    usleep($backoffMs * 1000);
                    $attempt++;

                    continue;
                }

                throw $lastException;
            }
        }

        throw $lastException ?? new ShopifyApiException('Unknown error');
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->timeout(30);
    }

    private function graphqlUrl(): string
    {
        $shop = preg_replace('#^https?://#', '', rtrim($this->shop, '/'));

        return "https://{$shop}/admin/api/".self::API_VERSION.'/graphql.json';
    }

    /**
     * Resolve variant GID from inventory item GID (for inventory_levels/update webhook).
     *
     * @return string|null Variant GID or null if not found
     */
    public function getVariantIdFromInventoryItemId(string $inventoryItemGid): ?string
    {
        $query = <<<'GRAPHQL'
            query getVariantFromInventoryItem($id: ID!) {
                inventoryItem(id: $id) {
                    variant { id }
                }
            }
        GRAPHQL;

        $result = $this->request($query, ['id' => $inventoryItemGid]);

        return $result['data']['inventoryItem']['variant']['id'] ?? null;
    }

    /**
     * Fetch a page of products with cursor-based pagination.
     *
     * @return array{products: list<array<string, mixed>>, nextCursor: string|null, hasNextPage: bool}
     */
    public function getProducts(?string $cursor = null): array
    {
        $query = <<<'GRAPHQL'
            query GetProducts($first: Int!, $after: String) {
                products(first: $first, after: $after) {
                    edges {
                        node {
                            id
                            title
                            descriptionHtml
                            status
                            handle
                            featuredImage { url }
                            variants(first: 100) {
                                edges {
                                    node {
                                        id
                                        title
                                        price
                                        sku
                                        inventoryItem { id }
                                        inventoryQuantity
                                    }
                                }
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        GRAPHQL;

        $variables = ['first' => 50];

        if ($cursor !== null) {
            $variables['after'] = $cursor;
        }

        $result = $this->request($query, $variables);
        $connection = $result['data']['products'];

        return [
            'products' => array_map(
                fn (array $edge) => $this->normalizeProduct($edge['node']),
                $connection['edges']
            ),
            'nextCursor' => $connection['pageInfo']['endCursor'] ?? null,
            'hasNextPage' => $connection['pageInfo']['hasNextPage'] ?? false,
        ];
    }

    /**
     * Fetch a single product by GID.
     *
     * @return array<string, mixed>
     */
    public function getProduct(string $gid): array
    {
        $query = <<<'GRAPHQL'
            query GetProduct($id: ID!) {
                product(id: $id) {
                    id
                    title
                    descriptionHtml
                    status
                    handle
                    featuredImage { url }
                    variants(first: 100) {
                        edges {
                            node {
                                id
                                title
                                price
                                sku
                                inventoryItem { id }
                                inventoryQuantity
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $result = $this->request($query, ['id' => $gid]);

        return $this->normalizeProduct($result['data']['product']);
    }

    /**
     * Fetch a page of collections with cursor-based pagination.
     *
     * @return array{collections: list<array<string, mixed>>, nextCursor: string|null, hasNextPage: bool}
     */
    public function getCollections(?string $cursor = null): array
    {
        $query = <<<'GRAPHQL'
            query GetCollections($first: Int!, $after: String) {
                collections(first: $first, after: $after) {
                    edges {
                        node {
                            id
                            title
                            descriptionHtml
                            handle
                        }
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        GRAPHQL;

        $variables = ['first' => 50];

        if ($cursor !== null) {
            $variables['after'] = $cursor;
        }

        $result = $this->request($query, $variables);
        $connection = $result['data']['collections'];

        return [
            'collections' => array_map(
                fn (array $edge) => $this->normalizeCollection($edge['node']),
                $connection['edges']
            ),
            'nextCursor' => $connection['pageInfo']['endCursor'] ?? null,
            'hasNextPage' => $connection['pageInfo']['hasNextPage'] ?? false,
        ];
    }

    /**
     * Fetch a page of product GIDs belonging to a collection.
     *
     * @return array{products: list<array{gid: string}>, nextCursor: string|null, hasNextPage: bool}
     */
    public function getCollectionProducts(string $collectionGid, ?string $cursor = null): array
    {
        $query = <<<'GRAPHQL'
            query GetCollectionProducts($id: ID!, $first: Int!, $after: String) {
                collection(id: $id) {
                    products(first: $first, after: $after) {
                        edges {
                            node {
                                id
                            }
                        }
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'id' => $collectionGid,
            'first' => 50,
        ];

        if ($cursor !== null) {
            $variables['after'] = $cursor;
        }

        $result = $this->request($query, $variables);
        $connection = $result['data']['collection']['products'];

        return [
            'products' => array_map(
                fn (array $edge) => ['gid' => $edge['node']['id']],
                $connection['edges']
            ),
            'nextCursor' => $connection['pageInfo']['endCursor'] ?? null,
            'hasNextPage' => $connection['pageInfo']['hasNextPage'] ?? false,
        ];
    }

    /**
     * Fetch current inventory quantity for a variant.
     *
     * @return array{variantGid: string, inventoryQuantity: int, inventoryItemGid: string|null}
     */
    public function getVariantInventory(string $variantGid): array
    {
        $query = <<<'GRAPHQL'
            query GetVariantInventory($id: ID!) {
                productVariant(id: $id) {
                    id
                    inventoryQuantity
                    inventoryItem { id }
                }
            }
        GRAPHQL;

        $result = $this->request($query, ['id' => $variantGid]);
        $variant = $result['data']['productVariant'];

        return [
            'variantGid' => $variant['id'],
            'inventoryQuantity' => $variant['inventoryQuantity'] ?? 0,
            'inventoryItemGid' => $variant['inventoryItem']['id'] ?? null,
        ];
    }

    /**
     * Normalize a raw Shopify product node into a consistent shape.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function normalizeProduct(array $node): array
    {
        $variantEdges = $node['variants']['edges'] ?? [];

        return [
            'gid' => $node['id'],
            'title' => $node['title'],
            'descriptionHtml' => $node['descriptionHtml'] ?? null,
            'status' => $node['status'],
            'handle' => $node['handle'] ?? null,
            'featuredImage' => isset($node['featuredImage']['url'])
                ? ['url' => $node['featuredImage']['url']]
                : null,
            'variants' => array_map(
                fn (array $edge) => $this->normalizeVariant($edge['node']),
                $variantEdges
            ),
        ];
    }

    /**
     * Normalize a raw Shopify variant node into a consistent shape.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function normalizeVariant(array $node): array
    {
        return [
            'gid' => $node['id'],
            'title' => $node['title'],
            'price' => $node['price'],
            'sku' => $node['sku'] ?? null,
            'inventoryItem' => isset($node['inventoryItem']['id'])
                ? ['gid' => $node['inventoryItem']['id']]
                : null,
            'inventoryQuantity' => $node['inventoryQuantity'] ?? 0,
        ];
    }

    /**
     * Normalize a raw Shopify collection node into a consistent shape.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function normalizeCollection(array $node): array
    {
        return [
            'gid' => $node['id'],
            'title' => $node['title'],
            'descriptionHtml' => $node['descriptionHtml'] ?? null,
            'handle' => $node['handle'] ?? null,
        ];
    }
}
