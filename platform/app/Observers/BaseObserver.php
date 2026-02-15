<?php

namespace App\Observers;

use App\Jobs\SyncBaseDeletedToShopifyJob;
use App\Jobs\SyncBaseToShopifyJob;
use App\Models\Base;
use Illuminate\Support\Facades\Log;

class BaseObserver
{
    private const SYNC_FIELDS = ['descriptor', 'retail_price'];

    public function created(Base $base): void
    {
        $this->dispatchSync($base, 'created');
    }

    public function updated(Base $base): void
    {
        if (! $this->syncFieldsChanged($base)) {
            return;
        }

        $this->dispatchSync($base, 'updated');
    }

    public function deleted(Base $base): void
    {
        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return;
        }

        try {
            SyncBaseDeletedToShopifyJob::dispatch($base->id, $base->account_id);
        } catch (\Throwable $e) {
            Log::warning('BaseObserver: failed to dispatch delete sync job', [
                'base_id' => $base->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncFieldsChanged(Base $base): bool
    {
        foreach (self::SYNC_FIELDS as $field) {
            if ($base->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function dispatchSync(Base $base, string $action): void
    {
        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return;
        }

        try {
            SyncBaseToShopifyJob::dispatch($base, $action);
        } catch (\Throwable $e) {
            Log::warning('BaseObserver: failed to dispatch sync job', [
                'base_id' => $base->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
