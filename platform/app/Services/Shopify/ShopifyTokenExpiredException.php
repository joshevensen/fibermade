<?php

namespace App\Services\Shopify;

/**
 * Thrown when Shopify returns a 401 Unauthorized response.
 *
 * This indicates the stored access token is invalid or has been rotated.
 * The Shopify embedded app will refresh the token on the merchant's next
 * page load via the /api/v1/shopify/refresh-token endpoint.
 */
class ShopifyTokenExpiredException extends ShopifyApiException {}
