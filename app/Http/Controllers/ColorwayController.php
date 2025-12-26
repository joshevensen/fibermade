<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreColorwayRequest;
use App\Http\Requests\UpdateColorwayRequest;
use App\Models\Colorway;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ColorwayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Colorway::class);

        $user = auth()->user();
        $colorways = $user->is_admin
            ? Colorway::with('account')->get()
            : Colorway::whereIn('account_id', $user->accounts()->pluck('id'))->with('account')->get();

        return Inertia::render('colorways/ColorwayIndexPage', [
            'colorways' => $colorways,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Colorway::class);

        return Inertia::render('colorways/ColorwayCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColorwayRequest $request): RedirectResponse
    {
        $colorway = Colorway::create($request->validated());
        $colorway->created_by = $request->user()->id;
        $colorway->save();

        return redirect()->route('colorways.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Colorway $colorway): Response
    {
        $this->authorize('view', $colorway);

        return Inertia::render('colorways/ColorwayEditPage', [
            'colorway' => $colorway,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateColorwayRequest $request, Colorway $colorway): RedirectResponse
    {
        $colorway->update($request->validated());
        $colorway->updated_by = $request->user()->id;
        $colorway->save();

        return redirect()->route('colorways.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Colorway $colorway): RedirectResponse
    {
        $this->authorize('delete', $colorway);

        $colorway->delete();

        return redirect()->route('colorways.index');
    }
}
