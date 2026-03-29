<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use App\Enums\IntegrationType;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Integration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->hasSession() ? $request->user() : null;

        $shopifyIntegration = null;
        $hasSyncErrors = false;

        if ($user?->account) {
            $shopifyIntegration = Integration::where('account_id', $user->account->id)
                ->where('type', IntegrationType::Shopify)
                ->where('active', true)
                ->first();

            $hasSyncErrors = (bool) ($shopifyIntegration?->settings['has_sync_errors'] ?? false);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'upload_max_filesize' => UploadedFile::getMaxFilesize(),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user?->load('account'),
                'account_type' => $user?->account?->type,
            ],
            'account' => $user?->account ? $this->sharedAccountProps($user->account) : null,
            'flash' => [
                'success' => $request->hasSession() ? $request->session()->get('success') : null,
                'error' => $request->hasSession() ? $request->session()->get('error') : null,
            ],
            'shopify' => [
                'has_sync_errors' => $hasSyncErrors,
                'integration_id' => $shopifyIntegration?->id,
            ],
        ];
    }

    /**
     * Shared account props for layouts (type, subscription_status, reactivation_days_remaining when inactive).
     *
     * @return array{type: AccountType, subscription_status: SubscriptionStatus|null, reactivation_days_remaining: int|null}
     */
    private function sharedAccountProps(Account $account): array
    {
        $props = [
            'type' => $account->type,
            'subscription_status' => $account->subscription_status,
            'reactivation_days_remaining' => null,
        ];

        if ($account->type === AccountType::Creator
            && $account->subscription_status === SubscriptionStatus::Inactive
        ) {
            $endsAt = $account->subscriptions()->latest('ends_at')->first()?->ends_at;
            if ($endsAt) {
                $daysSince = (int) $endsAt->diffInDays(now(), false);
                $props['reactivation_days_remaining'] = max(0, 90 - $daysSince);
            }
        }

        return $props;
    }
}
