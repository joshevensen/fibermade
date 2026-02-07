<?php

namespace App\Http\Controllers;

use App\Enums\BaseStatus;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionColorwaysRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Colorway;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Collection::class);

        $user = auth()->user();
        $collections = $user->is_admin
            ? Collection::with('account')->withCount('colorways')->get()
            : ($user->account_id ? Collection::where('account_id', $user->account_id)->with('account')->withCount('colorways')->get() : collect());

        $statusOptions = [
            ['label' => 'Active', 'value' => BaseStatus::Active->value],
            ['label' => 'Retired', 'value' => BaseStatus::Retired->value],
        ];

        return Inertia::render('creator/collections/CollectionIndexPage', [
            'collections' => $collections,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Collection::class);

        return Inertia::render('creator/collections/CollectionCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        Collection::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('collections.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Collection $collection): Response
    {
        $this->authorize('view', $collection);

        $collection->load('colorways');

        $user = auth()->user();
        $allColorways = $user->is_admin
            ? Colorway::orderBy('name')->get()
            : ($user->account_id ? Colorway::where('account_id', $user->account_id)->orderBy('name')->get() : collect());

        return Inertia::render('creator/collections/CollectionEditPage', [
            'collection' => $collection,
            'colorways' => $collection->colorways,
            'allColorways' => $allColorways,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $collection->update($request->validated());

        return redirect()->route('collections.index');
    }

    /**
     * Update the colorways for the specified collection.
     */
    public function updateColorways(UpdateCollectionColorwaysRequest $request, Collection $collection): RedirectResponse
    {
        $collection->colorways()->sync($request->validated()['colorway_ids']);

        return redirect()->route('collections.edit', $collection)->with('success', 'Colorways updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('collections.index');
    }
}
