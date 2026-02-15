<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Base;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBaseDeletedToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $baseId,
        public int $accountId
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

        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            return;
        }

        $shopifySync = new ShopifySyncService($client);

        $identifiers = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('external_type', 'shopify_variant')
            ->where('identifiable_type', Inventory::class)
            ->whereIn('identifiable_id', function ($q) {
                $q->select('id')
                    ->from('inventories')
                    ->where('base_id', $this->baseId);
            })
            ->get();

        $count = 0;
        foreach ($identifiers as $identifier) {
            try {
                $shopifySync->deleteVariant($identifier->external_id);
                $identifier->delete();
                $count++;
            } catch (ShopifyApiException) {
                // Variant may already be deleted in Shopify
            }
        }

        Inventory::where('base_id', $this->baseId)->delete();

        if ($count > 0) {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Base::class,
                'loggable_id' => $this->baseId,
                'status' => IntegrationLogStatus::Success,
                'message' => "Deleted {$count} Shopify variants for base removal",
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'base_deleted',
                    'count' => $count,
                ],
                'synced_at' => now(),
            ]);
        }
    }
}
