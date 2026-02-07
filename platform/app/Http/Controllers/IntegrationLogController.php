<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationLogRequest;
use App\Http\Requests\UpdateIntegrationLogRequest;
use App\Models\IntegrationLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', IntegrationLog::class);

        $user = auth()->user();
        $integrationLogs = $user->is_admin
            ? IntegrationLog::with('integration.account')->get()
            : ($user->account_id ? IntegrationLog::whereHas('integration', function ($query) use ($user) {
                $query->where('account_id', $user->account_id);
            })->with('integration.account')->get() : collect());

        return Inertia::render('creator/integration-logs/IntegrationLogIndexPage', [
            'integrationLogs' => $integrationLogs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', IntegrationLog::class);

        return Inertia::render('creator/integration-logs/IntegrationLogCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIntegrationLogRequest $request): RedirectResponse
    {
        IntegrationLog::create($request->validated());

        return redirect()->route('integration-logs.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IntegrationLog $integrationLog): Response
    {
        $this->authorize('view', $integrationLog);

        return Inertia::render('creator/integration-logs/IntegrationLogEditPage', [
            'integrationLog' => $integrationLog->load('integration'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIntegrationLogRequest $request, IntegrationLog $integrationLog): RedirectResponse
    {
        $integrationLog->update($request->validated());

        return redirect()->route('integration-logs.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IntegrationLog $integrationLog): RedirectResponse
    {
        $this->authorize('delete', $integrationLog);

        $integrationLog->delete();

        return redirect()->route('integration-logs.index');
    }
}
