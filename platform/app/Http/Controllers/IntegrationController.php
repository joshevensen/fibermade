<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationRequest;
use App\Http\Requests\UpdateIntegrationRequest;
use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Integration::class);

        $user = auth()->user();
        $integrations = $user->is_admin
            ? Integration::with('account')->get()
            : ($user->account_id ? Integration::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('creator/integrations/IntegrationIndexPage', [
            'integrations' => $integrations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Integration::class);

        return Inertia::render('creator/integrations/IntegrationCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIntegrationRequest $request): RedirectResponse
    {
        Integration::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('integrations.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Integration $integration): Response
    {
        $this->authorize('view', $integration);

        return Inertia::render('creator/integrations/IntegrationEditPage', [
            'integration' => $integration,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIntegrationRequest $request, Integration $integration): RedirectResponse
    {
        $integration->update($request->validated());

        return redirect()->route('integrations.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Integration $integration): RedirectResponse
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return redirect()->route('integrations.index');
    }
}
