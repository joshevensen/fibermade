<?php

namespace App\Services\Shopify;

use App\Enums\IntegrationLogStatus;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;

/**
 * Handles Shopify API mutations for collections.
 */
class ShopifyCollectionPushService
{
    public function __construct(
        private readonly ShopifyGraphqlClient $client,
    ) {}

    /**
     * Create a custom collection in Shopify and store the ExternalIdentifier mapping.
     *
     * @throws ShopifyApiException
     */
    public function createCollection(Collection $collection, Integration $integration): string
    {
        $mutation = <<<'GQL'
        mutation collectionCreate($input: CollectionInput!) {
          collectionCreate(input: $input) {
            collection {
              id
              title
              handle
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'input' => [
                'title' => $collection->name,
                'descriptionHtml' => $collection->description ?? '',
            ],
        ]);

        $payload = $result['data']['collectionCreate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }

        $shopifyCollection = $payload['collection'] ?? null;
        if (! $shopifyCollection) {
            throw new ShopifyApiException('collectionCreate returned no collection');
        }

        $collectionGid = $shopifyCollection['id'];

        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Collection::class,
            'identifiable_id' => $collection->id,
            'external_type' => 'shopify_collection',
            'external_id' => $collectionGid,
            'data' => ['handle' => $shopifyCollection['handle']],
        ]);

        return $collectionGid;
    }

    /**
     * Update a collection's title and description in Shopify.
     *
     * @throws ShopifyApiException
     */
    public function updateCollection(Collection $collection, string $collectionGid): void
    {
        $mutation = <<<'GQL'
        mutation collectionUpdate($input: CollectionInput!) {
          collectionUpdate(input: $input) {
            collection {
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
            'input' => [
                'id' => $collectionGid,
                'title' => $collection->name,
                'descriptionHtml' => $collection->description ?? '',
            ],
        ]);

        $payload = $result['data']['collectionUpdate'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Sync which products belong to a collection.
     *
     * Adds current colorway products and removes products for any removed colorway IDs.
     * Colorways without a Shopify product mapping are skipped and logged as warnings.
     *
     * @param  int[]  $removedColorwayIds
     *
     * @throws ShopifyApiException
     */
    public function syncCollectionProducts(
        Collection $collection,
        string $collectionGid,
        Integration $integration,
        array $removedColorwayIds = [],
    ): void {
        $colorways = $collection->colorways()->with('externalIdentifiers')->get();

        $productGids = [];
        foreach ($colorways as $colorway) {
            $gid = $colorway->getExternalIdFor($integration, 'shopify_product');
            if ($gid) {
                $productGids[] = $gid;
            } else {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => Collection::class,
                    'loggable_id' => $collection->id,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => "Colorway #{$colorway->id} has no Shopify product mapping; skipped during collection sync",
                    'metadata' => [
                        'operation' => 'collection_products_sync',
                        'colorway_id' => $colorway->id,
                    ],
                    'synced_at' => now(),
                ]);
            }
        }

        if (! empty($productGids)) {
            $this->addProductsToCollection($collectionGid, $productGids);
        }

        if (! empty($removedColorwayIds)) {
            $removedProductGids = ExternalIdentifier::where('integration_id', $integration->id)
                ->where('identifiable_type', Colorway::class)
                ->whereIn('identifiable_id', $removedColorwayIds)
                ->where('external_type', 'shopify_product')
                ->pluck('external_id')
                ->all();

            if (! empty($removedProductGids)) {
                $this->removeProductsFromCollection($collectionGid, $removedProductGids);
            }
        }
    }

    /**
     * Delete a collection from Shopify via REST.
     *
     * @throws ShopifyApiException
     */
    public function deleteCollection(string $collectionGid): void
    {
        $numericId = str_replace('gid://shopify/Collection/', '', $collectionGid);
        $this->client->restDelete("custom_collections/{$numericId}.json");
    }

    /**
     * Add products to a collection via collectionAddProductsV2 (async — fire-and-forget).
     *
     * @param  string[]  $productGids
     *
     * @throws ShopifyApiException
     */
    private function addProductsToCollection(string $collectionGid, array $productGids): void
    {
        $mutation = <<<'GQL'
        mutation collectionAddProductsV2($id: ID!, $productIds: [ID!]!) {
          collectionAddProductsV2(id: $id, productIds: $productIds) {
            job {
              id
              done
            }
            userErrors {
              field
              message
            }
          }
        }
        GQL;

        $result = $this->client->request($mutation, [
            'id' => $collectionGid,
            'productIds' => $productGids,
        ]);

        $payload = $result['data']['collectionAddProductsV2'] ?? [];
        $userErrors = $payload['userErrors'] ?? [];

        if (! empty($userErrors)) {
            $message = collect($userErrors)->pluck('message')->implode('; ');
            throw new ShopifyApiException($message, $userErrors);
        }
    }

    /**
     * Remove specific products from a collection via the REST collects API.
     *
     * @param  string[]  $productGids
     *
     * @throws ShopifyApiException
     */
    public function removeProductsFromCollection(string $collectionGid, array $productGids): void
    {
        $collectionNumericId = (int) str_replace('gid://shopify/Collection/', '', $collectionGid);
        $collects = $this->client->restGet("collects.json?collection_id={$collectionNumericId}");
        $collectList = $collects['collects'] ?? [];

        foreach ($productGids as $productGid) {
            $productNumericId = (int) str_replace('gid://shopify/Product/', '', $productGid);
            foreach ($collectList as $collect) {
                if ((int) $collect['product_id'] === $productNumericId) {
                    $this->client->restDelete("collects/{$collect['id']}.json");
                    break;
                }
            }
        }
    }
}
