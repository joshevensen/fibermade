<?php

namespace App\Jobs;

use App\Enums\ColorwayStatus;
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
use Sentry\State\Scope;

class SyncColorwayCatalogToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $action = 'updated';

    public function __construct(
        public Colorway $colorway,
        string $action = 'updated',
    ) {
        $this->action = $action;
    }

    public function handle(): void
    {
        $integration = $this->getShopifyIntegration();
        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        if ($this->action === 'created') {
            $this->handleCreated($integration);

            return;
        }

        $this->handleUpdated($integration);
    }

    private function handleCreated(Integration $integration): void
    {
        /** @var InventorySyncService $inventorySyncService */
        $inventorySyncService = app(InventorySyncService::class);
        $inventorySyncService->pushAllInventoryForColorway($this->colorway->fresh(), $integration, 'observer');
    }

    private function handleUpdated(Integration $integration): void
    {
        $productGid = $this->colorway->getExternalIdFor($integration, 'shopify_product');
        if (! $productGid) {
            return;
        }

        $shopifySync = $this->shopifySyncFor($integration);
        $colorway = $this->colorway->fresh();
        $isRetired = $colorway->status === ColorwayStatus::Retired;
        $operation = $isRetired ? 'product_archive' : 'product_update';

        try {
            if ($isRetired) {
                $shopifySync->archiveProduct($productGid);
                $this->logSuccess($integration, 'Archived colorway in Shopify', $operation);
            } else {
                $shopifySync->updateProduct($colorway, $productGid);
                $this->logSuccess($integration, 'Synced colorway catalog to Shopify', $operation);
            }
        } catch (ShopifyApiException $e) {
            \Sentry\captureException($e);
            $integration->flagSyncError();
            $this->logError($integration, $e, $operation);
        }
    }

    private function shopifySyncFor(Integration $integration): ShopifySyncService
    {
        $client = InventorySyncService::createShopifyClient($integration);
        if (! $client) {
            throw new \RuntimeException('Shopify integration is not configured.');
        }

        return new ShopifySyncService($client);
    }

    private function logSuccess(Integration $integration, string $message, string $operation): void
    {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Colorway::class,
            'loggable_id' => $this->colorway->id,
            'status' => IntegrationLogStatus::Success,
            'message' => $message,
            'metadata' => [
                'sync_source' => 'observer',
                'direction' => 'push',
                'operation' => $operation,
            ],
            'synced_at' => now(),
        ]);
    }

    private function logError(Integration $integration, ShopifyApiException $e, string $operation): void
    {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Colorway::class,
            'loggable_id' => $this->colorway->id,
            'status' => IntegrationLogStatus::Error,
            'message' => 'Colorway catalog sync failed: '.$e->getMessage(),
            'metadata' => [
                'sync_source' => 'observer',
                'direction' => 'push',
                'operation' => $operation,
                'error' => $e->getMessage(),
            ],
            'synced_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Sentry\withScope(function (Scope $scope) use ($exception): void {
            $scope->setContext('shopify_sync', [
                'job' => static::class,
                'colorway' => $this->colorway->id ?? null,
                'account' => $this->colorway->account_id ?? null,
                'action' => $this->action,
            ]);

            \Sentry\captureException($exception);
        });
    }

    private function getShopifyIntegration(): ?Integration
    {
        return Integration::where('account_id', $this->colorway->account_id)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }
}
