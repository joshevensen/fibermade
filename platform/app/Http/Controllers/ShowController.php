<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShowRequest;
use App\Http\Requests\UpdateShowRequest;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Show::class);

        $user = auth()->user();
        $shows = $user->is_admin
            ? Show::with('account')->get()
            : ($user->account_id ? Show::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('creator/shows/ShowIndexPage', [
            'shows' => $shows,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Show::class);

        return Inertia::render('creator/shows/ShowCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShowRequest $request): RedirectResponse
    {
        Show::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('shows.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Show $show): Response
    {
        $this->authorize('view', $show);

        return Inertia::render('creator/shows/ShowEditPage', [
            'show' => $show,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShowRequest $request, Show $show): RedirectResponse
    {
        $show->update($request->validated());

        return redirect()->route('shows.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Show $show): RedirectResponse
    {
        $this->authorize('delete', $show);

        $show->delete();

        return redirect()->route('shows.index');
    }
}
