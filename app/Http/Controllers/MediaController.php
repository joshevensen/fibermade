<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Media::class);

        $user = auth()->user();
        $accountIds = $user->accounts()->pluck('id');
        $medias = $user->is_admin
            ? Media::with('mediable')->get()
            : Media::whereHasMorph('mediable', [
                \App\Models\Colorway::class,
                \App\Models\Base::class,
                \App\Models\Collection::class,
            ], function ($query) use ($accountIds) {
                $query->whereIn('account_id', $accountIds);
            })->with('mediable')->get();

        return Inertia::render('media/MediaIndexPage', [
            'medias' => $medias,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Media::class);

        return Inertia::render('media/MediaCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $media = Media::create($request->validated());
        $media->created_by = $request->user()->id;
        $media->save();

        return redirect()->route('media.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Media $media): Response
    {
        $this->authorize('view', $media);

        return Inertia::render('media/MediaEditPage', [
            'media' => $media->load('mediable'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());
        $media->updated_by = $request->user()->id;
        $media->save();

        return redirect()->route('media.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $media->delete();

        return redirect()->route('media.index');
    }
}
