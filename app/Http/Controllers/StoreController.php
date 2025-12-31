<?php

namespace App\Http\Controllers;

use App\Enums\StoreVendorStatus;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
        $status = request()->query('status', 'active');

        $storeQuery = $user->is_admin
            ? Store::with('account')
            : ($user->account_id ? Store::where('account_id', $user->account_id)->with('account') : Store::query()->whereRaw('1 = 0'));

        // Get total count before status filtering
        $totalStores = (clone $storeQuery)->count();

        $query = clone $storeQuery;
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $stores = $query->get();

        return Inertia::render('stores/StoreIndexPage', [
            'stores' => $stores,
            'totalStores' => $totalStores,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStoreRequest $request): RedirectResponse
    {
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

        $statusOptions = collect(StoreVendorStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('stores/StoreEditPage', [
            'store' => $store,
            'statusOptions' => $statusOptions,
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
