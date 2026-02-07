<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDyeRequest;
use App\Http\Requests\UpdateDyeRequest;
use App\Models\Dye;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DyeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Dye::class);

        $user = auth()->user();
        $dyes = $user->is_admin
            ? Dye::with('account')->get()
            : ($user->account_id ? Dye::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('creator/dyes/DyeIndexPage', [
            'dyes' => $dyes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Dye::class);

        return Inertia::render('creator/dyes/DyeCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDyeRequest $request): RedirectResponse
    {
        Dye::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('dyes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dye $dye): Response
    {
        $this->authorize('view', $dye);

        return Inertia::render('creator/dyes/DyeEditPage', [
            'dye' => $dye,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDyeRequest $request, Dye $dye): RedirectResponse
    {
        $dye->update($request->validated());

        return redirect()->route('dyes.index');
    }

    /**
     * Toggle a boolean field on the dye.
     */
    public function toggleField(Dye $dye): RedirectResponse
    {
        $this->authorize('update', $dye);

        $field = request()->input('field');
        $value = request()->boolean('value');

        if (! in_array($field, ['does_bleed', 'do_like'])) {
            abort(400, 'Invalid field');
        }

        $dye->update([$field => $value]);

        return redirect()->route('dyes.index');
    }

    /**
     * Update the notes field on the dye.
     */
    public function updateNotes(UpdateDyeRequest $request, Dye $dye): RedirectResponse
    {
        $dye->update(['notes' => $request->input('notes')]);

        return redirect()->route('dyes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dye $dye): RedirectResponse
    {
        $this->authorize('delete', $dye);

        $dye->delete();

        return redirect()->route('dyes.index');
    }
}
