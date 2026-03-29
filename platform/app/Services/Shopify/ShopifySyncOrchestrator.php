<?php

namespace App\Services\Shopify;

use App\Exceptions\SyncAlreadyRunningException;
use App\Jobs\PullCollectionsJob;
use App\Jobs\PullColorwaysJob;
use App\Jobs\PullInventoryJob;
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
     * Dispatch all three pull jobs in order as a chain.
     *
     * @throws SyncAlreadyRunningException
     */
    public function pullAll(Integration $integration): void
    {
        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'products',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        Bus::chain([
            new PullColorwaysJob($integration),
            new PullCollectionsJob($integration),
            new PullInventoryJob($integration),
        ])->dispatch();
    }

    /**
     * Dispatch only the colorways pull job.
     *
     * @throws SyncAlreadyRunningException
     */
    public function pullColorways(Integration $integration): void
    {
        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'products',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        PullColorwaysJob::dispatch($integration);
    }

    /**
     * Dispatch only the collections pull job.
     *
     * @throws SyncAlreadyRunningException
     */
    public function pullCollections(Integration $integration): void
    {
        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'collections',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        PullCollectionsJob::dispatch($integration);
    }

    /**
     * Dispatch only the inventory pull job.
     *
     * @throws SyncAlreadyRunningException
     */
    public function pullInventory(Integration $integration): void
    {
        $this->guardAgainstRunning($integration);

        $this->writeSyncState($integration, [
            'status' => 'running',
            'current_step' => 'inventory',
            'started_at' => Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);

        PullInventoryJob::dispatch($integration);
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

    private function writeSyncState(Integration $integration, array $state): void
    {
        $settings = $integration->settings ?? [];
        $settings['sync'] = array_merge($settings['sync'] ?? [], $state);
        $integration->update(['settings' => $settings]);
    }
}
