<?php

namespace App\Services;

use App\Enums\IntegrationLogStatus;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifySyncService;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates bidirectional inventory sync between Fibermade and Shopify.
 */
class InventorySyncService
{
    public function __construct(
        private readonly ?ShopifySyncService $shopifySyncOverride = null
    ) {}

    /**
     * Push a single inventory item's quantity to Shopify.
     *
     * @return bool true if pushed, false if skipped (no variant mapping)
     */
    public function pushInventoryToShopify(Inventory $inventory, Integration $integration, string $syncSource = 'manual_push'): bool
    {
        $variantGid = $inventory->getExternalIdFor($integration, 'shopify_variant');

        if (! $variantGid) {
            return false;
        }

        $shopifySync = $this->shopifySyncFor($integration);
        $shopifySync->setVariantInventory($variantGid, $inventory->quantity);

        $inventory->update([
            'last_synced_at' => now(),
            'sync_status' => 'synced',
        ]);

        $this->logPush($integration, $inventory, IntegrationLogStatus::Success, 'variant', 1, $syncSource);

        return true;
    }

    /**
     * Push all inventory for a colorway to Shopify.
     *
     * Creates product and variants if colorway has no Shopify product yet.
     *
     * @return array{variants_updated: int, variants_created: int, products_created: int, skipped: int}
     */
    public function pushAllInventoryForColorway(Colorway $colorway, Integration $integration, string $syncSource = 'manual_push'): array
    {
        $accountId = $colorway->account_id;
        $productGid = $colorway->getExternalIdFor($integration, 'shopify_product');

        $result = [
            'variants_updated' => 0,
            'variants_created' => 0,
            'products_created' => 0,
            'skipped' => 0,
        ];

        if (! $productGid) {
            $shopifySync = $this->shopifySyncFor($integration);
            $created = $this->createProductAndVariants($colorway, $integration, $shopifySync, $syncSource);
            $result['products_created'] = 1;
            $result['variants_created'] = count($created['variant_ids']);

            foreach ($created['inventories'] as $inv) {
                $inv->update([
                    'last_synced_at' => now(),
                    'sync_status' => 'synced',
                ]);
            }

            $this->logPush($integration, $colorway, IntegrationLogStatus::Success, 'product_create', $result['variants_created'], $syncSource, [
                'shopify_product_id' => $created['product_id'],
            ]);

            return $result;
        }

        $inventories = Inventory::where('account_id', $accountId)
            ->where('colorway_id', $colorway->id)
            ->with('base')
            ->get();

        $shopifySync = $this->shopifySyncFor($integration);

        foreach ($inventories as $inventory) {
            $variantGid = $inventory->getExternalIdFor($integration, 'shopify_variant');

            if (! $variantGid) {
                try {
                    $variantGid = $shopifySync->createVariant($productGid, $inventory->base, $inventory->quantity);
                    ExternalIdentifier::create([
                        'integration_id' => $integration->id,
                        'identifiable_type' => Inventory::class,
                        'identifiable_id' => $inventory->id,
                        'external_type' => 'shopify_variant',
                        'external_id' => $variantGid,
                    ]);
                    $result['variants_created']++;
                } catch (ShopifyApiException $e) {
                    $this->logPush($integration, $inventory, IntegrationLogStatus::Error, 'variant_create', 0, $syncSource, [
                        'error' => $e->getMessage(),
                    ]);
                    $result['skipped']++;

                    continue;
                }
            } else {
                try {
                    $shopifySync->setVariantInventory($variantGid, $inventory->quantity);
                    $result['variants_updated']++;
                } catch (ShopifyApiException $e) {
                    $this->logPush($integration, $inventory, IntegrationLogStatus::Error, 'variant_update', 0, $syncSource, [
                        'error' => $e->getMessage(),
                        'shopify_variant_id' => $variantGid,
                    ]);
                    $result['skipped']++;

                    continue;
                }
            }

            $inventory->update([
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);
        }

        if ($result['variants_updated'] > 0 || $result['variants_created'] > 0) {
            $this->logPush(
                $integration,
                $colorway,
                IntegrationLogStatus::Success,
                'inventory_push',
                $result['variants_updated'] + $result['variants_created'],
                $syncSource,
                $result
            );
        }

        return $result;
    }

    /**
     * @return array{product_id: string, variant_ids: array<string>, inventories: \Illuminate\Support\Collection}
     */
    private function createProductAndVariants(Colorway $colorway, Integration $integration, ShopifySyncService $shopifySync, string $syncSource): array
    {
        $created = $shopifySync->createProduct($colorway, $integration);

        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Colorway::class,
            'identifiable_id' => $colorway->id,
            'external_type' => 'shopify_product',
            'external_id' => $created['product_id'],
        ]);

        $accountId = $colorway->account_id;
        $inventories = Inventory::where('account_id', $accountId)
            ->where('colorway_id', $colorway->id)
            ->with('base')
            ->get();

        $bases = $colorway->account->bases()
            ->where('status', \App\Enums\BaseStatus::Active)
            ->orderBy('id')
            ->get();
        $variantIds = $created['variant_ids'];

        foreach ($bases as $i => $base) {
            $inventory = $inventories->firstWhere('base_id', $base->id);
            if (! $inventory) {
                $inventory = Inventory::create([
                    'account_id' => $accountId,
                    'colorway_id' => $colorway->id,
                    'base_id' => $base->id,
                    'quantity' => 0,
                ]);
                $inventories->push($inventory);
            }

            if (isset($variantIds[$i])) {
                ExternalIdentifier::create([
                    'integration_id' => $integration->id,
                    'identifiable_type' => Inventory::class,
                    'identifiable_id' => $inventory->id,
                    'external_type' => 'shopify_variant',
                    'external_id' => $variantIds[$i],
                ]);

                $shopifySync->setVariantInventory($variantIds[$i], $inventory->quantity);
            }
        }

        try {
            $shopifySync->syncImages($colorway, $created['product_id']);
        } catch (ShopifyApiException $e) {
            $this->logPush($integration, $colorway, IntegrationLogStatus::Warning, 'image_sync', 0, $syncSource, [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'product_id' => $created['product_id'],
            'variant_ids' => $variantIds,
            'inventories' => $inventories,
        ];
    }

    /**
     * Pull inventory quantity from Shopify into Fibermade.
     *
     * Used when processing inventory_levels/update webhook.
     * Accepts webhook value (Shopify wins). When both systems changed since last sync,
     * logs a conflict warning to IntegrationLog for manual review.
     */
    public function pullInventoryFromShopify(string $variantGid, int $quantity, Integration $integration, string $syncSource = 'webhook'): bool
    {
        $identifier = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('external_type', 'shopify_variant')
            ->where('external_id', $variantGid)
            ->where('identifiable_type', Inventory::class)
            ->first();

        if (! $identifier) {
            return false;
        }

        $inventory = Inventory::find($identifier->identifiable_id);
        if (! $inventory) {
            return false;
        }

        $fibermadeQuantity = $inventory->quantity;
        $lastSyncedAt = $inventory->last_synced_at;
        $isConflict = $this->detectInventoryConflict($inventory, $quantity);

        DB::transaction(function () use ($inventory, $quantity, $integration, $syncSource, $fibermadeQuantity, $lastSyncedAt, $isConflict) {
            $inventory->update([
                'quantity' => $quantity,
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);

            if ($isConflict) {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => Inventory::class,
                    'loggable_id' => $inventory->id,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => 'Inventory sync conflict: both Fibermade and Shopify changed since last sync; Shopify value applied',
                    'metadata' => [
                        'sync_source' => $syncSource,
                        'direction' => 'pull',
                        'conflict' => true,
                        'fibermade_quantity' => $fibermadeQuantity,
                        'shopify_quantity' => $quantity,
                        'last_synced_at' => $lastSyncedAt?->toIso8601String(),
                        'shopify_variant_id' => $inventory->getExternalIdFor($integration, 'shopify_variant'),
                    ],
                    'synced_at' => now(),
                ]);
            } else {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => Inventory::class,
                    'loggable_id' => $inventory->id,
                    'status' => IntegrationLogStatus::Success,
                    'message' => "Pulled inventory from Shopify: quantity={$quantity}",
                    'metadata' => [
                        'sync_source' => $syncSource,
                        'direction' => 'pull',
                        'quantity' => $quantity,
                        'shopify_variant_id' => $inventory->getExternalIdFor($integration, 'shopify_variant'),
                    ],
                    'synced_at' => now(),
                ]);
            }
        });

        return true;
    }

    /**
     * True when both Fibermade and Shopify have changed since last sync and values differ.
     * Within 60 seconds of last sync we treat the webhook as likely our own push echo (no conflict).
     */
    private function detectInventoryConflict(Inventory $inventory, int $incomingQuantity): bool
    {
        $lastSyncedAt = $inventory->last_synced_at;
        if (! $lastSyncedAt) {
            return false;
        }

        if ($inventory->updated_at <= $lastSyncedAt) {
            return false;
        }

        if ($inventory->quantity === $incomingQuantity) {
            return false;
        }

        $secondsSinceSync = now()->diffInSeconds($lastSyncedAt, false);
        if ($secondsSinceSync < 0) {
            $secondsSinceSync = -$secondsSinceSync;
        }
        if ($secondsSinceSync <= 60) {
            return false;
        }

        return true;
    }

    private function shopifySyncFor(Integration $integration): ShopifySyncService
    {
        if ($this->shopifySyncOverride !== null) {
            return $this->shopifySyncOverride;
        }

        $client = self::createShopifyClient($integration);

        if (! $client) {
            throw new \RuntimeException('Shopify integration is not configured. Add shop domain and access token to integration settings and credentials.');
        }

        return new ShopifySyncService($client);
    }

    /**
     * Create a Shopify API client for the given integration.
     */
    public static function createShopifyClient(Integration $integration): ?ShopifyGraphqlClient
    {
        $config = $integration->getShopifyConfig();

        if (! $config) {
            return null;
        }

        return new ShopifyGraphqlClient($config['shop'], $config['access_token']);
    }

    private function logPush(
        Integration $integration,
        Colorway|Inventory $loggable,
        IntegrationLogStatus $status,
        string $operation,
        int $count,
        string $syncSource,
        array $extra = []
    ): void {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => $loggable::class,
            'loggable_id' => $loggable->id,
            'status' => $status,
            'message' => "Inventory sync: {$operation}, count={$count}",
            'metadata' => array_merge([
                'sync_source' => $syncSource,
                'direction' => 'push',
                'operation' => $operation,
                'count' => $count,
            ], $extra),
            'synced_at' => now(),
        ]);
    }
}
