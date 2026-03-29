<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyGraphqlClient;
use App\Services\Shopify\ShopifyWebhookNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles Shopify webhooks received directly from Shopify.
 *
 * All routes are public (no auth) but require valid HMAC signatures.
 * Product and collection webhooks are dispatched as queued jobs so Shopify
 * gets a fast 200 response. Processing happens asynchronously.
 */
class ShopifyWebhookController extends Controller
{
    public function __construct(
        private readonly ShopifyWebhookNormalizer $normalizer = new ShopifyWebhookNormalizer,
    ) {}

    /**
     * Handle inventory_levels/update webhooks.
     *
     * TODO: re-enable when inventory ships post-launch — processing is disabled for now.
     */
    public function inventory(Request $request): Response
    {
        return response('', 200);
    }

    /**
     * @codeCoverageIgnore
     */
    private function processInventoryWebhook(Request $request): Response
    {
        $topic = $request->header('X-Shopify-Topic');
        if ($topic !== 'inventory_levels/update') {
            return response('', 200);
        }

        $payload = $request->getContent();
        if (! $this->verifyHmac($payload, $request->header('X-Shopify-Hmac-Sha256'))) {
            Log::warning('Shopify webhook rejected: invalid HMAC signature');

            return response('', 401);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            Log::warning('Shopify webhook rejected: invalid JSON payload');

            return response('', 400);
        }

        $inventoryItemId = $data['inventory_item_id'] ?? null;

        if ($inventoryItemId === null) {
            Log::warning('Shopify webhook rejected: missing inventory_item_id', ['payload' => $data]);

            return response('', 400);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        if (empty($shopDomain)) {
            Log::warning('Shopify webhook rejected: missing X-Shopify-Shop-Domain');

            return response('', 400);
        }

        $integration = Integration::findShopifyByShopDomain($shopDomain);
        if (! $integration) {
            Log::warning('Shopify webhook: no integration found for shop', ['shop' => $shopDomain]);

            return response('', 200);
        }

        $inventoryItemGid = is_numeric($inventoryItemId)
            ? "gid://shopify/InventoryItem/{$inventoryItemId}"
            : $inventoryItemId;

        try {
            $client = app()->bound('shopify.graphql_client_resolver')
                ? app('shopify.graphql_client_resolver')($integration)
                : InventorySyncService::createShopifyClient($integration);
            if (! $client instanceof ShopifyGraphqlClient) {
                Log::warning('Shopify webhook: integration has no API config', ['integration_id' => $integration->id]);

                return response('', 500);
            }

            $variantGid = $client->getVariantIdFromInventoryItemId($inventoryItemGid);
            if (! $variantGid) {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => Inventory::class,
                    'loggable_id' => 0,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => 'Webhook: inventory_item has no variant or variant not found',
                    'metadata' => [
                        'sync_source' => 'webhook',
                        'inventory_item_id' => $inventoryItemId,
                    ],
                    'synced_at' => now(),
                ]);

                return response('', 200);
            }

            // Fetch on_hand from the API rather than using `available` from the webhook
            // payload. The webhook fires on every order (available drops), but Fibermade
            // only tracks physical on-hand stock — available is calculated by Shopify.
            $inventoryData = $client->getVariantInventory($variantGid);
            $onHand = $inventoryData['onHandQuantity'];

            $syncService = new InventorySyncService;
            $updated = $syncService->pullInventoryFromShopify($variantGid, $onHand, $integration, 'webhook');

            if (! $updated) {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => Inventory::class,
                    'loggable_id' => 0,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => 'Webhook: no Fibermade Inventory for Shopify variant',
                    'metadata' => [
                        'sync_source' => 'webhook',
                        'shopify_variant_id' => $variantGid,
                    ],
                    'synced_at' => now(),
                ]);
            }

            return response('', 200);
        } catch (\Throwable $e) {
            Log::error('Shopify webhook processing failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            IntegrationLog::create([
                'integration_id' => $integration->id,
                'loggable_type' => Inventory::class,
                'loggable_id' => 0,
                'status' => IntegrationLogStatus::Error,
                'message' => 'Webhook processing failed: '.$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'webhook',
                    'inventory_item_id' => $inventoryItemId,
                ],
                'synced_at' => now(),
            ]);

            return response('', 500);
        }
    }

    /**
     * Handle products/create webhooks.
     */
    public function productCreate(Request $request): Response
    {
        return $this->handleProductWebhook($request, 'create');
    }

    /**
     * Handle products/update webhooks.
     */
    public function productUpdate(Request $request): Response
    {
        return $this->handleProductWebhook($request, 'update');
    }

    /**
     * Handle products/delete webhooks.
     */
    public function productDelete(Request $request): Response
    {
        return $this->handleProductWebhook($request, 'delete');
    }

    /**
     * Handle collections/create webhooks.
     */
    public function collectionCreate(Request $request): Response
    {
        return $this->handleCollectionWebhook($request, 'create');
    }

    /**
     * Handle collections/update webhooks.
     */
    public function collectionUpdate(Request $request): Response
    {
        return $this->handleCollectionWebhook($request, 'update');
    }

    /**
     * Handle collections/delete webhooks.
     */
    public function collectionDelete(Request $request): Response
    {
        return $this->handleCollectionWebhook($request, 'delete');
    }

    private function handleProductWebhook(Request $request, string $action): Response
    {
        // Fibermade is the source of truth — product webhooks from Shopify are
        // ignored to prevent echo loops. Return 200 so Shopify stops retrying.
        return response('', 200);
    }

    private function handleCollectionWebhook(Request $request, string $action): Response
    {
        // Fibermade is the source of truth — collection webhooks from Shopify are
        // ignored to prevent echo loops. Return 200 so Shopify stops retrying.
        return response('', 200);
    }

    private function resolveIntegration(?string $shopDomain): ?Integration
    {
        if (empty($shopDomain)) {
            return null;
        }

        $integration = Integration::findShopifyByShopDomain($shopDomain);
        if (! $integration) {
            Log::info('Shopify webhook: no integration found for shop', ['shop' => $shopDomain]);
        }

        return $integration;
    }

    private function isAutoSyncEnabled(Integration $integration): bool
    {
        return (bool) ($integration->settings['auto_sync'] ?? false);
    }

    private function verifyHmac(string $body, ?string $headerHmac): bool
    {
        $secret = config('services.shopify.webhook_secret');
        if (empty($secret) || empty($headerHmac)) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $body, $secret, true));

        return hash_equals($calculated, $headerHmac);
    }
}
