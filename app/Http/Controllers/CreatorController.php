<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\StoreCreatorRequest;
use App\Http\Requests\UpdateCreatorRequest;
use App\Models\Creator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CreatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Creator::class);

        $user = auth()->user();

        // Creators typically see only their own creator record
        // Admins can see all creators
        if ($user->is_admin) {
            $creators = Creator::with('account')->get();
        } elseif ($user->account?->type === AccountType::Creator && $user->account_id) {
            $creators = Creator::where('account_id', $user->account_id)->with('account')->get();
        } else {
            $creators = collect();
        }

        return Inertia::render('creator/creators/CreatorIndexPage', [
            'creators' => $creators,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Creator::class);

        return Inertia::render('creator/creators/CreatorCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreatorRequest $request): RedirectResponse
    {
        // Creator should already have an account (created during registration)
        // This creates/updates the creator record for the existing account
        Creator::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('creators.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Creator $creator): Response
    {
        $this->authorize('view', $creator);

        $creator->load('account');

        return Inertia::render('creator/creators/CreatorShowPage', [
            'creator' => $creator,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Creator $creator): Response
    {
        $this->authorize('update', $creator);

        return Inertia::render('creator/creators/CreatorEditPage', [
            'creator' => $creator,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCreatorRequest $request, Creator $creator): RedirectResponse
    {
        $creator->update($request->validated());

        return redirect()->route('creators.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Creator $creator): RedirectResponse
    {
        $this->authorize('delete', $creator);

        $creator->delete();

        return redirect()->route('creators.index');
    }
}
