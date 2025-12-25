<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDyeRequest;
use App\Http\Requests\UpdateDyeRequest;
use App\Models\Dye;
use Inertia\Inertia;

class DyeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('dyes/DyeIndexPage');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('dyes/DyeCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDyeRequest $request)
    {
        // TODO: Implement later
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dye $dye)
    {
        return Inertia::render('dyes/DyeEditPage', [
            'dye' => $dye,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDyeRequest $request, Dye $dye)
    {
        // TODO: Implement later
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dye $dye)
    {
        // TODO: Implement later
    }
}
