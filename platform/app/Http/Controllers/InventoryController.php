<?php

namespace App\Http\Controllers;

use App\Enums\BaseStatus;
use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryQuantityRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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

        // Fetch colorways with same filtering/authorization as ColorwayController
        $colorways = $user->is_admin
            ? Colorway::with(['account', 'media', 'collections', 'inventories.base', 'inventories.externalIdentifiers.integration'])->get()
            : ($user->account_id
                ? Colorway::where('account_id', $user->account_id)
                    ->with(['account', 'media', 'collections', 'inventories.base', 'inventories.externalIdentifiers.integration'])
                    ->get()
                : collect());

        // Fetch all active bases for the account (ordered by code)
        $bases = $user->is_admin
            ? Base::where('status', BaseStatus::Active)->orderBy('code')->get()
            : ($user->account_id
                ? Base::where('account_id', $user->account_id)
                    ->where('status', BaseStatus::Active)
                    ->orderBy('code')
                    ->get()
                : collect());

        // Transform colorways to include bases with inventory data
        $colorways = $colorways->map(function ($colorway) use ($bases) {
            $colorwayArray = $colorway->toArray();
            $colorwayArray['primary_image_url'] = $colorway->primary_image_url;
            $colorwayArray['collections'] = $colorway->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
            ])->toArray();

            // Map bases with their inventory quantities
            $baseData = $bases->map(function ($base) use ($colorway) {
                $inventory = $colorway->inventories->firstWhere('base_id', $base->id);

                $baseArray = [
                    'id' => $base->id,
                    'code' => $base->code,
                    'descriptor' => $base->descriptor,
                    'quantity' => $inventory ? $inventory->quantity : 0,
                    'inventory_id' => $inventory ? $inventory->id : null,
                ];

                if ($inventory) {
                    $baseArray['external_identifiers'] = $inventory->externalIdentifiers->map(fn ($identifier) => [
                        'integration_type' => $identifier->integration->type->value,
                        'external_type' => $identifier->external_type,
                        'external_id' => $identifier->external_id,
                        'data' => $identifier->data,
                    ])->toArray();
                } else {
                    $baseArray['external_identifiers'] = [];
                }

                return $baseArray;
            })->toArray();

            $colorwayArray['bases'] = $baseData;

            // Calculate total quantity for this colorway
            $colorwayArray['total_quantity'] = collect($baseData)->sum('quantity');

            return $colorwayArray;
        });

        // Filter options (same as ColorwayController)
        $colorwayStatusOptions = collect(ColorwayStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $techniqueOptions = collect(Technique::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $colorOptions = collect(Color::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $collections = $user->is_admin
            ? Collection::orderBy('name')->get()
            : ($user->account_id
                ? Collection::where('account_id', $user->account_id)->orderBy('name')->get()
                : collect());

        $collectionOptions = $collections->map(fn ($collection) => [
            'label' => $collection->name,
            'value' => $collection->id,
        ])->toArray();

        return Inertia::render('creator/inventory/InventoryIndexPage', [
            'colorways' => $colorways,
            'colorwayStatusOptions' => $colorwayStatusOptions,
            'techniqueOptions' => $techniqueOptions,
            'colorOptions' => $colorOptions,
            'collectionOptions' => $collectionOptions,
        ]);
    }

    /**
     * Update inventory quantity for a colorway-base combination.
     */
    public function updateQuantity(UpdateInventoryQuantityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        Inventory::updateOrCreate(
            [
                'account_id' => $user->account_id,
                'colorway_id' => $validated['colorway_id'],
                'base_id' => $validated['base_id'],
            ],
            [
                'quantity' => $validated['quantity'],
            ]
        );

        return redirect()->back();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Inventory::class);

        return Inertia::render('creator/inventory/InventoryCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        Inventory::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('inventory.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory): Response
    {
        $this->authorize('view', $inventory);

        $inventory->load(['colorway', 'base', 'externalIdentifiers.integration']);
        $inventoryArray = $inventory->toArray();
        $inventoryArray['external_identifiers'] = $inventory->externalIdentifiers->map(fn ($identifier) => [
            'integration_type' => $identifier->integration->type->value,
            'external_type' => $identifier->external_type,
            'external_id' => $identifier->external_id,
            'data' => $identifier->data,
        ])->toArray();

        return Inertia::render('creator/inventory/InventoryEditPage', [
            'inventory' => $inventoryArray,
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
