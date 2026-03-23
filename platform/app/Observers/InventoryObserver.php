<?php

namespace App\Observers;

use App\Enums\IntegrationType;
use App\Jobs\SyncInventoryToShopifyJob;
use App\Models\Integration;
use App\Models\Inventory;
use Illuminate\Support\Facades\Log;

class InventoryObserver
{
    public function updated(Inventory $inventory): void
    {
        if (! $inventory->wasChanged('quantity')) {
            return;
        }

        $integration = $this->getShopifyIntegration($inventory->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            SyncInventoryToShopifyJob::dispatch($inventory->id, $integration->id);
        } catch (\Throwable $e) {
            Log::warning('InventoryObserver: failed to dispatch sync job', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getShopifyIntegration(int $accountId): ?Integration
    {
        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }
}
