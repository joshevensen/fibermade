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
    private const API_VERSION = '2024-01';

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
     * @throws \App\Services\Shopify\ShopifyApiException
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
}
