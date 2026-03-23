<?php

namespace App\Services\Shopify;

use App\Exceptions\SyncAlreadyRunningException;
use App\Jobs\SyncShopifyCollectionsJob;
use App\Jobs\SyncShopifyInventoryJob;
use App\Jobs\SyncShopifyProductsJob;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

/**
 * Coordinates the three Shopify sync types in the correct order.
 *
 * Products must run before Collections (collection sync resolves product GIDs
 * to Colorway IDs). Collections and Products must run before Inventory (inventory
 * sync resolves variant GIDs to Inventory records).
 */
class ShopifySyncOrchestrator
{
    /**
     * Dispatch all three sync jobs in order as a chain.
     *
     * Pull sync is gated behind the `allow_pull_sync` setting (default false).
     * Fibermade is the source of truth — pull sync is disabled by default to
     * prevent Shopify from overwriting Fibermade data.
     *
     * @throws SyncAlreadyRunningException
     */
    public function syncAll(Integration $integration): void
    {
        if (! $this->isPullSyncAllowed($integration)) {
            return;
        }

        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'products',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        Bus::chain([
            new SyncShopifyProductsJob($integration),
            new SyncShopifyCollectionsJob($integration),
            new SyncShopifyInventoryJob($integration),
        ])->dispatch();
    }

    /**
     * Dispatch only the products sync job.
     *
     * Gated behind `allow_pull_sync` — disabled by default.
     *
     * @throws SyncAlreadyRunningException
     */
    public function syncProducts(Integration $integration): void
    {
        if (! $this->isPullSyncAllowed($integration)) {
            return;
        }

        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'products',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        SyncShopifyProductsJob::dispatch($integration);
    }

    /**
     * Dispatch only the collections sync job.
     *
     * Gated behind `allow_pull_sync` — disabled by default.
     *
     * @throws SyncAlreadyRunningException
     */
    public function syncCollections(Integration $integration): void
    {
        if (! $this->isPullSyncAllowed($integration)) {
            return;
        }

        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'collections',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        SyncShopifyCollectionsJob::dispatch($integration);
    }

    /**
     * Dispatch only the inventory sync job.
     *
     * @throws SyncAlreadyRunningException
     */
    public function syncInventory(Integration $integration): void
    {
        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'inventory',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        SyncShopifyInventoryJob::dispatch($integration);
    }

    /**
     * @throws SyncAlreadyRunningException
     */
    private function guardAgainstRunning(Integration $integration): void
    {
        $sync = $integration->settings['sync'] ?? [];

        if (($sync['status'] ?? null) === 'running') {
            throw new SyncAlreadyRunningException(
                "A sync is already running for integration #{$integration->id}."
            );
        }
    }

    /**
     * Whether pull sync (Shopify → Fibermade) is allowed for this integration.
     *
     * Fibermade is the source of truth. Pull sync is disabled by default.
     * Set `allow_pull_sync: true` in integration settings to re-enable
     * (e.g. for admin import of an existing Shopify catalog).
     */
    private function isPullSyncAllowed(Integration $integration): bool
    {
        return (bool) ($integration->settings['allow_pull_sync'] ?? false);
    }

    private function writeSyncState(Integration $integration, array $state): void
    {
        $settings = $integration->settings ?? [];
        $settings['sync'] = array_merge($settings['sync'] ?? [], $state);
        $integration->update(['settings' => $settings]);
    }
}
