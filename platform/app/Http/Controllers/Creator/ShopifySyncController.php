<?php

namespace App\Http\Controllers\Creator;

use App\Enums\IntegrationType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Shopify\ShopifySyncOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
