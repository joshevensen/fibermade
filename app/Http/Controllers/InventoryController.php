<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Inventory::class);

        $user = auth()->user();
        $inventories = $user->is_admin
            ? Inventory::with(['account', 'colorway', 'base'])->get()
            : Inventory::whereIn('account_id', $user->accounts()->pluck('id'))->with(['account', 'colorway', 'base'])->get();

        return Inertia::render('inventory/InventoryPage', [
            'inventories' => $inventories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Inventory::class);

        return Inertia::render('inventory/InventoryCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        Inventory::create($request->validated());

        return redirect()->route('inventory.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory): Response
    {
        $this->authorize('view', $inventory);

        return Inertia::render('inventory/InventoryEditPage', [
            'inventory' => $inventory->load(['colorway', 'base']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryRequest $request, Inventory $inventory): RedirectResponse
    {
        $inventory->update($request->validated());

        return redirect()->route('inventory.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory): RedirectResponse
    {
        $this->authorize('delete', $inventory);

        $inventory->delete();

        return redirect()->route('inventory.index');
    }
}
