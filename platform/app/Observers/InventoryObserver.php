<?php

namespace App\Observers;

use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\Inventory;

class InventoryObserver
{
    public function updated(Inventory $inventory): void
    {
        // TODO: re-enable when inventory ships post-launch
    }

    private function getShopifyIntegration(int $accountId): ?Integration
    {
        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }
}
