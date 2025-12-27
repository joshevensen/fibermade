<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\Collection;
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
            ? Collection::with('account')->get()
            : ($user->account_id ? Collection::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('collections/CollectionIndexPage', [
            'collections' => $collections,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Collection::class);

        return Inertia::render('collections/CollectionCreatePage');
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

        return Inertia::render('collections/CollectionEditPage', [
            'collection' => $collection,
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
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('collections.index');
    }
}
