<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationLogStatus;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyGraphqlClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles Shopify webhooks. inventory_levels/update pulls inventory into Fibermade.
 */
class ShopifyWebhookController extends Controller
{
    public function __invoke(Request $request): Response
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
        $available = $data['available'] ?? null;

        if ($inventoryItemId === null || $available === null) {
            Log::warning('Shopify webhook rejected: missing inventory_item_id or available', ['payload' => $data]);

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
            $client = InventorySyncService::createShopifyClient($integration);
            if (! $client instanceof ShopifyGraphqlClient) {
                Log::warning('Shopify webhook: integration has no API config', ['integration_id' => $integration->id]);

                return response('', 500);
            }

            $variantGid = $client->getVariantIdFromInventoryItemId($inventoryItemGid);
            if (! $variantGid) {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => \App\Models\Inventory::class,
                    'loggable_id' => 0,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => 'Webhook: inventory_item has no variant or variant not found',
                    'metadata' => [
                        'sync_source' => 'webhook',
                        'inventory_item_id' => $inventoryItemId,
                        'available' => $available,
                    ],
                    'synced_at' => now(),
                ]);

                return response('', 200);
            }

            $syncService = new InventorySyncService;
            $updated = $syncService->pullInventoryFromShopify($variantGid, (int) $available, $integration, 'webhook');

            if (! $updated) {
                IntegrationLog::create([
                    'integration_id' => $integration->id,
                    'loggable_type' => \App\Models\Inventory::class,
                    'loggable_id' => 0,
                    'status' => IntegrationLogStatus::Warning,
                    'message' => 'Webhook: no Fibermade Inventory for Shopify variant',
                    'metadata' => [
                        'sync_source' => 'webhook',
                        'shopify_variant_id' => $variantGid,
                        'available' => $available,
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
                'loggable_type' => \App\Models\Inventory::class,
                'loggable_id' => 0,
                'status' => IntegrationLogStatus::Error,
                'message' => 'Webhook processing failed: '.$e->getMessage(),
                'metadata' => [
                    'sync_source' => 'webhook',
                    'inventory_item_id' => $inventoryItemId,
                    'available' => $available,
                ],
                'synced_at' => now(),
            ]);

            return response('', 500);
        }
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
