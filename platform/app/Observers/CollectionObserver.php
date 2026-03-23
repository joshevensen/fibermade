<?php

namespace App\Observers;

use App\Enums\IntegrationType;
use App\Jobs\SyncCollectionDeletedToShopifyJob;
use App\Jobs\SyncCollectionToShopifyJob;
use App\Models\Collection;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;

class CollectionObserver
{
    public function created(Collection $collection): void
    {
        $integration = $this->getShopifyIntegration($collection->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            SyncCollectionToShopifyJob::dispatch($collection, 'created');
        } catch (\Throwable $e) {
            Log::warning('CollectionObserver: failed to dispatch sync job', [
                'collection_id' => $collection->id,
                'action' => 'created',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Collection $collection): void
    {
        $integration = $this->getShopifyIntegration($collection->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            SyncCollectionToShopifyJob::dispatch($collection, 'updated');
        } catch (\Throwable $e) {
            Log::warning('CollectionObserver: failed to dispatch sync job', [
                'collection_id' => $collection->id,
                'action' => 'updated',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(Collection $collection): void
    {
        $integration = $this->getShopifyIntegration($collection->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            SyncCollectionDeletedToShopifyJob::dispatch($collection->id, $collection->account_id);
        } catch (\Throwable $e) {
            Log::warning('CollectionObserver: failed to dispatch delete sync job', [
                'collection_id' => $collection->id,
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
