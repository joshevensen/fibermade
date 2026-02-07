<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Account::class);

        $user = auth()->user();
        $accounts = $user->is_admin
            ? Account::with('users')->get()
            : ($user->account_id ? Account::with('users')->where('id', $user->account_id)->get() : collect());

        return Inertia::render('creator/accounts/AccountIndexPage', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Account::class);

        return Inertia::render('creator/accounts/AccountCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = Account::create($request->validated());

        // Associate user with account as Owner
        $request->user()->update([
            'account_id' => $account->id,
            'role' => UserRole::Owner->value,
        ]);

        return redirect()->route('accounts.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account): Response
    {
        $this->authorize('view', $account);

        return Inertia::render('creator/accounts/AccountEditPage', [
            'account' => $account->load('users'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->account_id) {
            abort(403, 'User does not have an account.');
        }

        $account = Account::findOrFail($user->account_id);
        $account->update($request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()->route('accounts.index');
    }
}
