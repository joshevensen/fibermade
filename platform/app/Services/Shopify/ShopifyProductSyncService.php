<?php

namespace App\Services\Shopify;

use App\Data\Shopify\SyncResult;
use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationLogStatus;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Models\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Pulls Shopify products into Fibermade as Colorways, Bases, and Inventory records.
 *
 * This is a read-only pull from Shopify — it never pushes to Shopify.
 */
class ShopifyProductSyncService
{
    private const STATUS_MAP = [
        'ACTIVE' => ColorwayStatus::Active,
        'DRAFT' => ColorwayStatus::Idea,
        'ARCHIVED' => ColorwayStatus::Retired,
    ];

    private const DEFAULT_VARIANT_TITLE = 'Default Title';

    public function __construct(
        private readonly ?ShopifyGraphqlClient $clientOverride = null
    ) {}

    /**
     * Sync all Shopify products into Fibermade (paginated).
     */
    public function syncAll(Integration $integration): SyncResult
    {
        $client = $this->shopifyClientFor($integration);
        $result = new SyncResult;
        $cursor = null;

        do {
            $page = $client->getProducts($cursor);

            foreach ($page['products'] as $product) {
                try {
                    $outcome = $this->syncProduct($product, $integration);
                    match ($outcome) {
                        'created' => $result->created++,
                        'updated' => $result->updated++,
                        'skipped' => $result->skipped++,
                    };
                } catch (\Throwable $e) {
                    $result->addError($product['gid'], $e->getMessage());
                }
            }

            $cursor = $page['nextCursor'];
        } while ($page['hasNextPage']);

        return $result;
    }

    /**
     * Sync a single Shopify product (create or update).
     *
     * @return string 'created'|'updated'|'skipped'
     */
    public function syncProduct(array $shopifyProduct, Integration $integration): string
    {
        $identifier = ExternalIdentifier::where('integration_id', $integration->id)
            ->where('external_type', 'shopify_product')
            ->where('external_id', $shopifyProduct['gid'])
            ->where('identifiable_type', Colorway::class)
            ->first();

        if ($identifier) {
            $colorway = Colorway::find($identifier->identifiable_id);
            if ($colorway) {
                $this->updateColorway($colorway, $shopifyProduct, $integration);

                return 'updated';
            }
        }

        $this->createColorway($shopifyProduct, $integration);

        return 'created';
    }

    private function createColorway(array $shopifyProduct, Integration $integration): Colorway
    {
        $shop = $this->shopDomain($integration);

        $colorway = Colorway::create([
            'account_id' => $integration->account_id,
            'name' => $this->productName($shopifyProduct),
            'description' => $shopifyProduct['descriptionHtml'] ? trim($shopifyProduct['descriptionHtml']) : null,
            'status' => $this->mapStatus($shopifyProduct['status']),
            'per_pan' => 1,
        ]);

        $this->syncImage($colorway, $shopifyProduct);

        $productNumericId = $this->numericId($shopifyProduct['gid']);
        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Colorway::class,
            'identifiable_id' => $colorway->id,
            'external_type' => 'shopify_product',
            'external_id' => $shopifyProduct['gid'],
            'data' => [
                'admin_url' => "https://{$shop}/admin/products/{$productNumericId}",
                'shopify_handle' => $shopifyProduct['handle'],
            ],
        ]);

        $this->createVariants($colorway, $shopifyProduct, $integration, $shop);

        $this->log(
            $integration,
            $colorway,
            IntegrationLogStatus::Success,
            "Imported Shopify product '{$colorway->name}' as Colorway #{$colorway->id}",
            ['shopify_gid' => $shopifyProduct['gid']]
        );

        return $colorway;
    }

    private function updateColorway(Colorway $colorway, array $shopifyProduct, Integration $integration): void
    {
        $shop = $this->shopDomain($integration);

        $colorway->update([
            'name' => $this->productName($shopifyProduct),
            'description' => $shopifyProduct['descriptionHtml'] ? trim($shopifyProduct['descriptionHtml']) : null,
            'status' => $this->mapStatus($shopifyProduct['status']),
        ]);

        $variants = $shopifyProduct['variants'] ?? [];

        // Build a map of variantGid → Inventory for existing records
        $existingByVariantGid = [];
        foreach ($colorway->inventories()->get() as $inventory) {
            $variantGid = $inventory->getExternalIdFor($integration, 'shopify_variant');
            if ($variantGid) {
                $existingByVariantGid[$variantGid] = $inventory;
            }
        }

        $incomingGids = array_flip(array_column($variants, 'gid'));

        // Retire bases whose variants were removed in Shopify
        foreach ($existingByVariantGid as $variantGid => $inventory) {
            if (! isset($incomingGids[$variantGid])) {
                Base::find($inventory->base_id)?->update(['status' => BaseStatus::Retired]);
            }
        }

        $productNumericId = $this->numericId($shopifyProduct['gid']);

        // Update prices for existing variants; create new variants
        foreach ($variants as $variant) {
            $variantGid = $variant['gid'];

            if (isset($existingByVariantGid[$variantGid])) {
                $price = $this->parsePrice($variant['price'] ?? '');
                if ($price !== null) {
                    Base::find($existingByVariantGid[$variantGid]->base_id)?->update(['retail_price' => $price]);
                }
            } else {
                try {
                    $base = $this->findOrCreateBase($variant, $shopifyProduct['title'] ?? '', $integration->account_id);
                    $inventory = Inventory::create([
                        'account_id' => $integration->account_id,
                        'colorway_id' => $colorway->id,
                        'base_id' => $base->id,
                        'quantity' => 0,
                    ]);
                    ExternalIdentifier::create([
                        'integration_id' => $integration->id,
                        'identifiable_type' => Inventory::class,
                        'identifiable_id' => $inventory->id,
                        'external_type' => 'shopify_variant',
                        'external_id' => $variantGid,
                        'data' => [
                            'admin_url' => "https://{$shop}/admin/products/{$productNumericId}/variants/{$this->numericId($variantGid)}",
                        ],
                    ]);
                } catch (\Throwable $e) {
                    $this->log(
                        $integration,
                        $colorway,
                        IntegrationLogStatus::Warning,
                        "Failed to sync variant {$variantGid}: {$e->getMessage()}",
                        ['shopify_gid' => $shopifyProduct['gid'], 'variant_gid' => $variantGid]
                    );
                }
            }
        }

        $this->log(
            $integration,
            $colorway,
            IntegrationLogStatus::Success,
            "Updated Shopify product '{$colorway->name}' (Colorway #{$colorway->id})",
            ['shopify_gid' => $shopifyProduct['gid']]
        );
    }

    private function createVariants(Colorway $colorway, array $shopifyProduct, Integration $integration, string $shop): void
    {
        $productNumericId = $this->numericId($shopifyProduct['gid']);

        foreach ($shopifyProduct['variants'] ?? [] as $variant) {
            try {
                $base = $this->findOrCreateBase($variant, $shopifyProduct['title'] ?? '', $integration->account_id);
                $inventory = Inventory::create([
                    'account_id' => $integration->account_id,
                    'colorway_id' => $colorway->id,
                    'base_id' => $base->id,
                    'quantity' => 0,
                ]);
                ExternalIdentifier::create([
                    'integration_id' => $integration->id,
                    'identifiable_type' => Inventory::class,
                    'identifiable_id' => $inventory->id,
                    'external_type' => 'shopify_variant',
                    'external_id' => $variant['gid'],
                    'data' => [
                        'admin_url' => "https://{$shop}/admin/products/{$productNumericId}/variants/{$this->numericId($variant['gid'])}",
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->log(
                    $integration,
                    $colorway,
                    IntegrationLogStatus::Warning,
                    "Failed to sync variant {$variant['gid']}: {$e->getMessage()}",
                    ['shopify_gid' => $shopifyProduct['gid'], 'variant_gid' => $variant['gid']]
                );
            }
        }
    }

    private function findOrCreateBase(array $variant, string $productTitle, int $accountId): Base
    {
        $descriptor = $this->variantDescriptor($variant, $productTitle);

        $existing = Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Base::create([
            'account_id' => $accountId,
            'descriptor' => $descriptor,
            'status' => BaseStatus::Active,
            'retail_price' => $this->parsePrice($variant['price'] ?? ''),
        ]);
    }

    private function syncImage(Colorway $colorway, array $shopifyProduct): void
    {
        $imageUrl = $shopifyProduct['featuredImage']['url'] ?? null;
        if (! $imageUrl) {
            return;
        }

        $alreadySynced = $colorway->media()
            ->get()
            ->contains(fn (Media $m) => ($m->metadata['source'] ?? null) === 'shopify');

        if ($alreadySynced) {
            return;
        }

        $fileName = basename(parse_url($imageUrl, PHP_URL_PATH));
        $disk = config('filesystems.media_disk', 'public');
        $relativePath = "colorways/{$colorway->id}/{$fileName}";

        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (! $response->successful()) {
                Log::warning("Shopify image download failed for colorway {$colorway->id}: HTTP {$response->status()}", [
                    'url' => $imageUrl,
                ]);

                return;
            }

            Storage::disk($disk)->put($relativePath, $response->body());
        } catch (\Throwable $e) {
            Log::warning("Shopify image download failed for colorway {$colorway->id}: {$e->getMessage()}", [
                'url' => $imageUrl,
            ]);

            return;
        }

        Media::create([
            'mediable_type' => Colorway::class,
            'mediable_id' => $colorway->id,
            'file_path' => $relativePath,
            'disk' => $disk,
            'file_name' => $fileName,
            'mime_type' => $response->header('Content-Type') ?: null,
            'size' => strlen($response->body()),
            'is_primary' => true,
            'metadata' => [
                'source' => 'shopify',
                'original_url' => $imageUrl,
            ],
        ]);
    }

    private function productName(array $product): string
    {
        return trim($product['title'] ?? '') ?: 'Untitled';
    }

    private function variantDescriptor(array $variant, string $productTitle): string
    {
        $title = trim($variant['title'] ?? '');
        if ($title === '' || $title === self::DEFAULT_VARIANT_TITLE) {
            return trim($productTitle) ?: 'Untitled';
        }

        return $title;
    }

    private function mapStatus(string $shopifyStatus): ColorwayStatus
    {
        return self::STATUS_MAP[$shopifyStatus] ?? ColorwayStatus::Active;
    }

    private function parsePrice(string $priceStr): ?float
    {
        $trimmed = trim($priceStr);
        if ($trimmed === '') {
            return null;
        }

        return (float) $trimmed;
    }

    private function numericId(string $gid): string
    {
        return last(explode('/', $gid)) ?? '';
    }

    private function shopDomain(Integration $integration): string
    {
        return $integration->getShopifyConfig()['shop'] ?? '';
    }

    private function shopifyClientFor(Integration $integration): ShopifyGraphqlClient
    {
        if ($this->clientOverride !== null) {
            return $this->clientOverride;
        }

        $config = $integration->getShopifyConfig();
        if (! $config) {
            throw new \RuntimeException('Shopify integration not configured.');
        }

        return new ShopifyGraphqlClient($config['shop'], $config['access_token']);
    }

    private function log(Integration $integration, Colorway $colorway, IntegrationLogStatus $status, string $message, array $metadata = []): void
    {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => Colorway::class,
            'loggable_id' => $colorway->id,
            'status' => $status,
            'message' => $message,
            'metadata' => $metadata,
            'synced_at' => now(),
        ]);
    }
}
