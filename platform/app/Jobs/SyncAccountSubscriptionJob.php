<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\SubscriptionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAccountSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Account $account
    ) {}

    public function handle(SubscriptionSyncService $syncService): void
    {
        $syncService->syncAccount($this->account);
    }
}
