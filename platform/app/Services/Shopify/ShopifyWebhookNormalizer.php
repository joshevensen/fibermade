<?php

namespace App\Services\Shopify;

/**
 * Converts Shopify REST webhook payloads to the array shape expected by
 * ShopifyProductSyncService and ShopifyCollectionSyncService.
 *
 * Shopify webhooks deliver REST-format payloads (integer IDs, snake_case fields),
 * while the sync services expect GID strings and GraphQL-style field names.
 */
class ShopifyWebhookNormalizer
{
    private const STATUS_MAP = [
        'active' => 'ACTIVE',
        'draft' => 'DRAFT',
        'archived' => 'ARCHIVED',
    ];

    /**
     * Normalize a products/create or products/update REST payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeProduct(array $payload): array
    {
        $id = $payload['id'] ?? null;
        $gid = $id !== null ? "gid://shopify/Product/{$id}" : '';

        $variants = [];
        foreach ($payload['variants'] ?? [] as $v) {
            $variantId = $v['id'] ?? null;
            $variantGid = $variantId !== null ? "gid://shopify/ProductVariant/{$variantId}" : '';
            $variants[] = [
                'gid' => $variantGid,
                'title' => $v['title'] ?? 'Default Title',
                'price' => isset($v['price']) ? (string) $v['price'] : '',
            ];
        }

        $featuredImage = null;
        $images = $payload['images'] ?? [];
        if (! empty($images) && isset($images[0]['src'])) {
            $featuredImage = ['url' => $images[0]['src']];
        }

        $status = strtolower((string) ($payload['status'] ?? 'active'));

        return [
            'gid' => $gid,
            'title' => $payload['title'] ?? '',
            'descriptionHtml' => isset($payload['body_html']) ? (string) $payload['body_html'] : null,
            'status' => self::STATUS_MAP[$status] ?? 'ACTIVE',
            'handle' => isset($payload['handle']) && $payload['handle'] !== '' ? (string) $payload['handle'] : null,
            'featuredImage' => $featuredImage,
            'variants' => $variants,
        ];
    }

    /**
     * Normalize a collections/create or collections/update REST payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeCollection(array $payload): array
    {
        $id = $payload['id'] ?? null;
        $gid = $id !== null ? "gid://shopify/Collection/{$id}" : '';

        return [
            'gid' => $gid,
            'title' => $payload['title'] ?? '',
            'descriptionHtml' => isset($payload['body_html']) ? (string) $payload['body_html'] : null,
            'handle' => isset($payload['handle']) && $payload['handle'] !== '' ? (string) $payload['handle'] : null,
        ];
    }

    /**
     * Extract the Shopify product GID from a products/delete payload.
     */
    public function extractProductGid(array $payload): string
    {
        $id = $payload['id'] ?? null;

        return $id !== null ? "gid://shopify/Product/{$id}" : '';
    }

    /**
     * Extract the Shopify collection GID from a collections/delete payload.
     */
    public function extractCollectionGid(array $payload): string
    {
        $id = $payload['id'] ?? null;

        return $id !== null ? "gid://shopify/Collection/{$id}" : '';
    }
}
