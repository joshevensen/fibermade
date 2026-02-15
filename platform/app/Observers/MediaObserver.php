<?php

namespace App\Observers;

use App\Jobs\SyncColorwayImagesToShopifyJob;
use App\Models\Colorway;
use App\Models\Media;
use Illuminate\Support\Facades\Log;

class MediaObserver
{
    public function created(Media $media): void
    {
        $this->dispatchImageSync($media);
    }

    public function updated(Media $media): void
    {
        if (! $media->wasChanged(['file_path', 'is_primary'])) {
            return;
        }

        $this->dispatchImageSync($media);
    }

    public function deleted(Media $media): void
    {
        $this->dispatchImageSync($media);
    }

    private function dispatchImageSync(Media $media): void
    {
        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return;
        }

        $colorway = $media->mediable;
        if (! $colorway instanceof Colorway) {
            return;
        }

        try {
            SyncColorwayImagesToShopifyJob::dispatch($colorway);
        } catch (\Throwable $e) {
            Log::warning('MediaObserver: failed to dispatch image sync job', [
                'media_id' => $media->id,
                'colorway_id' => $colorway->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
