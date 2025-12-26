<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBaseRequest;
use App\Http\Requests\UpdateBaseRequest;
use App\Models\Base;
use Illuminate\Http\RedirectResponse;
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
            : Base::whereIn('account_id', $user->accounts()->pluck('id'))->with('account')->get();

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

        return Inertia::render('bases/BaseCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseRequest $request): RedirectResponse
    {
        Base::create($request->validated());

        return redirect()->route('bases.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Base $base): Response
    {
        $this->authorize('view', $base);

        return Inertia::render('bases/BaseEditPage', [
            'base' => $base,
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
