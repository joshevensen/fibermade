<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBaseRequest;
use App\Http\Requests\UpdateBaseRequest;
use App\Models\Base;

class BaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Base $base)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Base $base)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaseRequest $request, Base $base)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Base $base)
    {
        //
    }
}
