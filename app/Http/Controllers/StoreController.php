<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();

        // For creators, show stores they have vendor relationships with
        // For stores, they can see their own store data
        // For admins, show all stores
        if ($user->is_admin) {
            $stores = Store::with('account')->get();
            $totalStores = $stores->count();
        } elseif ($user->account?->type === AccountType::Creator && $user->account->creator) {
            // Get stores that this creator has relationships with
            $stores = $user->account->creator->stores()->with('account')->get();
            $totalStores = $stores->count();
        } elseif ($user->account?->type === AccountType::Store && $user->account_id) {
            // Store users see only their own store
            $stores = Store::where('account_id', $user->account_id)->with('account')->get();
            $totalStores = $stores->count();
        } else {
            $stores = collect();
            $totalStores = 0;
        }

        return Inertia::render('stores/StoreIndexPage', [
            'stores' => $stores,
            'totalStores' => $totalStores,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Note: Store creation should typically happen during registration.
     * This method may need to create both Account and Store records,
     * or handle store creation separately from account creation.
     */
    public function store(StoreStoreRequest $request): RedirectResponse
    {
        // For now, this assumes the account already exists (e.g., from registration)
        // If account doesn't exist, it should be created during registration flow
        Store::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('stores.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store): Response
    {
        $this->authorize('view', $store);

        $store->load(['orders.orderable']);

        return Inertia::render('stores/StoreEditPage', [
            'store' => $store,
            'orders' => $store->orders->map(fn ($order) => [
                'id' => $order->id,
                'order_date' => $order->order_date->toDateString(),
                'status' => $order->status->value,
                'total_amount' => $order->total_amount,
                'orderable' => $order->orderable ? [
                    'name' => $order->orderable->name,
                ] : null,
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return redirect()->route('stores.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return redirect()->route('stores.index');
    }
}
