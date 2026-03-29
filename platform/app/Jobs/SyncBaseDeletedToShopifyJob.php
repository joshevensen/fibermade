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
use Sentry\State\Scope;

class SyncBaseDeletedToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

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

        $inventories = Inventory::where('base_id', $this->baseId)
            ->with('colorway')
            ->get();

        $identifiers = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('external_type', 'shopify_variant')
            ->where('identifiable_type', Inventory::class)
            ->whereIn('identifiable_id', $inventories->pluck('id'))
            ->get()
            ->keyBy('identifiable_id');

        $grouped = [];
        foreach ($inventories as $inventory) {
            $identifier = $identifiers->get($inventory->id);
            if (! $identifier) {
                continue;
            }

            $productGid = $inventory->colorway?->getExternalIdFor($integration, 'shopify_product');
            if (! $productGid) {
                continue;
            }

            $grouped[$productGid][] = [
                'variant_gid' => $identifier->external_id,
                'identifier_id' => $identifier->id,
            ];
        }

        $count = 0;
        foreach ($grouped as $productGid => $entries) {
            $variantGids = array_column($entries, 'variant_gid');
            $identifierIds = array_column($entries, 'identifier_id');

            try {
                $shopifySync->deleteVariantsBulk($productGid, $variantGids);
                ExternalIdentifier::whereIn('id', $identifierIds)->delete();
                $count += count($variantGids);
            } catch (ShopifyApiException $e) {
                $integration->handleSyncException($e);
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

    public function failed(\Throwable $exception): void
    {
        \Sentry\withScope(function (Scope $scope) use ($exception): void {
            $scope->setContext('shopify_sync', [
                'job' => static::class,
                'base_id' => $this->baseId,
                'account' => $this->accountId,
            ]);

            \Sentry\captureException($exception);
        });
    }
}
