<?php

namespace App\Models;

use App\Enums\IntegrationType;
use App\Services\Shopify\ShopifyApiException;
use App\Services\Shopify\ShopifyTokenExpiredException;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an integration connection (Shopify, future integrations).
 *
 * Integrations store connection details and settings for external services.
 * In Stage 1, this primarily represents Shopify connections via the GraphQL
 * Admin API. Credentials are stored encrypted, and settings are stored as JSON.
 * Each Integration belongs to an Account and can be activated or deactivated.
 *
 * @property int $id
 * @property int $account_id
 * @property IntegrationType $type
 * @property string $credentials
 * @property array|null $settings
 * @property bool $active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'credentials',
        'settings',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'credentials' => 'encrypted',
            'settings' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the account that owns this integration.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the integration logs for this integration.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    /**
     * Whether automatic catalog sync to Shopify is enabled for this integration.
     * Requires global SHOPIFY_CATALOG_SYNC_ENABLED; per-integration setting can disable.
     */
    public function isCatalogSyncEnabled(): bool
    {
        if ($this->type !== IntegrationType::Shopify) {
            return false;
        }

        if (! config('services.shopify.catalog_sync_enabled', false)) {
            return false;
        }

        if ($this->settings['token_invalid'] ?? false) {
            return false;
        }

        return (bool) ($this->settings['catalog_sync_enabled'] ?? true);
    }

    /**
     * Get Shopify API config (shop domain and access token) for Shopify integrations.
     *
     * @return array{shop: string, access_token: string, refresh_token: string|null}|null
     */
    public function getShopifyConfig(): ?array
    {
        if ($this->type !== IntegrationType::Shopify) {
            return null;
        }

        $settings = $this->settings ?? [];
        $shop = $settings['shop'] ?? $settings['store_url'] ?? null;
        if (is_string($shop) && str_starts_with($shop, 'http')) {
            $parsed = parse_url($shop);
            $shop = $parsed['host'] ?? $shop;
        }

        $credentials = $this->credentials;
        if (empty($credentials) || ! $shop) {
            return null;
        }

        $accessToken = $credentials;
        $refreshToken = null;
        $decoded = json_decode($credentials, true);
        if (is_array($decoded) && isset($decoded['access_token'])) {
            $accessToken = $decoded['access_token'];
            $refreshToken = $decoded['refresh_token'] ?? null;
        }

        if (empty($accessToken)) {
            return null;
        }

        return [
            'shop' => $shop,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Update the stored access token (and optionally refresh token) after a
     * successful server-side token refresh.
     */
    public function updateTokenCredentials(string $accessToken, ?string $refreshToken = null): void
    {
        $decoded = json_decode($this->credentials ?? '', true) ?? [];

        $decoded['access_token'] = $accessToken;
        if ($refreshToken !== null) {
            $decoded['refresh_token'] = $refreshToken;
        }

        $this->update(['credentials' => json_encode($decoded)]);
    }

    /**
     * Get the current push sync status from integration settings.
     */
    public function getPushSyncStatus(): ?string
    {
        return $this->settings['push_sync']['status'] ?? null;
    }

    /**
     * Find an active Shopify integration by shop domain (e.g. from X-Shopify-Shop-Domain).
     */
    public static function findShopifyByShopDomain(string $shopDomain): ?self
    {
        $normalized = strtolower(preg_replace('#^https?://#', '', rtrim($shopDomain, '/')));

        return self::where('type', IntegrationType::Shopify)
            ->where('active', true)
            ->get()
            ->first(function (self $i) use ($normalized) {
                $config = $i->getShopifyConfig();

                return $config && self::normalizeShopDomain($config['shop']) === $normalized;
            });
    }

    /**
     * Set the has_sync_errors flag in integration settings.
     */
    public function flagSyncError(): void
    {
        $settings = $this->settings ?? [];
        $settings['has_sync_errors'] = true;
        $this->update(['settings' => $settings]);
    }

    /**
     * Clear the has_sync_errors flag in integration settings.
     */
    public function clearSyncErrors(): void
    {
        $settings = $this->settings ?? [];
        $settings['has_sync_errors'] = false;
        $this->update(['settings' => $settings]);
    }

    /**
     * Mark the integration's access token as invalid (e.g. after a 401 from Shopify).
     *
     * Catalog sync jobs check this flag and skip until the token is refreshed.
     */
    public function flagTokenInvalid(): void
    {
        $settings = $this->settings ?? [];
        $settings['token_invalid'] = true;
        $this->update(['settings' => $settings]);
    }

    /**
     * Clear the token_invalid flag after a successful token refresh.
     */
    public function clearTokenInvalid(): void
    {
        $settings = $this->settings ?? [];
        $settings['token_invalid'] = false;
        $this->update(['settings' => $settings]);
    }

    /**
     * Handle a Shopify API exception from a sync job.
     *
     * 401 errors flag the token as invalid (recoverable via the Shopify app's
     * refresh-token flow) without reporting to Sentry. All other errors flag
     * a general sync error and are captured for investigation.
     */
    public function handleSyncException(ShopifyApiException $e): void
    {
        if ($e instanceof ShopifyTokenExpiredException) {
            $this->flagTokenInvalid();
        } else {
            \Sentry\captureException($e);
            $this->flagSyncError();
        }
    }

    private static function normalizeShopDomain(string $shop): string
    {
        return strtolower(preg_replace('#^https?://#', '', rtrim($shop, '/')));
    }
}
