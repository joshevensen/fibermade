<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Base;
use App\Models\Colorway;
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

class SyncBaseToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Base $base,
        public string $action
    ) {}

    public function handle(): void
    {
        $accountId = $this->base->account_id;
        $integration = $this->getShopifyIntegration($accountId);
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        try {
            match ($this->action) {
                'updated' => $this->handleUpdated($integration),
                'created' => $this->handleCreated($integration),
                default => null,
            };
        } catch (ShopifyApiException $e) {
            \Sentry\captureException($e);
            $integration->flagSyncError();

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Base::class,
                'loggable_id' => $this->base->id,
                'status' => IntegrationLogStatus::Error,
                'message' => "Base sync ({$this->action}) failed: ".$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => "base_{$this->action}",
                    'error' => $e->getMessage(),
                ],
                'synced_at' => now(),
            ]);
        }
    }

    private function handleUpdated(Integration $integration): void
    {
        $shopifySync = $this->shopifySyncFor($integration);

        $inventories = Inventory::where('base_id', $this->base->id)
            ->where('account_id', $this->base->account_id)
            ->with(['colorway', 'base'])
            ->get();

        $grouped = [];
        foreach ($inventories as $inventory) {
            $variantGid = $inventory->getExternalIdFor($integration, 'shopify_variant');
            if (! $variantGid) {
                continue;
            }

            $productGid = $inventory->colorway?->getExternalIdFor($integration, 'shopify_product');
            if (! $productGid) {
                continue;
            }

            $grouped[$productGid][] = [
                'variant_gid' => $variantGid,
                'base' => $inventory->base,
            ];
        }

        $count = 0;
        foreach ($grouped as $productGid => $entries) {
            $shopifySync->updateVariantsBulk($productGid, $entries);
            $count += count($entries);
        }

        if ($count > 0) {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Base::class,
                'loggable_id' => $this->base->id,
                'status' => IntegrationLogStatus::Success,
                'message' => "Updated {$count} Shopify variants for base change",
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'base_updated',
                    'count' => $count,
                ],
                'synced_at' => now(),
            ]);
        }
    }

    private function handleCreated(Integration $integration): void
    {
        $shopifySync = $this->shopifySyncFor($integration);

        $colorways = Colorway::where('account_id', $this->base->account_id)
            ->whereHas('externalIdentifiers', function ($q) use ($integration) {
                $q->where('integration_id', $integration->id)
                    ->where('external_type', 'shopify_product');
            })
            ->get();

        $grouped = [];
        foreach ($colorways as $colorway) {
            $productGid = $colorway->getExternalIdFor($integration, 'shopify_product');
            if (! $productGid) {
                continue;
            }

            $inventory = Inventory::firstOrCreate(
                [
                    'account_id' => $this->base->account_id,
                    'colorway_id' => $colorway->id,
                    'base_id' => $this->base->id,
                ],
                ['quantity' => 0]
            );

            $grouped[$productGid][] = [
                'inventory' => $inventory,
                'base' => $this->base,
                'quantity' => 0,
            ];
        }

        $count = 0;
        foreach ($grouped as $productGid => $entries) {
            $variantMap = $shopifySync->createVariantsBulk($productGid, $entries);
            foreach ($variantMap as $inventoryId => $variantGid) {
                ExternalIdentifier::create([
                    'integration_id' => $integration->id,
                    'identifiable_type' => Inventory::class,
                    'identifiable_id' => $inventoryId,
                    'external_type' => 'shopify_variant',
                    'external_id' => $variantGid,
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Base::class,
                'loggable_id' => $this->base->id,
                'status' => IntegrationLogStatus::Success,
                'message' => "Created variant for new base in {$count} Shopify products",
                'metadata' => [
                    'sync_source' => 'observer',
                    'direction' => 'push',
                    'operation' => 'base_created',
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
                'base' => $this->base->id ?? null,
                'account' => $this->base->account_id ?? null,
                'action' => $this->action,
            ]);

            \Sentry\captureException($exception);
        });
    }

    private function getShopifyIntegration(?int $accountId = null): ?Integration
    {
        $accountId = $accountId ?? $this->base->account_id;

        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }

    private function shopifySyncFor(Integration $integration): ShopifySyncService
    {
        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            throw new \RuntimeException('Shopify integration is not configured.');
        }

        return new ShopifySyncService($client);
    }
}
