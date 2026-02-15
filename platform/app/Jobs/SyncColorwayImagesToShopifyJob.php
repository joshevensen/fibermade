<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncColorwayImagesToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Colorway $colorway
    ) {}

    public function handle(): void
    {
        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return;
        }

        $integration = Integration::where('account_id', $this->colorway->account_id)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();

        if (! $integration) {
            return;
        }

        $productGid = $this->colorway->getExternalIdFor($integration, 'shopify_product');
        if (! $productGid) {
            return;
        }

        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            return;
        }

        try {
            $shopifySync = new ShopifySyncService($client);
            $shopifySync->syncImages($this->colorway->fresh(), $productGid);

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Colorway::class,
                'loggable_id' => $this->colorway->id,
                'status' => IntegrationLogStatus::Success,
                'message' => 'Synced colorway images to Shopify',
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'image_sync',
                ],
                'synced_at' => now(),
            ]);
        } catch (ShopifyApiException $e) {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Colorway::class,
                'loggable_id' => $this->colorway->id,
                'status' => IntegrationLogStatus::Error,
                'message' => 'Colorway image sync failed: '.$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'image_sync',
                    'error' => $e->getMessage(),
                ],
                'synced_at' => now(),
            ]);
        }
    }
}
