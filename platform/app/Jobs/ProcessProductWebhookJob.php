<?php

namespace App\Jobs;

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationLogStatus;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\Shopify\ShopifyProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProductWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $normalizedProduct  Normalized product payload (for create/update)
     */
    public function __construct(
        public readonly Integration $integration,
        public readonly string $action,
        public readonly array $normalizedProduct,
    ) {}

    public function handle(ShopifyProductSyncService $service): void
    {
        match ($this->action) {
            'create', 'update' => $service->syncProduct($this->normalizedProduct, $this->integration),
            'delete' => $this->retireColorway(),
            default => null,
        };
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessProductWebhookJob failed', [
            'integration_id' => $this->integration->id,
            'action' => $this->action,
            'gid' => $this->normalizedProduct['gid'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }

    private function retireColorway(): void
    {
        $gid = $this->normalizedProduct['gid'] ?? '';
        if (empty($gid)) {
            return;
        }

        $identifier = ExternalIdentifier::where('integration_id', $this->integration->id)
            ->where('external_type', 'shopify_product')
            ->where('external_id', $gid)
            ->where('identifiable_type', Colorway::class)
            ->first();

        if (! $identifier) {
            Log::info('Shopify product delete webhook: no Fibermade colorway found for GID', [
                'integration_id' => $this->integration->id,
                'gid' => $gid,
            ]);

            return;
        }

        $colorway = Colorway::find($identifier->identifiable_id);
        if (! $colorway) {
            return;
        }

        $colorway->update(['status' => ColorwayStatus::Retired]);

        IntegrationLog::create([
            'integration_id' => $this->integration->id,
            'loggable_type' => Colorway::class,
            'loggable_id' => $colorway->id,
            'status' => IntegrationLogStatus::Success,
            'message' => "Shopify product deleted — Colorway #{$colorway->id} '{$colorway->name}' retired",
            'metadata' => [
                'sync_source' => 'webhook',
                'shopify_gid' => $gid,
            ],
            'synced_at' => now(),
        ]);
    }
}
