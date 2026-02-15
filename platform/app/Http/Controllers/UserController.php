<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Account;
use App\Models\Dye;
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

        if ($user->account_id) {
            $account = Account::with('users')->find($user->account_id);
            $dyes = Dye::where('account_id', $user->account_id)->get();

            if ($account
                && $account->type === AccountType::Creator
                && $account->subscription_status === SubscriptionStatus::Active
            ) {
                $subscription = $account->subscription();
                if ($subscription) {
                    $periodEnd = $subscription->currentPeriodEnd();
                    $nextBillingDate = $periodEnd?->format('Y-m-d');
                }
            }
        }

        $routeName = $request->route()->getName();
        $page = $routeName === 'store.settings'
            ? 'store/settings/SettingsPage'
            : 'creator/settings/SettingsPage';

        return Inertia::render($page, [
            'status' => $request->session()->get('status'),
            'account' => $account,
            'dyes' => $dyes,
            'next_billing_date' => $nextBillingDate,
        ]);
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
