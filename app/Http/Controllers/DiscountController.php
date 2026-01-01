<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Models\Discount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Discount::class);

        $user = auth()->user();
        $discounts = $user->is_admin
            ? Discount::with(['account', 'externalIdentifiers.integration'])->get()
            : ($user->account_id ? Discount::where('account_id', $user->account_id)->with(['account', 'externalIdentifiers.integration'])->get() : collect());

        $discounts = $discounts->map(function ($discount) {
            $discountArray = $discount->toArray();
            $discountArray['external_identifiers'] = $discount->externalIdentifiers->map(fn ($identifier) => [
                'integration_type' => $identifier->integration->type->value,
                'external_type' => $identifier->external_type,
                'external_id' => $identifier->external_id,
                'data' => $identifier->data,
            ])->toArray();

            return $discountArray;
        });

        return Inertia::render('discounts/DiscountIndexPage', [
            'discounts' => $discounts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Discount::class);

        $discountTypeOptions = collect(DiscountType::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('discounts/DiscountCreatePage', [
            'discountTypeOptions' => $discountTypeOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        Discount::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('discounts.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Discount $discount): Response
    {
        $this->authorize('view', $discount);

        $discountTypeOptions = collect(DiscountType::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $discount->load(['externalIdentifiers.integration']);
        $discountArray = $discount->toArray();
        $discountArray['external_identifiers'] = $discount->externalIdentifiers->map(fn ($identifier) => [
            'integration_type' => $identifier->integration->type->value,
            'external_type' => $identifier->external_type,
            'external_id' => $identifier->external_id,
            'data' => $identifier->data,
        ])->toArray();

        return Inertia::render('discounts/DiscountEditPage', [
            'discount' => $discountArray,
            'discountTypeOptions' => $discountTypeOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $discount->update($request->validated());

        return redirect()->route('discounts.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discount $discount): RedirectResponse
    {
        $this->authorize('delete', $discount);

        $discount->delete();

        return redirect()->route('discounts.index');
    }
}
