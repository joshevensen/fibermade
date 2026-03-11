<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Models\Account;
use App\Services\SubscriptionSyncService;
use Illuminate\Console\Command;

class SubscriptionsSyncCommand extends Command
{
    protected $signature = 'subscriptions:sync';

    protected $description = 'Reconcile subscription_status on Creator accounts from Stripe (manual recovery).';

    public function __construct(
        private readonly SubscriptionSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $accounts = Account::query()
            ->where('type', AccountType::Creator)
            ->whereNotNull('stripe_id')
            ->get();

        foreach ($accounts as $account) {
            if ($this->syncService->syncAccount($account)) {
                $this->line("Updated account {$account->id} to {$account->fresh()->subscription_status->value}");
            }
        }

        $this->info('Sync complete.');

        return self::SUCCESS;
    }
}
