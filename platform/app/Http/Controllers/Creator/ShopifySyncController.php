<?php

namespace App\Http\Controllers\Creator;

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Http\Controllers\Controller;
use App\Jobs\PushCatalogToShopifyJob;
use App\Models\Integration;
use App\Services\Shopify\ShopifySyncOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ShopifySyncController extends Controller
{
    public function __construct(
        private readonly ShopifySyncOrchestrator $orchestrator
    ) {}

    /**
     * Trigger a full sync (products → collections → inventory).
     */
    public function syncAll(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->syncAll($integration));
    }

    /**
     * Trigger a products-only sync.
     */
    public function syncProducts(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->syncProducts($integration));
    }

    /**
     * Trigger a collections-only sync.
     */
    public function syncCollections(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->syncCollections($integration));
    }

    /**
     * Trigger an inventory-only sync.
     */
    public function syncInventory(Request $request): JsonResponse
    {
        return $this->triggerSync($request, fn (Integration $integration) => $this->orchestrator->syncInventory($integration));
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

        PushCatalogToShopifyJob::dispatch($integration->id);

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
            'recent_errors' => $this->recentErrors($integration, $settings),
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
     * Return recent error logs since the last sync started (or empty if no sync has run).
     *
     * @param  array<string, mixed>  $settings
     * @return list<array{id: int, message: string, created_at: string|null}>
     */
    private function recentErrors(Integration $integration, array $settings): array
    {
        $syncStartedAt = $settings['sync']['started_at'] ?? null;
        $pushStartedAt = $settings['push_sync']['started_at'] ?? null;

        if (! $syncStartedAt && ! $pushStartedAt) {
            return [];
        }

        $since = Carbon::parse(max(array_filter([$syncStartedAt, $pushStartedAt])));

        return $integration->logs()
            ->where('status', IntegrationLogStatus::Error)
            ->where('created_at', '>=', $since)
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
