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
use Sentry\State\Scope;

class PushCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  int[]  $removedColorwayIds
     */
    public function __construct(
        public readonly Collection $collection,
        public readonly string $action,
        public readonly array $removedColorwayIds = [],
    ) {}

    public function handle(): void
    {
        $integration = $this->getShopifyIntegration($this->collection->account_id);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        $pushService = $this->collectionPushServiceFor($integration);

        try {
            match ($this->action) {
                'created' => $this->handleCreated($pushService, $integration),
                'updated' => $this->handleUpdated($pushService, $integration),
                default => null,
            };
        } catch (ShopifyApiException $e) {
            $integration->handleSyncException($e);

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Collection::class,
                'loggable_id' => $this->collection->id,
                'status' => IntegrationLogStatus::Error,
                'message' => "Collection sync ({$this->action}) failed: ".$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => "collection_{$this->action}",
                    'error' => $e->getMessage(),
                ],
                'synced_at' => now(),
            ]);
        }
    }

    private function handleCreated(ShopifyCollectionPushService $pushService, Integration $integration): void
    {
        // If a mapping already exists (e.g. collection was pulled from Shopify), treat as update.
        $existingGid = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('identifiable_type', Collection::class)
            ->where('identifiable_id', $this->collection->id)
            ->where('external_type', 'shopify_collection')
            ->value('external_id');

        if ($existingGid) {
            $pushService->updateCollection($this->collection, $existingGid);
            $pushService->syncCollectionProducts($this->collection, $existingGid, $integration, $this->removedColorwayIds);

            return;
        }

        $collectionGid = $pushService->createCollection($this->collection, $integration);
        $pushService->syncCollectionProducts($this->collection, $collectionGid, $integration, $this->removedColorwayIds);

        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Collection::class,
            'loggable_id' => $this->collection->id,
            'status' => IntegrationLogStatus::Success,
            'message' => 'Collection created in Shopify',
            'metadata' => [
                'sync_source' => 'observer',
                'direction' => 'push',
                'operation' => 'collection_create',
            ],
            'synced_at' => now(),
        ]);
    }

    private function handleUpdated(ShopifyCollectionPushService $pushService, Integration $integration): void
    {
        $collectionGid = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('identifiable_type', Collection::class)
            ->where('identifiable_id', $this->collection->id)
            ->where('external_type', 'shopify_collection')
            ->value('external_id');

        if (! $collectionGid) {
            // No mapping exists — create it now
            $this->handleCreated($pushService, $integration);

            return;
        }

        $pushService->updateCollection($this->collection, $collectionGid);
        $pushService->syncCollectionProducts($this->collection, $collectionGid, $integration, $this->removedColorwayIds);

        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Collection::class,
            'loggable_id' => $this->collection->id,
            'status' => IntegrationLogStatus::Success,
            'message' => 'Collection updated in Shopify',
            'metadata' => [
                'sync_source' => 'observer',
                'direction' => 'push',
                'operation' => 'collection_update',
            ],
            'synced_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Sentry\withScope(function (Scope $scope) use ($exception): void {
            $scope->setContext('shopify_sync', [
                'job' => static::class,
                'collection' => $this->collection->id ?? null,
                'account' => $this->collection->account_id ?? null,
                'action' => $this->action,
            ]);

            \Sentry\captureException($exception);
        });
    }

    private function getShopifyIntegration(int $accountId): ?Integration
    {
        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }

    private function collectionPushServiceFor(Integration $integration): ShopifyCollectionPushService
    {
        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            throw new \RuntimeException('Shopify integration is not configured.');
        }

        return new ShopifyCollectionPushService($client);
    }
}
