<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Enums\SubscriptionStatus;
use App\Mail\RetentionReminderMail;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class InactiveCreatorRetentionCommand extends Command
{
    protected $signature = 'creators:inactive-retention';

    protected $description = 'Send retention reminders to inactive Creator accounts and hard-delete after 90 days.';

    public function handle(): int
    {
        $accounts = Account::query()
            ->where('type', AccountType::Creator)
            ->where('subscription_status', SubscriptionStatus::Inactive)
            ->with(['users', 'subscriptions'])
            ->get();

        foreach ($accounts as $account) {
            $endsAt = $account->subscriptions()->latest('ends_at')->first()?->ends_at;
            if (! $endsAt) {
                continue;
            }

            $daysSince = (int) $endsAt->diffInDays(now(), false);

            if ($daysSince >= 90) {
                $this->deleteAccountAndRelatedData($account);

                continue;
            }

            $reminderDays = [7, 30, 60, 80];
            if (! in_array($daysSince, $reminderDays, true)) {
                continue;
            }

            $daysRemaining = 90 - $daysSince;
            $primaryUser = $account->users()->first();
            if ($primaryUser) {
                Mail::to($primaryUser->email)->send(new RetentionReminderMail(
                    $primaryUser,
                    $daysSince,
                    $daysRemaining
                ));
            }
        }

        return self::SUCCESS;
    }

    private function deleteAccountAndRelatedData(Account $account): void
    {
        $account->colorways()->delete();
        $account->bases()->delete();
        $account->orders()->delete();
        $account->integrations()->delete();
        $account->users()->delete();
        $account->creator?->delete();
        $account->subscriptions()->delete();
        $account->forceDelete();
    }
}
