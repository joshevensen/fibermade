<?php

namespace App\Http\Controllers;

use App\Enums\BaseStatus;
use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\Technique;
use App\Http\Requests\StoreColorwayMediaRequest;
use App\Http\Requests\StoreColorwayRequest;
use App\Http\Requests\UpdateColorwayCollectionsRequest;
use App\Http\Requests\UpdateColorwayRequest;
use App\Jobs\SyncColorwayCatalogToShopifyJob;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ColorwayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Colorway::class);

        $user = auth()->user();
        $colorways = $user->is_admin
            ? Colorway::with(['account', 'media', 'collections', 'externalIdentifiers.integration'])->get()
            : ($user->account_id ? Colorway::where('account_id', $user->account_id)->with(['account', 'media', 'collections', 'externalIdentifiers.integration'])->get() : collect());

        $colorways = $colorways->map(function ($colorway) {
            $colorwayArray = $colorway->toArray();
            $colorwayArray['primary_image_url'] = $colorway->primary_image_url;
            $colorwayArray['collections'] = $colorway->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
            ])->toArray();
            $colorwayArray['external_identifiers'] = $colorway->externalIdentifiers->map(fn ($identifier) => [
                'integration_type' => $identifier->integration->type->value,
                'external_type' => $identifier->external_type,
                'external_id' => $identifier->external_id,
                'data' => $identifier->data,
            ])->toArray();

            return $colorwayArray;
        });

        $colorwayStatusOptions = collect(ColorwayStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $techniqueOptions = collect(Technique::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $colorOptions = collect(Color::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $collections = $user->is_admin
            ? Collection::orderBy('name')->get()
            : ($user->account_id ? Collection::where('account_id', $user->account_id)->orderBy('name')->get() : collect());

        $collectionOptions = $collections->map(fn ($collection) => [
            'label' => $collection->name,
            'value' => $collection->id,
        ])->toArray();

        return Inertia::render('creator/colorways/ColorwayIndexPage', [
            'colorways' => $colorways,
            'colorwayStatusOptions' => $colorwayStatusOptions,
            'techniqueOptions' => $techniqueOptions,
            'colorOptions' => $colorOptions,
            'collectionOptions' => $collectionOptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Colorway::class);

        $colorwayStatusOptions = collect(ColorwayStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $techniqueOptions = collect(Technique::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $colorOptions = collect(Color::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('creator/colorways/ColorwayCreatePage', [
            'colorwayStatusOptions' => $colorwayStatusOptions,
            'techniqueOptions' => $techniqueOptions,
            'colorOptions' => $colorOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColorwayRequest $request): RedirectResponse
    {
        $colorway = Colorway::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('colorways.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Colorway $colorway): Response
    {
        $this->authorize('view', $colorway);

        $colorway->load(['collections', 'inventories', 'externalIdentifiers.integration', 'media']);

        $user = auth()->user();
        $allCollections = $user->is_admin
            ? Collection::orderBy('name')->get()
            : ($user->account_id ? Collection::where('account_id', $user->account_id)->orderBy('name')->get() : collect());

        // Fetch all active bases for the account
        $bases = $user->is_admin
            ? Base::where('status', BaseStatus::Active)->orderBy('code')->get()
            : ($user->account_id
                ? Base::where('account_id', $user->account_id)
                    ->where('status', BaseStatus::Active)
                    ->orderBy('code')
                    ->get()
                : collect());

        // Map bases with their inventory quantities for this colorway
        $basesData = $bases->map(function ($base) use ($colorway) {
            $inventory = $colorway->inventories->firstWhere('base_id', $base->id);

            return [
                'id' => $base->id,
                'code' => $base->code,
                'descriptor' => $base->descriptor,
                'quantity' => $inventory ? $inventory->quantity : 0,
                'inventory_id' => $inventory ? $inventory->id : null,
            ];
        })->toArray();

        $colorwayStatusOptions = collect(ColorwayStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $techniqueOptions = collect(Technique::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $colorOptions = collect(Color::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $colorwayArray = $colorway->toArray();
        $colorwayArray['external_identifiers'] = $colorway->externalIdentifiers->map(fn ($identifier) => [
            'integration_type' => $identifier->integration->type->value,
            'external_type' => $identifier->external_type,
            'external_id' => $identifier->external_id,
            'data' => $identifier->data,
        ])->toArray();

        $hasShopify = Integration::where('account_id', $colorway->account_id)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->exists();

        $mediaData = $colorway->media->map(fn ($media) => [
            'id' => $media->id,
            'url' => $colorway->resolveMediaUrl($media),
            'file_name' => $media->file_name,
        ])->values()->toArray();

        return Inertia::render('creator/colorways/ColorwayEditPage', [
            'colorway' => $colorwayArray,
            'collections' => $colorway->collections,
            'allCollections' => $allCollections,
            'bases' => $basesData,
            'colorwayStatusOptions' => $colorwayStatusOptions,
            'techniqueOptions' => $techniqueOptions,
            'colorOptions' => $colorOptions,
            'has_shopify' => $hasShopify,
            'media' => $mediaData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateColorwayRequest $request, Colorway $colorway): RedirectResponse
    {
        $colorway->update($request->validated());
        $colorway->updated_by = $request->user()->id;
        $colorway->save();

        return redirect()->route('colorways.index');
    }

    /**
     * Update the collections for the specified colorway.
     */
    public function updateCollections(UpdateColorwayCollectionsRequest $request, Colorway $colorway): RedirectResponse
    {
        $colorway->collections()->sync($request->validated()['collection_ids']);

        return redirect()->route('colorways.edit', $colorway)->with('success', 'Collections updated successfully.');
    }

    /**
     * Dispatch a push-to-Shopify job for the given colorway.
     */
    public function pushToShopify(Colorway $colorway): JsonResponse
    {
        $this->authorize('update', $colorway);

        $integration = Integration::where('account_id', $colorway->account_id)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();

        $hasExistingProduct = $integration && $colorway->getExternalIdFor($integration, 'shopify_product');
        $action = $hasExistingProduct ? 'updated' : 'created';

        SyncColorwayCatalogToShopifyJob::dispatch($colorway, $action);

        return response()->json(['message' => 'Push queued.']);
    }

    /**
     * Upload one or more images for the given colorway.
     */
    public function storeMedia(StoreColorwayMediaRequest $request, Colorway $colorway): RedirectResponse
    {
        $disk = config('filesystems.media_disk', 'public');
        $hasExistingMedia = $colorway->media()->exists();

        foreach ($request->file('images') as $file) {
            $relativePath = $file->store("colorways/{$colorway->id}", $disk);

            $media = $colorway->media()->create([
                'mediable_type' => Colorway::class,
                'mediable_id' => $colorway->id,
                'file_path' => $relativePath,
                'disk' => $disk,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_primary' => ! $hasExistingMedia,
                'created_by' => $request->user()->id,
            ]);

            // Only the first uploaded image can become primary
            if (! $hasExistingMedia) {
                $hasExistingMedia = true;
            }
        }

        return redirect()->route('colorways.edit', $colorway);
    }

    /**
     * Delete a media record and its file for the given colorway.
     */
    public function destroyMedia(Colorway $colorway, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        if ($media->disk) {
            Storage::disk($media->disk)->delete($media->file_path);
        }

        $media->delete();

        return redirect()->route('colorways.edit', $colorway);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Colorway $colorway): RedirectResponse
    {
        $this->authorize('delete', $colorway);

        $colorway->delete();

        return redirect()->route('colorways.index');
    }
}
