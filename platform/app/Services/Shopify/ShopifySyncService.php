<?php

namespace App\Services\Shopify;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\Inventory;

/**
 * Handles Shopify API mutations for products, variants, images, and inventory.
 */
class ShopifySyncService
{
    public function __construct(
        private readonly ShopifyGraphqlClient $client
    ) {}

    /**
     * Set inventory quantity for a Shopify variant.
     *
     * @throws ShopifyApiException
     */
    public function setVariantInventory(string $variantGid, int $quantity): void
    {
        $inventoryItemId = $this->getVariantInventoryItemId($variantGid);
        $locationId = $this->getDefaultLocationId();

        $mutation = <<<'GQL'
        mutation inventorySetQuantities($input: InventorySetQuantitiesInput!) {
          inventorySetQuantities(input: $input) {
            userErrors {
              code
              field
              message
            }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'input' => [
                'ignoreCompareQuantity' => true,
                'name' => 'available',
                'reason' => 'correction',
                'quantities' => [
                    [
                        'inventoryItemId' => $inventoryItemId,
                        'locationId' => $locationId,
                        'quantity' => $quantity,
                    ],
                ],
            ],
        ]);

        $payload = $result['data']['inventorySetQuantities'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];
        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Get the inventory item ID for a product variant.
     */
    private function getVariantInventoryItemId(string $variantGid): string
    {
        $query = <<<'GQL'
        query getVariantInventoryItem($id: ID!) {
          productVariant(id: $id) {
            inventoryItem {
              id
            }
          }
        }
        GQL;

        $result = $this->client->request($query, ['id' => $variantGid]);
        $variant = $result['data']['productVariant'] ?? null;

        if (! $variant || ! ($variant['inventoryItem']['id'] ?? null)) {
            throw new ShopifyApiException("Variant not found or has no inventory item: {$variantGid}");
        }

        return $variant['inventoryItem']['id'];
    }

    /**
     * Get the first manageable location ID for the shop.
     */
    private function getDefaultLocationId(): string
    {
        $query = <<<'GQL'
        query getLocations {
          locations(first: 1, query: "manageable") {
            edges {
              node {
                id
              }
            }
          }
        }
        GQL;

        $result = $this->client->request($query);
        $edges = $result['data']['locations']['edges'] ?? [];

        if (empty($edges) || ! ($edges[0]['node']['id'] ?? null)) {
            throw new ShopifyApiException('No inventory location found for this shop');
        }

        return $edges[0]['node']['id'];
    }

    /**
     * Create a Shopify product from a Colorway with all variants.
     *
     * @return array{product_id: string, variant_ids: array<string>}
     *
     * @throws ShopifyApiException
     */
    public function createProduct(Colorway $colorway, Integration $integration): array
    {
        $account = $integration->account;
        $bases = Base::where('account_id', $account->id)
            ->where('status', \App\Enums\BaseStatus::Active)
            ->orderBy('id')
            ->get();

        $variants = [];
        foreach ($bases as $base) {
            $variants[] = [
                'optionValues' => [['optionName' => 'Base', 'name' => $base->descriptor]],
                'price' => (string) ($base->retail_price ?? '0'),
            ];
        }

        if (empty($variants)) {
            $variants[] = [
                'optionValues' => [['optionName' => 'Base', 'name' => 'Default']],
                'price' => '0',
            ];
        }

        $status = match ($colorway->status->value) {
            'active' => 'ACTIVE',
            'retired' => 'ARCHIVED',
            'idea' => 'DRAFT',
            default => 'ACTIVE',
        };

        $tags = collect();
        if ($colorway->colors) {
            $tags = $tags->merge($colorway->colors->map(fn ($c) => $c->value));
        }
        if ($colorway->technique) {
            $tags->push($colorway->technique->value);
        }

        $mutation = <<<'GQL'
        mutation productCreate($product: ProductCreateInput!) {
          productCreate(product: $product) {
            product {
              id
              handle
              variants(first: 100) {
                edges { node { id } }
              }
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $optionValues = $variants
            ? array_map(fn ($v) => ['name' => $v['optionValues'][0]['name'] ?? 'Default'], $variants)
            : [['name' => 'Default']];

        $productInput = [
            'title' => $colorway->name ?: 'Untitled',
            'descriptionHtml' => $colorway->description ?: '',
            'productType' => 'Yarn',
            'vendor' => $account->name ?? 'Fibermade',
            'status' => $status,
            'tags' => $tags->unique()->values()->all(),
            'productOptions' => [['name' => 'Base', 'values' => $optionValues]],
            'variants' => $variants,
        ];

        if ($colorway->per_pan > 0) {
            $productInput['metafields'] = [
                [
                    'namespace' => 'fibermade',
                    'key' => 'per_pan',
                    'value' => (string) $colorway->per_pan,
                    'type' => 'number_integer',
                ],
            ];
        }

        $result = $this->client->request($mutation, ['product' => $productInput]);
        $payload = $result['data']['productCreate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }

        $product = $payload['product'] ?? null;
        if (! $product) {
            throw new ShopifyApiException('productCreate returned no product');
        }

        $variantIds = collect($product['variants']['edges'] ?? [])
            ->pluck('node.id')
            ->all();

        return [
            'product_id' => $product['id'],
            'variant_ids' => $variantIds,
        ];
    }

    /**
     * Update a Shopify product's basic fields.
     */
    public function updateProduct(Colorway $colorway, string $productGid): void
    {
        $status = match ($colorway->status->value) {
            'active' => 'ACTIVE',
            'retired' => 'ARCHIVED',
            'idea' => 'DRAFT',
            default => 'ACTIVE',
        };

        $tags = collect();
        if ($colorway->colors) {
            $tags = $tags->merge($colorway->colors->map(fn ($c) => $c->value));
        }
        if ($colorway->technique) {
            $tags->push($colorway->technique->value);
        }

        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
          productUpdate(input: $input) {
            product { id }
            userErrors { field message }
          }
        }
        GQL;

        $input = [
            'id' => $productGid,
            'title' => $colorway->name ?: 'Untitled',
            'descriptionHtml' => $colorway->description ?: '',
            'status' => $status,
            'tags' => $tags->unique()->values()->all(),
        ];

        $result = $this->client->request($mutation, ['input' => $input]);
        $payload = $result['data']['productUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Create a variant for a product.
     *
     * @return string variant GID
     */
    public function createVariant(string $productGid, Base $base, int $quantity = 0): string
    {
        $mutation = <<<'GQL'
        mutation productVariantCreate($input: ProductVariantInput!) {
          productVariantCreate(input: $input) {
            productVariant { id }
            userErrors { field message }
          }
        }
        GQL;

        $input = [
            'productId' => $productGid,
            'optionValues' => [['optionName' => 'Base', 'name' => $base->descriptor]],
            'price' => (string) ($base->retail_price ?? '0'),
            'inventoryQuantities' => [['availableQuantity' => $quantity]],
        ];

        $result = $this->client->request($mutation, ['input' => $input]);
        $payload = $result['data']['productVariantCreate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }

        $variant = $payload['productVariant'] ?? null;
        if (! $variant || ! ($variant['id'] ?? null)) {
            throw new ShopifyApiException('productVariantCreate returned no variant');
        }

        return $variant['id'];
    }

    /**
     * Update a variant's price and option.
     */
    public function updateVariant(string $variantGid, Base $base): void
    {
        $mutation = <<<'GQL'
        mutation productVariantUpdate($input: ProductVariantInput!) {
          productVariantUpdate(input: $input) {
            productVariant { id }
            userErrors { field message }
          }
        }
        GQL;

        $input = [
            'id' => $variantGid,
            'optionValues' => [['optionName' => 'Base', 'name' => $base->descriptor]],
            'price' => (string) ($base->retail_price ?? '0'),
        ];

        $result = $this->client->request($mutation, ['input' => $input]);
        $payload = $result['data']['productVariantUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Delete a variant from a product.
     */
    public function deleteVariant(string $variantGid): void
    {
        $mutation = <<<'GQL'
        mutation productVariantDelete($id: ID!) {
          productVariantDelete(id: $id) {
            deletedProductVariantId
            userErrors { field message }
          }
        }
        GQL;

        $result = $this->client->request($mutation, ['id' => $variantGid]);
        $payload = $result['data']['productVariantDelete'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Sync product images from Colorway media.
     * Replaces existing product media to avoid duplicates on re-sync.
     */
    public function syncImages(Colorway $colorway, string $productGid): void
    {
        $media = $colorway->media()
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $this->deleteExistingProductMedia($productGid);

        foreach ($media as $m) {
            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($m->file_path);
            $fullUrl = str_starts_with($url, 'http') ? $url : url($url);
            $this->createProductMedia($productGid, $fullUrl);
        }
    }

    /**
     * Delete all existing media for a product (images, etc).
     */
    private function deleteExistingProductMedia(string $productGid): void
    {
        $query = <<<'GQL'
        query getProductMedia($id: ID!) {
          product(id: $id) {
            media(first: 50) {
              edges { node { id } }
            }
          }
        }
        GQL;

        $result = $this->client->request($query, ['id' => $productGid]);
        $product = $result['data']['product'] ?? null;
        $edges = $product['media']['edges'] ?? [];

        if (empty($edges)) {
            return;
        }

        $mediaIds = collect($edges)->pluck('node.id')->all();

        $mutation = <<<'GQL'
        mutation productDeleteMedia($productId: ID!, $mediaIds: [ID!]!) {
          productDeleteMedia(productId: $productId, mediaIds: $mediaIds) {
            deletedMediaIds
            mediaUserErrors { field message }
          }
        }
        GQL;

        $this->client->request($mutation, [
            'productId' => $productGid,
            'mediaIds' => $mediaIds,
        ]);
    }

    private function createProductMedia(string $productGid, string $imageUrl): void
    {
        $mutation = <<<'GQL'
        mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
          productCreateMedia(productId: $productId, media: $media) {
            media { ... on MediaImage { id } }
            mediaUserErrors { field message code }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'productId' => $productGid,
            'media' => [
                ['originalSource' => $imageUrl, 'mediaContentType' => 'IMAGE'],
            ],
        ]);

        $payload = $result['data']['productCreateMedia'] ?? [];
        $errors = $payload['mediaUserErrors'] ?? [];
        if (! empty($errors)) {
            $message = collect($errors)->pluck('message')->implode('; ');
            throw new ShopifyApiException("Image upload failed: {$message}", $errors);
        }
    }
}
