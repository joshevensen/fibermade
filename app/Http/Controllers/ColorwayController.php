<?php

namespace App\Http\Controllers;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Http\Requests\StoreColorwayRequest;
use App\Http\Requests\UpdateColorwayRequest;
use App\Models\Colorway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
            : ($user->account_id ? Colorway::where('account_id', $user->account_id)->with('account')->get() : collect());

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

        return Inertia::render('colorways/ColorwayCreatePage', [
            'colorwayStatusOptions' => $colorwayStatusOptions,
            'techniqueOptions' => $techniqueOptions,
            'colorOptions' => $colorOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColorwayRequest $request): RedirectResponse
    {
        $colorway = Colorway::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
            'created_by' => $request->user()->id,
        ]);

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
