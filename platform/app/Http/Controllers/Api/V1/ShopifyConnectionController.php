<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IntegrationType;
use App\Http\Controllers\Api\ApiController;
use App\Models\Account;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyConnectionController extends ApiController
{
    /**
     * Connect a Shopify store to a Fibermade account.
     */
    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connect_token' => ['required', 'string'],
            'shop' => ['required', 'string'],
            'shopify_access_token' => ['required', 'string'],
            'shopify_refresh_token' => ['nullable', 'string'],
        ]);

        $account = Account::where('shopify_connect_token', $validated['connect_token'])->first();

        if (! $account) {
            return $this->errorResponse('Invalid connect token');
        }

        $oldIds = Integration::query()
            ->where('account_id', $account->id)
            ->where('type', IntegrationType::Shopify)
            ->pluck('id');

        $credentials = ['access_token' => $validated['shopify_access_token']];
        if (! empty($validated['shopify_refresh_token'])) {
            $credentials['refresh_token'] = $validated['shopify_refresh_token'];
        }

        $integration = Integration::create([
            'account_id' => $account->id,
            'type' => IntegrationType::Shopify,
            'credentials' => json_encode($credentials),
            'settings' => ['shop' => $validated['shop'], 'auto_sync' => true],
            'active' => true,
        ]);

        if ($oldIds->isNotEmpty()) {
            ExternalIdentifier::whereIn('integration_id', $oldIds)
                ->update(['integration_id' => $integration->id]);

            Integration::whereIn('id', $oldIds)->delete();
        }

        return response()->json(['data' => ['integration_id' => $integration->id]], 201);
    }

    /**
     * Update the Shopify access token (and optional refresh token) for an
     * existing integration.
     *
     * Called by the Shopify app on every admin page load so Fibermade always
     * has the latest credentials.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connect_token' => ['required', 'string'],
            'shop' => ['required', 'string'],
            'shopify_access_token' => ['required', 'string'],
            'shopify_refresh_token' => ['nullable', 'string'],
        ]);

        $account = Account::where('shopify_connect_token', $validated['connect_token'])->first();

        if (! $account) {
            return $this->errorResponse('Invalid connect token');
        }

        $integration = Integration::where('account_id', $account->id)
            ->where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->get()
            ->first(function (Integration $i) use ($validated) {
                $config = $i->getShopifyConfig();

                return $config && strtolower($config['shop']) === strtolower(
                    preg_replace('#^https?://#', '', rtrim($validated['shop'], '/'))
                );
            });

        if (! $integration) {
            return response()->json(null, 204);
        }

        $integration->updateTokenCredentials(
            $validated['shopify_access_token'],
            $validated['shopify_refresh_token'] ?? null,
        );
        $integration->clearTokenInvalid();

        return response()->json(null, 204);
    }

    /**
     * Disconnect a Shopify store from a Fibermade account.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connect_token' => ['required', 'string'],
            'shop' => ['required', 'string'],
        ]);

        $account = Account::where('shopify_connect_token', $validated['connect_token'])->first();

        if (! $account) {
            return response()->json(null, 204);
        }

        $integration = Integration::findShopifyByShopDomain($validated['shop']);

        if ($integration && $integration->account_id === $account->id) {
            $integration->update(['active' => false]);
        }

        return response()->json(null, 204);
    }

    /**
     * Get the connection status for a Shopify store and Fibermade account.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'connect_token' => ['required', 'string'],
            'shop' => ['required', 'string'],
        ]);

        $account = Account::where('shopify_connect_token', $request->connect_token)->first();

        if (! $account) {
            return $this->successResponse(['active' => false]);
        }

        $integration = Integration::findShopifyByShopDomain($request->shop);

        if (! $integration || $integration->account_id !== $account->id) {
            return $this->successResponse(['active' => false, 'integration_id' => null]);
        }

        return $this->successResponse([
            'active' => $integration->active,
            'integration_id' => $integration->id,
            'creator_name' => $account->creator?->name,
        ]);
    }
}
