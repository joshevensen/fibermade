<?php

namespace App\Observers;

use App\Jobs\SyncColorwayCatalogToShopifyJob;
use App\Models\Colorway;
use Illuminate\Support\Facades\Log;

class ColorwayObserver
{
    private const CATALOG_FIELDS = ['name', 'description', 'status', 'technique', 'colors'];

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
                'error' => $e->getMessage(),
            ]);
        }
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
