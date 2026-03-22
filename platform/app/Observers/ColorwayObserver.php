<?php

namespace App\Observers;

use App\Enums\IntegrationType;
use App\Jobs\SyncColorwayCatalogToShopifyJob;
use App\Models\Colorway;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;

class ColorwayObserver
{
    private const CATALOG_FIELDS = ['name', 'description', 'status', 'technique', 'colors'];

    public function created(Colorway $colorway): void
    {
        $integration = $this->getShopifyIntegration($colorway->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            SyncColorwayCatalogToShopifyJob::dispatch($colorway, 'created');
        } catch (\Throwable $e) {
            Log::warning('ColorwayObserver: failed to dispatch catalog sync job', [
                'colorway_id' => $colorway->id,
                'action' => 'created',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Colorway $colorway): void
    {
        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return;
        }

        if (! $this->catalogFieldsChanged($colorway)) {
            return;
        }

        try {
            SyncColorwayCatalogToShopifyJob::dispatch($colorway);
        } catch (\Throwable $e) {
            Log::warning('ColorwayObserver: failed to dispatch catalog sync job', [
                'colorway_id' => $colorway->id,
                'action' => 'updated',
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

    private function catalogFieldsChanged(Colorway $colorway): bool
    {
        foreach (self::CATALOG_FIELDS as $field) {
            if ($colorway->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }
}
