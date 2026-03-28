<?php

namespace App\Services\Shopify;

use App\Enums\BaseStatus;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\Inventory;
use Illuminate\Support\Facades\Storage;

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
            ->where('status', BaseStatus::Active)
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

        $optionValues = ! empty($variants)
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

        // Shopify auto-creates variants from productOptions.values with price=0.
        // Update prices now that we have the variant IDs.
        if (! empty($variants) && count($variantIds) === count($variants)) {
            $updateEntries = array_map(fn ($variantId, $variant) => [
                'variant_gid' => $variantId,
                'price' => $variant['price'],
            ], $variantIds, $variants);

            $this->updateVariantPricesBulk($product['id'], $updateEntries);
        }

        return [
            'product_id' => $product['id'],
            'variant_ids' => $variantIds,
        ];
    }

    /**
     * Archive a Shopify product by setting its status to ARCHIVED.
     *
     * @throws ShopifyApiException
     */
    public function archiveProduct(string $productGid): void
    {
        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
          productUpdate(input: $input) {
            product { id status }
            userErrors { field message }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'input' => [
                'id' => $productGid,
                'status' => 'ARCHIVED',
            ],
        ]);

        $payload = $result['data']['productUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
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
     * Creates variants on an existing Shopify product for multiple inventories.
     * Returns a map of inventory_id => variant_gid.
     *
     * @param  array<array{inventory: Inventory, base: Base, quantity: int}>  $entries
     * @return array<int, string> [inventory_id => variant_gid]
     *
     * @throws ShopifyApiException
     */
    public function createVariantsBulk(string $productGid, array $entries): array
    {
        $mutation = <<<'GQL'
        mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
          productVariantsBulkCreate(productId: $productId, variants: $variants) {
            productVariants {
              id
              title
              selectedOptions {
                name
                value
              }
              inventoryItem {
                id
              }
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $locationId = $this->getDefaultLocationId();

        $variants = [];
        foreach ($entries as $entry) {
            $variants[] = [
                'optionValues' => [['optionName' => 'Base', 'name' => $entry['base']->descriptor]],
                'price' => (string) ($entry['base']->retail_price ?? '0'),
                'inventoryItem' => [
                    'cost' => (string) ($entry['base']->cost ?? '0'),
                    'tracked' => true,
                ],
                'inventoryQuantities' => [[
                    'locationId' => $locationId,
                    'availableQuantity' => $entry['quantity'],
                ]],
            ];
        }

        $result = $this->client->request($mutation, [
            'productId' => $productGid,
            'variants' => $variants,
        ]);

        $payload = $result['data']['productVariantsBulkCreate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }

        $createdVariants = $payload['productVariants'] ?? [];
        if (count($createdVariants) !== count($entries)) {
            throw new ShopifyApiException('productVariantsBulkCreate returned unexpected number of variants');
        }

        $map = [];
        foreach ($entries as $index => $entry) {
            $variantGid = $createdVariants[$index]['id'] ?? null;
            if (! $variantGid) {
                throw new ShopifyApiException("productVariantsBulkCreate returned no ID for variant at index {$index}");
            }
            $map[$entry['inventory']->id] = $variantGid;
        }

        return $map;
    }

    /**
     * Update only the price of multiple variants after auto-creation via productOptions.
     *
     * @param  array<array{variant_gid: string, price: string}>  $entries
     *
     * @throws ShopifyApiException
     */
    private function updateVariantPricesBulk(string $productGid, array $entries): void
    {
        $mutation = <<<'GQL'
        mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
          productVariantsBulkUpdate(productId: $productId, variants: $variants) {
            productVariants { id }
            userErrors { field message }
          }
        }
        GQL;

        $variants = array_map(fn ($entry) => [
            'id' => $entry['variant_gid'],
            'price' => $entry['price'],
        ], $entries);

        $result = $this->client->request($mutation, [
            'productId' => $productGid,
            'variants' => $variants,
        ]);

        $payload = $result['data']['productVariantsBulkUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Updates multiple variants on a single Shopify product.
     *
     * @param  array<array{variant_gid: string, base: Base}>  $entries
     *
     * @throws ShopifyApiException
     */
    public function updateVariantsBulk(string $productGid, array $entries): void
    {
        $mutation = <<<'GQL'
        mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
          productVariantsBulkUpdate(productId: $productId, variants: $variants) {
            productVariants {
              id
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $variants = [];
        foreach ($entries as $entry) {
            $variants[] = [
                'id' => $entry['variant_gid'],
                'optionValues' => [['optionName' => 'Base', 'name' => $entry['base']->descriptor]],
                'price' => (string) ($entry['base']->retail_price ?? '0'),
            ];
        }

        $result = $this->client->request($mutation, [
            'productId' => $productGid,
            'variants' => $variants,
        ]);

        $payload = $result['data']['productVariantsBulkUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Deletes multiple variants from a single Shopify product.
     * All variant GIDs must belong to the same product.
     *
     * @param  string[]  $variantGids
     *
     * @throws ShopifyApiException
     */
    public function deleteVariantsBulk(string $productGid, array $variantGids): void
    {
        $mutation = <<<'GQL'
        mutation productVariantsBulkDelete($productId: ID!, $variantsIds: [ID!]!) {
          productVariantsBulkDelete(productId: $productId, variantsIds: $variantsIds) {
            product {
              id
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'productId' => $productGid,
            'variantsIds' => $variantGids,
        ]);

        $payload = $result['data']['productVariantsBulkDelete'] ?? [];
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

        if ($media->isEmpty()) {
            return;
        }

        $mediaInputs = $media->map(function ($m) use ($colorway) {
            $url = Storage::disk('public')->url($m->file_path);
            $fullUrl = str_starts_with($url, 'http') ? $url : url($url);

            return [
                'originalSource' => $fullUrl,
                'mediaContentType' => 'IMAGE',
                'alt' => $colorway->name,
            ];
        })->all();

        $mutation = <<<'GQL'
        mutation productUpdate($product: ProductUpdateInput!, $media: [CreateMediaInput!]) {
          productUpdate(product: $product, media: $media) {
            product {
              id
              media(first: 10) {
                edges {
                  node {
                    id
                    status
                  }
                }
              }
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'product' => ['id' => $productGid],
            'media' => $mediaInputs,
        ]);

        $payload = $result['data']['productUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException("Image upload failed: {$message}", $userErrors);
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
}
