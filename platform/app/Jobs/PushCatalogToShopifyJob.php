<?php

namespace App\Jobs;

use App\Enums\IntegrationType;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyCollectionPushService;
use App\Services\Shopify\ShopifyTokenExpiredException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PushCatalogToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public readonly int $integrationId,
        public readonly bool $includeInventory = true,
    ) {}

    public function handle(InventorySyncService $inventorySync): void
    {
        $integration = Integration::with('account')->find($this->integrationId);

        if (! $integration) {
            return;
        }

        $this->updatePushSyncState($integration, 'running', 'colorways');

        $colorwayResult = $this->pushColorways($integration, $inventorySync);

        $this->updatePushSyncState($integration, 'running', 'collections');

        $collectionResult = $this->pushCollections($integration);

        $settings = $integration->fresh()->settings ?? [];
        $settings['push_sync'] = array_merge($settings['push_sync'] ?? [], [
            'status' => 'complete',
            'current_step' => null,
            'completed_at' => Carbon::now()->toIso8601String(),
            'last_result' => [
                'colorways' => $colorwayResult,
                'collections' => $collectionResult,
            ],
        ]);
        $integration->update(['settings' => $settings]);
    }

    public function failed(\Throwable $e): void
    {
        $integration = Integration::find($this->integrationId);
        if (! $integration) {
            return;
        }

        $settings = $integration->settings ?? [];
        $settings['push_sync'] = array_merge($settings['push_sync'] ?? [], [
            'status' => 'failed',
            'current_step' => null,
            'completed_at' => Carbon::now()->toIso8601String(),
        ]);
        $integration->update(['settings' => $settings]);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    private function pushColorways(Integration $integration, InventorySyncService $inventorySync): array
    {
        $colorways = Colorway::where('account_id', $integration->account_id)->get();

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($colorways as $colorway) {
            try {
                $result = $inventorySync->pushAllInventoryForColorway($colorway, $integration, 'full_catalog_push');

                if ($result['products_created'] > 0) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (ShopifyTokenExpiredException $e) {
                $integration->handleSyncException($e);
                throw $e;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    private function pushCollections(Integration $integration): array
    {
        $collections = Collection::where('account_id', $integration->account_id)->get();

        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            return ['created' => 0, 'updated' => 0, 'failed' => 0];
        }

        $pushService = new ShopifyCollectionPushService($client);

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($collections as $collection) {
            try {
                $collectionGid = ExternalIdentifier::where('integration_id', $integration->id)
                    ->where('identifiable_type', Collection::class)
                    ->where('identifiable_id', $collection->id)
                    ->where('external_type', 'shopify_collection')
                    ->value('external_id');

                if ($collectionGid) {
                    $pushService->updateCollection($collection, $collectionGid);
                    $pushService->syncCollectionProducts($collection, $collectionGid, $integration);
                    $updated++;
                } else {
                    $collectionGid = $pushService->createCollection($collection, $integration);
                    $pushService->syncCollectionProducts($collection, $collectionGid, $integration);
                    $created++;
                }
            } catch (ShopifyTokenExpiredException $e) {
                $integration->handleSyncException($e);
                throw $e;
            } catch (ShopifyApiException) {
                $failed++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
    }

    private function updatePushSyncState(Integration $integration, string $status, ?string $currentStep): void
    {
        $settings = $integration->settings ?? [];
        $existing = $settings['push_sync'] ?? [];
        $settings['push_sync'] = array_merge($existing, [
            'status' => $status,
            'current_step' => $currentStep,
            'started_at' => $existing['started_at'] ?? Carbon::now()->toIso8601String(),
            'completed_at' => null,
        ]);
        $integration->update(['settings' => $settings]);
        $integration->refresh();
    }

    private function getShopifyIntegration(int $accountId): ?Integration
    {
        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }
}
