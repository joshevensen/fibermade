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
        $status = request()->query('status', 'active');

        $baseQuery = $user->is_admin
            ? Base::with('account')
            : ($user->account_id ? Base::where('account_id', $user->account_id)->with('account') : Base::query()->whereRaw('1 = 0'));

        // Get total count before status filtering
        $totalBases = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bases = $query->get();

        return Inertia::render('creator/bases/BaseIndexPage', [
            'bases' => $bases,
            'totalBases' => $totalBases,
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
            ->map(function ($case) {
                $label = $case->name === 'DK'
                    ? 'DK'
                    : Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name)));

                return [
                    'label' => $label,
                    'value' => $case->value,
                ];
            })
            ->toArray();

        return Inertia::render('creator/bases/BaseCreatePage', [
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
        // Ensure account_id is loaded (refresh if needed)
        $base->refresh();

        $this->authorize('view', $base);

        $baseStatusOptions = collect(BaseStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $weightOptions = collect(Weight::cases())
            ->map(function ($case) {
                $label = $case->name === 'DK'
                    ? 'DK'
                    : Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name)));

                return [
                    'label' => $label,
                    'value' => $case->value,
                ];
            })
            ->toArray();

        return Inertia::render('creator/bases/BaseEditPage', [
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
