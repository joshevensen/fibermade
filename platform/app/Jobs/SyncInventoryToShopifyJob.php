<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        $inventorySync->pushInventoryToShopify($inventory, $integration, syncSource: 'observer');
    }
}
