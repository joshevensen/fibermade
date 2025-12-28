<?php

namespace App\Http\Controllers;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use App\Http\Requests\StoreBaseRequest;
use App\Http\Requests\UpdateBaseRequest;
use App\Models\Base;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Base::class);

        $user = auth()->user();
        $bases = $user->is_admin
            ? Base::with('account')->get()
            : ($user->account_id ? Base::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('bases/BaseIndexPage', [
            'bases' => $bases,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Base::class);

        $baseStatusOptions = collect(BaseStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $weightOptions = collect(Weight::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('bases/BaseCreatePage', [
            'baseStatusOptions' => $baseStatusOptions,
            'weightOptions' => $weightOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseRequest $request): RedirectResponse
    {
        Base::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('bases.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Base $base): Response
    {
        $this->authorize('view', $base);

        $baseStatusOptions = collect(BaseStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $weightOptions = collect(Weight::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('bases/BaseEditPage', [
            'base' => $base,
            'baseStatusOptions' => $baseStatusOptions,
            'weightOptions' => $weightOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaseRequest $request, Base $base): RedirectResponse
    {
        $base->update($request->validated());

        return redirect()->route('bases.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Base $base): RedirectResponse
    {
        $this->authorize('delete', $base);

        $base->delete();

        return redirect()->route('bases.index');
    }
}
