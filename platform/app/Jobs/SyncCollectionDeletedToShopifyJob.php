<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Collection;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyCollectionPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCollectionDeletedToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $collectionId,
        public readonly int $accountId,
    ) {}

    public function handle(): void
    {
        $integration = Integration::where('account_id', $this->accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();

        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        $identifier = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('identifiable_type', Collection::class)
            ->where('identifiable_id', $this->collectionId)
            ->where('external_type', 'shopify_collection')
            ->first();

        if (! $identifier) {
            return;
        }

        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            return;
        }

        $pushService = new ShopifyCollectionPushService($client);

        try {
            $pushService->deleteCollection($identifier->external_id);
            $identifier->delete();

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Collection::class,
                'loggable_id' => $this->collectionId,
                'status' => IntegrationLogStatus::Success,
                'message' => 'Collection deleted from Shopify',
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'collection_delete',
                ],
                'synced_at' => now(),
            ]);
        } catch (ShopifyApiException $e) {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Collection::class,
                'loggable_id' => $this->collectionId,
                'status' => IntegrationLogStatus::Error,
                'message' => 'Collection delete failed: '.$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'collection_delete',
                    'error' => $e->getMessage(),
                ],
                'synced_at' => now(),
            ]);
        }
    }
}
