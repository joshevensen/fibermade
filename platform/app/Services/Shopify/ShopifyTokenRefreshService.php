<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Exchanges a Shopify OAuth refresh token for a new access token.
 *
 * Used when background jobs receive a 401 from Shopify and need to refresh
 * credentials without merchant interaction.
 *
 * Shopify issues refresh tokens when `expiringOfflineAccessTokens` is enabled
 * in the app configuration. The refresh token is valid for 90 days and can
 * be used via the standard OAuth refresh_token grant.
 *
 * @return array{access_token: string, refresh_token: string|null, refresh_token_expires_in: int|null}
 */
class ShopifyTokenRefreshService
{
    /**
     * Exchange a refresh token for a new access token.
     *
     * @return array{access_token: string, refresh_token: string|null, refresh_token_expires_in: int|null}
     *
     * @throws ShopifyTokenExpiredException if the refresh token is invalid or expired
     * @throws ShopifyApiException for other API errors
     */
    public static function refresh(string $shop, string $refreshToken): array
    {
        $shop = preg_replace('#^https?://#', '', rtrim($shop, '/'));
        $apiKey = config('services.shopify.api_key');
        $apiSecret = config('services.shopify.api_secret');

        if (! $apiKey || ! $apiSecret) {
            throw new ShopifyApiException('Shopify API credentials (api_key/api_secret) are not configured.');
        }

        try {
            $response = Http::asForm()->post(
                "https://{$shop}/admin/oauth/access_token",
                [
                    'client_id' => $apiKey,
                    'client_secret' => $apiSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]
            );

            if ($response->status() === 400 || $response->status() === 401) {
                Log::warning('Shopify refresh token rejected', ['shop' => $shop, 'status' => $response->status()]);
                throw new ShopifyTokenExpiredException('Refresh token is invalid or expired.');
            }

            $response->throw();
            $body = $response->json();

            return [
                'access_token' => $body['access_token'],
                'refresh_token' => $body['refresh_token'] ?? null,
                'refresh_token_expires_in' => $body['refresh_token_expires_in'] ?? null,
            ];
        } catch (ShopifyTokenExpiredException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ShopifyApiException('Failed to refresh Shopify access token: '.$e->getMessage());
        }
    }
}
