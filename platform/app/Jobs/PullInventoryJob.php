<?php

namespace App\Jobs;

use App\Data\Shopify\SyncResult;
use App\Models\Integration;
use App\Services\InventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PullInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Integration $integration
    ) {}

    public function handle(InventorySyncService $service): void
    {
        $this->updateCurrentStep('inventory');
        $result = $service->syncAll($this->integration);
        $this->writeSyncResult('inventory', $result);
    }

    public function failed(Throwable $e): void
    {
        $this->markSyncFailed();
    }

    private function updateCurrentStep(string $step): void
    {
        $this->integration->refresh();
        $settings = $this->integration->settings ?? [];
        $settings['sync'] = array_merge($settings['sync'] ?? [], ['current_step' => $step]);
        $this->integration->update(['settings' => $settings]);
    }

    private function writeSyncResult(string $step, SyncResult $result): void
    {
        $this->integration->refresh();
        $settings = $this->integration->settings ?? [];
        $sync = $settings['sync'] ?? [];

        $sync['last_result'] = $sync['last_result'] ?? [];
        $sync['last_result'][$step] = [
            'updated' => $result->updated,
            'failed' => $result->failed,
        ];

        $sync['errors'] = $sync['errors'] ?? [];
        foreach ($result->errors as $error) {
            $sync['errors'][] = array_merge(['step' => $step], $error);
        }

        $sync['status'] = 'complete';
        $sync['completed_at'] = now()->toIso8601String();
        $settings['sync'] = $sync;

        $this->integration->update(['settings' => $settings]);
    }

    private function markSyncFailed(): void
    {
        $this->integration->refresh();
        $settings = $this->integration->settings ?? [];
        $sync = $settings['sync'] ?? [];
        $sync['status'] = 'failed';
        $sync['completed_at'] = now()->toIso8601String();
        $settings['sync'] = $sync;
        $this->integration->update(['settings' => $settings]);
    }
}
