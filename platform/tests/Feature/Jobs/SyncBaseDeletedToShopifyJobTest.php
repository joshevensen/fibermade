<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncBaseDeletedToShopifyJob;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Config::set('services.shopify.catalog_sync_enabled', true);

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('SyncBaseDeletedToShopifyJob groups variants by product and calls productVariantsBulkDelete', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);

    $colorway1 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $this->account->id]);

    $inv1 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway1->id, 'base_id' => $base->id]);
    $inv2 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway2->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway1->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway2->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv1->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv2->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/20',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseDeletedToShopifyJob($base->id, $this->account->id);
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkDelete'));
});

test('SyncBaseDeletedToShopifyJob removes ExternalIdentifier records after delete', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    $variantIdentifier = ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseDeletedToShopifyJob($base->id, $this->account->id);
    $job->handle();

    expect(ExternalIdentifier::find($variantIdentifier->id))->toBeNull();
});

test('SyncBaseDeletedToShopifyJob deletes inventory records', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseDeletedToShopifyJob($base->id, $this->account->id);
    $job->handle();

    expect(Inventory::find($inv->id))->toBeNull();
});

test('SyncBaseDeletedToShopifyJob logs success after deleting variants', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseDeletedToShopifyJob($base->id, $this->account->id);
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $base->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('base_deleted');
    expect($log->metadata['count'])->toBe(1);
});

test('SyncBaseDeletedToShopifyJob silently continues when Shopify API fails', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => null,
                    'userErrors' => [['field' => 'variantsIds', 'message' => 'Variant not found']],
                ],
            ],
        ]),
    ]);

    // Should not throw
    $job = new SyncBaseDeletedToShopifyJob($base->id, $this->account->id);
    $job->handle();

    // Inventory still deleted (cleanup always runs)
    expect(Inventory::find($inv->id))->toBeNull();
});

test('SyncBaseDeletedToShopifyJob returns early when no active integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $otherAccount->id]);

    Http::fake();

    $job = new SyncBaseDeletedToShopifyJob($base->id, $otherAccount->id);
    $job->handle();

    Http::assertNothingSent();
});

test('SyncBaseDeletedToShopifyJob has retry configuration', function () {
    $job = new SyncBaseDeletedToShopifyJob(1, 1);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});
