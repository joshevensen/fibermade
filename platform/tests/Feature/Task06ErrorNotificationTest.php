<?php

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncBaseDeletedToShopifyJob;
use App\Jobs\SyncBaseToShopifyJob;
use App\Jobs\SyncCollectionDeletedToShopifyJob;
use App\Jobs\SyncCollectionToShopifyJob;
use App\Jobs\SyncColorwayCatalogToShopifyJob;
use App\Jobs\SyncColorwayImagesToShopifyJob;
use App\Jobs\SyncInventoryToShopifyJob;
use App\Models\Account;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Models\Media;
use App\Models\User;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyApiException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

// ─── Integration::flagSyncError / clearSyncErrors ────────────────────────────

test('flagSyncError sets has_sync_errors to true in settings', function () {
    $integration = Integration::factory()->create([
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $integration->flagSyncError();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();
});

test('flagSyncError preserves existing settings keys', function () {
    $integration = Integration::factory()->create([
        'settings' => ['shop' => 'test.myshopify.com', 'auto_sync' => true],
    ]);

    $integration->flagSyncError();

    $integration->refresh();
    expect($integration->settings['shop'])->toBe('test.myshopify.com');
    expect($integration->settings['auto_sync'])->toBeTrue();
    expect($integration->settings['has_sync_errors'])->toBeTrue();
});

test('clearSyncErrors sets has_sync_errors to false in settings', function () {
    $integration = Integration::factory()->create([
        'settings' => ['shop' => 'test.myshopify.com', 'has_sync_errors' => true],
    ]);

    $integration->clearSyncErrors();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeFalse();
});

test('clearSyncErrors works when has_sync_errors was not previously set', function () {
    $integration = Integration::factory()->create([
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $integration->clearSyncErrors();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeFalse();
});

// ─── SyncColorwayCatalogToShopifyJob ─────────────────────────────────────────

test('SyncColorwayCatalogToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $account->id,
        'status' => ColorwayStatus::Active,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => null,
                    'userErrors' => [['field' => 'id', 'message' => 'Product not found']],
                ],
            ],
        ]),
    ]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
});

test('SyncColorwayCatalogToShopifyJob failed() method is callable without exception', function () {
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $job = new SyncColorwayCatalogToShopifyJob($colorway, 'updated');

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncColorwayImagesToShopifyJob ──────────────────────────────────────────

test('SyncColorwayImagesToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    // Attach a media record so syncImages actually calls productUpdate
    Media::create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/test.jpg',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'is_primary' => true,
    ]);

    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::sequence()
            // getProductMedia query — no existing media
            ->push(['data' => ['product' => ['media' => ['edges' => []]]]])
            // productUpdate with media — returns userErrors
            ->push(['data' => ['productUpdate' => [
                'product' => null,
                'userErrors' => [['field' => null, 'message' => 'Image upload failed']],
            ]]]),
    ]);

    $job = new SyncColorwayImagesToShopifyJob($colorway);
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
});

test('SyncColorwayImagesToShopifyJob failed() method is callable without exception', function () {
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $job = new SyncColorwayImagesToShopifyJob($colorway);

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncBaseToShopifyJob ─────────────────────────────────────────────────────

test('SyncBaseToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $base = Base::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/1',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => null,
                    'userErrors' => [['message' => 'Variant not found']],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseToShopifyJob($base, 'updated');
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
});

test('SyncBaseToShopifyJob failed() method is callable without exception', function () {
    $account = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);

    $job = new SyncBaseToShopifyJob($base, 'updated');

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncBaseDeletedToShopifyJob ─────────────────────────────────────────────

test('SyncBaseDeletedToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $base = Base::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/1',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkDelete' => [
                    'product' => null,
                    'userErrors' => [['message' => 'Variant not found']],
                ],
            ],
        ]),
    ]);

    $job = new SyncBaseDeletedToShopifyJob($base->id, $account->id);
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();
});

test('SyncBaseDeletedToShopifyJob failed() method is callable without exception', function () {
    $job = new SyncBaseDeletedToShopifyJob(1, 1);

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncCollectionToShopifyJob ───────────────────────────────────────────────

test('SyncCollectionToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $collection = Collection::factory()->create(['account_id' => $account->id]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionCreate' => [
                    'collection' => null,
                    'userErrors' => [['message' => 'Error creating collection']],
                ],
            ],
        ]),
    ]);

    $job = new SyncCollectionToShopifyJob($collection, 'created');
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
});

test('SyncCollectionToShopifyJob failed() method is callable without exception', function () {
    $account = Account::factory()->creator()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);

    $job = new SyncCollectionToShopifyJob($collection, 'created');

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncCollectionDeletedToShopifyJob ───────────────────────────────────────

test('SyncCollectionDeletedToShopifyJob flags sync error on ShopifyApiException', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $collection = Collection::factory()->create(['account_id' => $account->id]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collection->id,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/1',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'collectionDelete' => [
                    'deletedCollectionId' => null,
                    'userErrors' => [['message' => 'Collection not found']],
                ],
            ],
        ]),
    ]);

    $job = new SyncCollectionDeletedToShopifyJob($collection->id, $account->id);
    $job->handle();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
});

test('SyncCollectionDeletedToShopifyJob failed() method is callable without exception', function () {
    $job = new SyncCollectionDeletedToShopifyJob(1, 1);

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── SyncInventoryToShopifyJob ────────────────────────────────────────────────

test('SyncInventoryToShopifyJob flags sync error on ShopifyApiException', function () {
    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $mock = $this->mock(InventorySyncService::class);
    $mock->shouldReceive('pushInventoryToShopify')
        ->once()
        ->andThrow(new ShopifyApiException('API error', []));

    $job = new SyncInventoryToShopifyJob($inventory->id, $integration->id);
    $job->handle(app(InventorySyncService::class));

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeTrue();

    $log = IntegrationLog::where('integration_id', $integration->id)
        ->where('status', IntegrationLogStatus::Error)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->metadata['operation'])->toBe('inventory_sync');
});

test('SyncInventoryToShopifyJob failed() method is callable without exception', function () {
    $job = new SyncInventoryToShopifyJob(1, 1);

    expect(fn () => $job->failed(new RuntimeException('Test failure')))->not->toThrow(Throwable::class);
});

// ─── HandleInertiaRequests shared data ───────────────────────────────────────

test('HandleInertiaRequests shares has_sync_errors true when integration has flag set', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com', 'has_sync_errors' => true],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('shopify.has_sync_errors', true));
});

test('HandleInertiaRequests shares has_sync_errors false when no errors', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('shopify.has_sync_errors', false));
});

test('HandleInertiaRequests shares has_sync_errors false when no integration exists', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('shopify.has_sync_errors', false)
            ->where('shopify.integration_id', null)
        );
});

test('HandleInertiaRequests shares integration_id when integration exists', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('shopify.integration_id', $integration->id));
});

// ─── dismissErrors controller action ─────────────────────────────────────────

test('dismissErrors clears sync errors and redirects back', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com', 'has_sync_errors' => true],
    ]);

    $this->actingAs($user)
        ->post(route('shopify.errors.dismiss', $integration))
        ->assertRedirect();

    $integration->refresh();
    expect($integration->settings['has_sync_errors'])->toBeFalse();
});

test('dismissErrors returns 403 for integration belonging to another account', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $otherAccount = Account::factory()->creator()->create();
    $otherIntegration = Integration::factory()->create([
        'account_id' => $otherAccount->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'other.myshopify.com', 'has_sync_errors' => true],
    ]);

    $this->actingAs($user)
        ->post(route('shopify.errors.dismiss', $otherIntegration))
        ->assertForbidden();
});

test('dismissErrors redirects unauthenticated users to login', function () {
    $integration = Integration::factory()->create();

    $this->post(route('shopify.errors.dismiss', $integration))
        ->assertRedirect(route('login'));
});
