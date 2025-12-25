<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreColorwayRequest;
use App\Http\Requests\UpdateColorwayRequest;
use App\Models\Colorway;
use Inertia\Inertia;

class ColorwayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('colorways/ColorwayIndexPage');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('colorways/ColorwayCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColorwayRequest $request)
    {
        // TODO: Implement later
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Colorway $colorway)
    {
        return Inertia::render('colorways/ColorwayEditPage', [
            'colorway' => $colorway,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateColorwayRequest $request, Colorway $colorway)
    {
        // TODO: Implement later
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Colorway $colorway)
    {
        // TODO: Implement later
    }
}
