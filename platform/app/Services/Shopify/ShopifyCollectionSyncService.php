<?php

namespace App\Services\Shopify;

use App\Data\Shopify\SyncResult;
use App\Enums\IntegrationLogStatus;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;

/**
 * Pulls Shopify collections into Fibermade as Collection records.
 *
 * Depends on product sync having run first — product GIDs must already be
 * mapped to Colorways before collection sync can assign them correctly.
 *
 * Only published Shopify collections are synced (archived/unpublished are
 * excluded at the API level via the published_status filter in getCollections()).
 */
class ShopifyCollectionSyncService
{
    public function __construct(
        private readonly ?ShopifyGraphqlClient $clientOverride = null
    ) {}

    /**
     * Sync all (published) Shopify collections into Fibermade.
     */
    public function syncAll(Integration $integration): SyncResult
    {
        $client = $this->shopifyClientFor($integration);
        $result = new SyncResult;
        $cursor = null;

        do {
            $page = $client->getCollections($cursor);

            foreach ($page['collections'] as $collection) {
                try {
                    $outcome = $this->syncCollection($collection, $integration, $client);
                    match ($outcome) {
                        'created' => $result->created++,
                        'updated' => $result->updated++,
                        'skipped' => $result->skipped++,
                    };
                } catch (\Throwable $e) {
                    $result->addError($collection['gid'], $e->getMessage());
                }
            }

            $cursor = $page['nextCursor'];
        } while ($page['hasNextPage']);

        return $result;
    }

    /**
     * Sync a single Shopify collection (create or update).
     *
     * @return string 'created'|'updated'|'skipped'
     */
    public function syncCollection(array $shopifyCollection, Integration $integration, ?ShopifyGraphqlClient $client = null): string
    {
        $client ??= $this->shopifyClientFor($integration);

        $identifier = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('external_type', 'shopify_collection')
            ->where('external_id', $shopifyCollection['gid'])
            ->where('identifiable_type', Collection::class)
            ->first();

        if ($identifier) {
            $collection = Collection::find($identifier->identifiable_id);
            if ($collection) {
                $this->updateCollection($collection, $shopifyCollection, $integration, $client);

                return 'updated';
            }
        }

        $this->createCollection($shopifyCollection, $integration, $client);

        return 'created';
    }

    private function createCollection(array $shopifyCollection, Integration $integration, ShopifyGraphqlClient $client): Collection
    {
        $shop = $this->shopDomain($integration);
        $name = $this->collectionName($shopifyCollection);

        $collection = Collection::create([
            'account_id' => $integration->account_id,
            'name' => $name,
            'description' => $shopifyCollection['descriptionHtml'] ? trim($shopifyCollection['descriptionHtml']) : null,
            'status' => 'active',
        ]);

        $collectionNumericId = $this->numericId($shopifyCollection['gid']);
        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Collection::class,
            'identifiable_id' => $collection->id,
            'external_type' => 'shopify_collection',
            'external_id' => $shopifyCollection['gid'],
            'data' => [
                'admin_url' => "https://{$shop}/admin/collections/{$collectionNumericId}",
                'shopify_handle' => $shopifyCollection['handle'],
            ],
        ]);

        $colorwayIds = $this->resolveColorwayIds($shopifyCollection['gid'], $integration, $client);

        if (! empty($colorwayIds)) {
            $collection->colorways()->sync($colorwayIds);
        }

        $logStatus = ! empty($colorwayIds) ? IntegrationLogStatus::Success : IntegrationLogStatus::Warning;
        $this->log(
            $integration,
            $collection,
            $logStatus,
            ! empty($colorwayIds)
                ? "Imported Shopify collection '{$name}' as Collection #{$collection->id} with ".count($colorwayIds).' colorway(s)'
                : "Imported Shopify collection '{$name}' as Collection #{$collection->id} (no colorways mapped)",
            ['shopify_gid' => $shopifyCollection['gid'], 'colorway_count' => count($colorwayIds)]
        );

        return $collection;
    }

    private function updateCollection(Collection $collection, array $shopifyCollection, Integration $integration, ShopifyGraphqlClient $client): void
    {
        $name = $this->collectionName($shopifyCollection);

        $collection->update([
            'name' => $name,
            'description' => $shopifyCollection['descriptionHtml'] ? trim($shopifyCollection['descriptionHtml']) : null,
        ]);

        $colorwayIds = $this->resolveColorwayIds($shopifyCollection['gid'], $integration, $client);
        $collection->colorways()->sync($colorwayIds);

        $this->log(
            $integration,
            $collection,
            IntegrationLogStatus::Success,
            "Updated Collection #{$collection->id} '{$name}' (".count($colorwayIds).' colorway(s))',
            ['shopify_gid' => $shopifyCollection['gid'], 'colorway_count' => count($colorwayIds)]
        );
    }

    /**
     * Fetch all product GIDs for a collection and resolve them to Colorway IDs.
     *
     * Products with no mapping are silently skipped.
     *
     * @return array<int>
     */
    private function resolveColorwayIds(string $collectionGid, Integration $integration, ShopifyGraphqlClient $client): array
    {
        $colorwayIds = [];
        $cursor = null;

        do {
            $page = $client->getCollectionProducts($collectionGid, $cursor);

            foreach ($page['products'] as $product) {
                $productGid = $product['gid'];

                $identifier = ExternalIdentifier::where('integration_id', $integration->id)
                    ->where('external_type', 'shopify_product')
                    ->where('external_id', $productGid)
                    ->where('identifiable_type', Colorway::class)
                    ->first();

                if ($identifier) {
                    $colorwayIds[] = $identifier->identifiable_id;
                }
            }

            $cursor = $page['nextCursor'];
        } while ($page['hasNextPage']);

        return $colorwayIds;
    }

    private function collectionName(array $collection): string
    {
        return trim($collection['title'] ?? '') ?: 'Untitled';
    }

    private function numericId(string $gid): string
    {
        return last(explode('/', $gid)) ?? '';
    }

    private function shopDomain(Integration $integration): string
    {
        return $integration->getShopifyConfig()['shop'] ?? '';
    }

    private function shopifyClientFor(Integration $integration): ShopifyGraphqlClient
    {
        if ($this->clientOverride !== null) {
            return $this->clientOverride;
        }

        $config = $integration->getShopifyConfig();
        if (! $config) {
            throw new \RuntimeException('Shopify integration not configured.');
        }

        return new ShopifyGraphqlClient($config['shop'], $config['access_token']);
    }

    private function log(Integration $integration, Collection $collection, IntegrationLogStatus $status, string $message, array $metadata = []): void
    {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Collection::class,
            'loggable_id' => $collection->id,
            'status' => $status,
            'message' => $message,
            'metadata' => $metadata,
            'synced_at' => now(),
        ]);
    }
}
