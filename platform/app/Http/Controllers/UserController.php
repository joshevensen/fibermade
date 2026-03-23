<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Account;
use App\Models\Dye;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the user's settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $account = null;
        $dyes = collect();
        $nextBillingDate = null;

        $business = null;

        if ($user->account_id) {
            $account = Account::with('users')->find($user->account_id);
            $dyes = Dye::where('account_id', $user->account_id)->get();

            if ($account) {
                $business = $account->type === AccountType::Creator
                    ? $account->creator
                    : $account->store;

                if ($account->type === AccountType::Creator
                    && $account->subscription_status === SubscriptionStatus::Active
                ) {
                    $subscription = $account->subscription();
                    if ($subscription) {
                        $periodEnd = $subscription->currentPeriodEnd();
                        $nextBillingDate = $periodEnd?->format('Y-m-d');
                    }
                }
            }
        }

        $shopify = $this->buildShopifyProp($user->account_id);

        $routeName = $request->route()->getName();
        $page = $routeName === 'store.settings'
            ? 'store/settings/SettingsPage'
            : 'creator/settings/SettingsPage';

        return Inertia::render($page, [
            'status' => $request->session()->get('status'),
            'business' => $business,
            'dyes' => $dyes,
            'next_billing_date' => $nextBillingDate,
            'shopify' => $shopify,
        ]);
    }

    /**
     * Build the shopify integration state prop for the settings page.
     *
     * @return array<string, mixed>|null
     */
    /**
     * Reset the creator's Shopify connect token.
     */
    public function resetConnectToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->account_id) {
            abort(403, 'User does not have an account.');
        }

        $account = Account::findOrFail($user->account_id);

        $this->authorize('update', $account);

        $account->generateConnectToken();

        return response()->json(['connect_token' => $account->shopify_connect_token]);
    }

    /**
     * Build the shopify integration state prop for the settings page.
     *
     * @return array<string, mixed>|null
     */
    private function buildShopifyProp(?int $accountId): ?array
    {
        if (! $accountId) {
            return null;
        }

        $account = Account::find($accountId);

        $integration = Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();

        if (! $integration) {
            return [
                'connected' => false,
                'shop' => null,
                'connected_since' => null,
                'auto_sync' => false,
                'sync' => ['status' => 'idle'],
                'recent_errors' => [],
                'connect_token' => $account?->shopify_connect_token,
            ];
        }

        $settings = $integration->settings ?? [];

        $recentErrors = $integration->logs()
            ->where('status', IntegrationLogStatus::Error)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (object $log) => [
                'id' => $log->id,
                'message' => $log->message,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->toArray();

        return [
            'connected' => true,
            'shop' => $settings['shop'] ?? null,
            'connected_since' => $integration->created_at?->toDateString(),
            'auto_sync' => $settings['auto_sync'] ?? false,
            'sync' => $settings['sync'] ?? ['status' => 'idle'],
            'push_sync' => $settings['push_sync'] ?? ['status' => 'idle'],
            'recent_errors' => $recentErrors,
            'connect_token' => $account?->shopify_connect_token,
        ];
    }

    /**
     * Update the user's information.
     */
    public function update(UpdateUserRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return to_route('user.edit');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back();
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Delete the user's account (not the user).
     */
    public function destroyAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->account_id) {
            abort(403, 'User does not have an account.');
        }

        $account = $user->account;

        $this->authorize('delete', $account);

        Auth::logout();

        $account->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
