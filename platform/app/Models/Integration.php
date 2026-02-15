<?php

namespace App\Models;

use App\Enums\IntegrationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property \App\Enums\IntegrationType $type
 * @property string $credentials
 * @property array|null $settings
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Integration extends Model
{
    /** @use HasFactory<\Database\Factories\IntegrationFactory> */
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

        return (bool) ($this->settings['catalog_sync_enabled'] ?? true);
    }

    /**
     * Get Shopify API config (shop domain and access token) for Shopify integrations.
     *
     * @return array{shop: string, access_token: string}|null
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
        $decoded = json_decode($credentials, true);
        if (is_array($decoded) && isset($decoded['access_token'])) {
            $accessToken = $decoded['access_token'];
        }

        if (empty($accessToken)) {
            return null;
        }

        return [
            'shop' => $shop,
            'access_token' => $accessToken,
        ];
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

    private static function normalizeShopDomain(string $shop): string
    {
        return strtolower(preg_replace('#^https?://#', '', rtrim($shop, '/')));
    }
}
