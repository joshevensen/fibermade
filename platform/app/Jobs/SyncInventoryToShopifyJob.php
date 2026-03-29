<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Sentry\State\Scope;

class SyncInventoryToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $inventoryId,
        public readonly int $integrationId,
    ) {}

    public function handle(InventorySyncService $inventorySync): void
    {
        $integration = Integration::find($this->integrationId);
        $inventory = Inventory::find($this->inventoryId);

        if (! $integration || ! $inventory) {
            return;
        }

        try {
            $inventorySync->pushInventoryToShopify($inventory, $integration, syncSource: 'observer');
        } catch (ShopifyApiException $e) {
            $integration->handleSyncException($e);

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Inventory::class,
                'loggable_id' => $this->inventoryId,
                'status' => IntegrationLogStatus::Error,
                'message' => 'Inventory sync failed: '.$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'inventory_sync',
                    'error' => $e->getMessage(),
                ],
                'synced_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Sentry\withScope(function (Scope $scope) use ($exception): void {
            $scope->setContext('shopify_sync', [
                'job' => static::class,
                'inventory_id' => $this->inventoryId,
                'integration_id' => $this->integrationId,
            ]);

            \Sentry\captureException($exception);
        });
    }
}
