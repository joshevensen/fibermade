<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\InviteType;
use App\Enums\UserRole;
use App\Http\Requests\AcceptStoreInviteRequest;
use App\Http\Requests\StoreInviteRequest;
use App\Mail\StoreInviteMail;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class InviteController extends Controller
{
    public function store(StoreInviteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $type = $validated['type'] instanceof InviteType
            ? $validated['type']
            : InviteType::from($validated['type']);

        if ($type !== InviteType::Store) {
            abort(422, 'Invite type not supported.');
        }

        $creator = $request->user()->account?->creator;
        if (! $creator) {
            abort(403, 'Creator account required.');
        }

        $email = $validated['email'];
        $pendingExists = Invite::pending()
            ->where('email', $email)
            ->where('inviter_type', $creator->getMorphClass())
            ->where('inviter_id', $creator->id)
            ->where('invite_type', InviteType::Store)
            ->exists();

        if ($pendingExists) {
            return back()->withErrors(['email' => 'A pending invite already exists for this email.']);
        }

        $invite = Invite::create([
            'invite_type' => InviteType::Store,
            'email' => $email,
            'inviter_type' => $creator->getMorphClass(),
            'inviter_id' => $creator->id,
            'metadata' => [
                'store_name' => $validated['store_name'] ?? null,
                'owner_name' => $validated['owner_name'] ?? null,
            ],
        ]);

        Log::info('Store invite: preparing to send email', [
            'invite_id' => $invite->id,
            'email' => $email,
            'creator_id' => $creator->id,
            'queued' => true,
            'queue_driver' => config('queue.default'),
        ]);

        try {
            Mail::to($email)->send(new StoreInviteMail(
                email: $email,
                creatorName: $creator->name,
                inviteToken: $invite->token,
                inviteMetadata: $invite->metadata ?? [],
            ));
            Log::info('Store invite: email dispatched successfully', [
                'invite_id' => $invite->id,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Store invite: failed to send email', [
                'invite_id' => $invite->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return back()->with('success', 'Invite sent.');
    }

    public function accept(string $token): RedirectResponse|Response
    {
        $invite = Invite::where('token', $token)->firstOrFail();

        if (! $invite->isPending()) {
            return redirect()->route('home')->with('error', 'This invite has expired or was already used.');
        }

        $invite->load('inviter');
        $meta = $invite->metadata ?? [];

        return Inertia::render('store/invites/AcceptInvitePage', [
            'token' => $invite->token,
            'invite' => [
                'store_name' => $meta['store_name'] ?? '',
                'owner_name' => $meta['owner_name'] ?? '',
                'email' => $invite->email,
            ],
            'creator_name' => $invite->inviter->name,
        ]);
    }

    public function acceptStore(AcceptStoreInviteRequest $request, string $token): RedirectResponse
    {
        $invite = Invite::where('token', $token)->firstOrFail();
        $validated = $request->validated();

        if (! $invite->isPending()) {
            return redirect()
                ->route('invites.accept', ['token' => $token])
                ->withErrors(['invite' => 'This invite has expired or was already used.']);
        }

        $user = DB::transaction(function () use ($invite, $validated) {
            $account = Account::create([
                'status' => BaseStatus::Active,
                'type' => AccountType::Store,
            ]);

            $store = Store::create([
                'account_id' => $account->id,
                'name' => $validated['store_name'],
                'email' => $validated['email'],
                'owner_name' => $validated['owner_name'] ?? null,
                'address_line1' => $validated['address_line1'],
                'address_line2' => null,
                'city' => $validated['city'],
                'state_region' => $validated['state_region'],
                'postal_code' => $validated['postal_code'],
                'country_code' => 'US',
            ]);

            $user = User::create([
                'name' => $validated['owner_name'] ?? $validated['email'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'account_id' => $account->id,
                'role' => UserRole::Owner,
                'terms_accepted_at' => $validated['terms_accepted'] ? now() : null,
                'privacy_accepted_at' => $validated['privacy_accepted'] ? now() : null,
                'marketing_opt_in' => false,
            ]);

            $user->update(['email_verified_at' => now()]);

            /** @var Creator $creator */
            $creator = $invite->inviter;
            $creator->stores()->attach($store->id, ['status' => 'active']);

            $invite->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('store.home');
    }

    public function resend(Invite $invite): RedirectResponse
    {
        $this->authorize('resend', $invite);

        $creator = $invite->inviter;
        $email = $invite->email;

        $invite->update(['expires_at' => now()->addDays(7)]);

        Mail::to($email)->send(new StoreInviteMail(
            email: $email,
            creatorName: $creator->name,
            inviteToken: $invite->token,
            inviteMetadata: $invite->metadata ?? [],
        ));

        return back()->with('success', 'Invite resent.');
    }
}
