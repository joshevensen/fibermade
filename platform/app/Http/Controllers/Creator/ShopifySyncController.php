<?php

namespace App\Http\Controllers\Creator;

use App\Enums\BaseStatus;
use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Http\Controllers\Controller;
use App\Jobs\PushBaseJob;
use App\Jobs\PushCatalogJob;
use App\Jobs\PushCollectionJob;
use App\Jobs\PushColorwayJob;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Integration;
use App\Services\Shopify\ShopifySyncOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopifySyncController extends Controller
{
    public function __construct(
        private readonly ShopifySyncOrchestrator $orchestrator
    ) {}

    /**
     * Trigger a full pull (products → collections → inventory).
     */
    public function pullAll(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->pullAll($integration));
    }

    /**
     * Trigger a colorways-only pull.
     */
    public function pullColorways(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->pullColorways($integration));
    }

    /**
     * Trigger a collections-only pull.
     */
    public function pullCollections(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->pullCollections($integration));
    }

    /**
     * Trigger an inventory-only pull.
     */
    public function pullInventory(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->pullInventory($integration));
    }

    /**
     * Dispatch push jobs for all active bases to sync variants across all Shopify products.
     */
    public function pushBases(Request $request): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        $bases = Base::where('account_id', $request->user()->account_id)
            ->where('status', BaseStatus::Active)
            ->get();

        foreach ($bases as $base) {
            PushBaseJob::dispatch($base, 'updated');
        }

        return response()->json(['message' => 'Base sync queued.', 'count' => $bases->count()], 202);
    }

    /**
     * Push all colorways to Shopify.
     */
    public function pushColorways(Request $request): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        $colorways = Colorway::where('account_id', $request->user()->account_id)->get();

        foreach ($colorways as $colorway) {
            PushColorwayJob::dispatch($colorway, 'updated');
        }

        return response()->json(['message' => 'Colorway push queued.', 'count' => $colorways->count()], 202);
    }

    /**
     * Push all collections to Shopify.
     */
    public function pushCollections(Request $request): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        $collections = Collection::where('account_id', $request->user()->account_id)->get();

        foreach ($collections as $collection) {
            PushCollectionJob::dispatch($collection, 'updated');
        }

        return response()->json(['message' => 'Collection push queued.', 'count' => $collections->count()], 202);
    }

    /**
     * Trigger a full catalog push (colorways → collections) to Shopify.
     */
    public function pushAll(Request $request): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        if ($integration->getPushSyncStatus() === 'running') {
            return response()->json(['message' => 'A push is already running.'], 409);
        }

        PushCatalogJob::dispatch($integration->id);

        $integration->refresh();
        $settings = $integration->settings ?? [];

        return response()->json([
            'message' => 'Push started',
            'push_sync' => $settings['push_sync'] ?? ['status' => 'running'],
        ], 202);
    }

    /**
     * Return the current sync state and connection info.
     */
    public function status(Request $request): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['connected' => false], 404);
        }

        $settings = $integration->settings ?? [];

        return response()->json([
            'connected' => true,
            'shop' => $settings['shop'] ?? null,
            'auto_sync' => $settings['auto_sync'] ?? false,
            'sync' => $settings['sync'] ?? ['status' => 'idle'],
            'push_sync' => $settings['push_sync'] ?? ['status' => 'idle'],
            'recent_errors' => $this->recentErrors($integration),
        ]);
    }

    /**
     * Save the auto_sync toggle on the integration settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'auto_sync' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        $settings = $integration->settings ?? [];
        $settings['auto_sync'] = $validated['auto_sync'];
        $integration->update(['settings' => $settings]);

        return response()->json(['auto_sync' => $settings['auto_sync']]);
    }

    /**
     * Dismiss the Shopify sync error banner by clearing the error flag.
     */
    public function dismissErrors(Integration $integration): RedirectResponse
    {
        $this->authorize('update', $integration);
        $integration->clearSyncErrors();

        return back();
    }

    /**
     * Return the most recent error logs for this integration.
     *
     * @return list<array{id: int, message: string, created_at: string|null}>
     */
    private function recentErrors(Integration $integration): array
    {
        return $integration->logs()
            ->where('status', IntegrationLogStatus::Error)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'message' => $log->message,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Resolve the authenticated user's active Shopify integration.
     */
    private function resolveIntegration(Request $request): ?Integration
    {
        $accountId = $request->user()->account_id;

        if (! $accountId) {
            return null;
        }

        return Integration::where('account_id', $accountId)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->first();
    }

    /**
     * Resolve the integration, call the given sync callback, and return a 202 response.
     */
    private function triggerSync(Request $request, callable $sync): JsonResponse
    {
        $integration = $this->resolveIntegration($request);

        if (! $integration) {
            return response()->json(['message' => 'No active Shopify integration found.'], 404);
        }

        try {
            $sync($integration);
        } catch (SyncAlreadyRunningException) {
            return response()->json(['message' => 'A sync is already running.'], 409);
        }

        $integration->refresh();
        $settings = $integration->settings ?? [];

        return response()->json([
            'message' => 'Sync started',
            'sync' => $settings['sync'] ?? ['status' => 'running'],
        ], 202);
    }
}
