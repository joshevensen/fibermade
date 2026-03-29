<?php

namespace App\Jobs;

use App\Enums\IntegrationLogStatus;
use App\Models\Collection;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\Shopify\ShopifyCollectionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCollectionWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $normalizedCollection  Normalized collection payload (for create/update)
     */
    public function __construct(
        public readonly Integration $integration,
        public readonly string $action,
        public readonly array $normalizedCollection,
    ) {}

    public function handle(ShopifyCollectionSyncService $service): void
    {
        match ($this->action) {
            'create', 'update' => $service->syncCollection($this->normalizedCollection, $this->integration),
            'delete' => $this->deleteCollection(),
            default => null,
        };
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCollectionWebhookJob failed', [
            'integration_id' => $this->integration->id,
            'action' => $this->action,
            'gid' => $this->normalizedCollection['gid'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }

    private function deleteCollection(): void
    {
        $gid = $this->normalizedCollection['gid'] ?? '';
        if (empty($gid)) {
            return;
        }

        $identifier = ExternalIdentifier::where('integration_id', $this->integration->id)
            ->where('external_type', 'shopify_collection')
            ->where('external_id', $gid)
            ->where('identifiable_type', Collection::class)
            ->first();

        if (! $identifier) {
            Log::info('Shopify collection delete webhook: no Fibermade collection found for GID', [
                'integration_id' => $this->integration->id,
                'gid' => $gid,
            ]);

            return;
        }

        $collection = Collection::find($identifier->identifiable_id);
        if (! $collection) {
            return;
        }

        IntegrationLog::create([
            'integration_id' => $this->integration->id,
            'loggable_type' => Collection::class,
            'loggable_id' => $collection->id,
            'status' => IntegrationLogStatus::Success,
            'message' => "Shopify collection deleted — Collection #{$collection->id} '{$collection->name}' removed",
            'metadata' => [
                'sync_source' => 'webhook',
                'shopify_gid' => $gid,
            ],
            'synced_at' => now(),
        ]);

        $collection->delete();
    }
}
