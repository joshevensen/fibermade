<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBaseRequest;
use App\Http\Requests\UpdateBaseRequest;
use App\Models\Base;
use Inertia\Inertia;

class BaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('bases/BaseIndexPage');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('bases/BaseCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseRequest $request)
    {
        // TODO: Implement later
    }

    /**
     * Display the specified resource.
     */
    public function show(Base $base)
    {
        return Inertia::render('bases/BaseShowPage', [
            'base' => $base,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Base $base)
    {
        return Inertia::render('bases/BaseEditPage', [
            'base' => $base,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaseRequest $request, Base $base)
    {
        // TODO: Implement later
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Base $base)
    {
        // TODO: Implement later
    }
}
