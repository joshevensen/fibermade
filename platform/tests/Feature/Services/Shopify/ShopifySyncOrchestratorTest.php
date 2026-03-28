<?php

use App\Data\Shopify\SyncResult;
use App\Exceptions\SyncAlreadyRunningException;
use App\Jobs\SyncShopifyCollectionsJob;
use App\Jobs\SyncShopifyInventoryJob;
use App\Jobs\SyncShopifyProductsJob;
use App\Models\Integration;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyCollectionSyncService;
use App\Services\Shopify\ShopifyProductSyncService;
use App\Services\Shopify\ShopifySyncOrchestrator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->integration = Integration::factory()->create([
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $this->orchestrator = new ShopifySyncOrchestrator;
});

// ─── ShopifySyncOrchestrator ──────────────────────────────────────────────────

it('syncAll dispatches chained jobs in correct order', function () {
    Bus::fake();

    $this->orchestrator->syncAll($this->integration);

    Bus::assertChained([
        SyncShopifyProductsJob::class,
        SyncShopifyCollectionsJob::class,
        SyncShopifyInventoryJob::class,
    ]);
});

it('syncAll sets sync status to running with correct initial state', function () {
    Bus::fake();

    $this->orchestrator->syncAll($this->integration);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('running');
    expect($sync['current_step'])->toBe('products');
    expect($sync['started_at'])->not->toBeNull();
    expect($sync['completed_at'])->toBeNull();
});

it('syncAll throws SyncAlreadyRunningException when a sync is in progress', function () {
    Bus::fake();

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running'];
    $this->integration->update(['settings' => $settings]);

    expect(fn () => $this->orchestrator->syncAll($this->integration))
        ->toThrow(SyncAlreadyRunningException::class);

    Bus::assertNothingDispatched();
});

it('syncProducts dispatches SyncShopifyProductsJob only', function () {
    Bus::fake();

    $this->orchestrator->syncProducts($this->integration);

    Bus::assertDispatched(SyncShopifyProductsJob::class);
    Bus::assertNotDispatched(SyncShopifyCollectionsJob::class);
    Bus::assertNotDispatched(SyncShopifyInventoryJob::class);
});

it('syncProducts sets sync status to running with current_step products', function () {
    Bus::fake();

    $this->orchestrator->syncProducts($this->integration);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('running');
    expect($sync['current_step'])->toBe('products');
});

it('syncProducts throws SyncAlreadyRunningException when a sync is in progress', function () {
    Bus::fake();

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running'];
    $this->integration->update(['settings' => $settings]);

    expect(fn () => $this->orchestrator->syncProducts($this->integration))
        ->toThrow(SyncAlreadyRunningException::class);
});

it('syncCollections dispatches SyncShopifyCollectionsJob only', function () {
    Bus::fake();

    $this->orchestrator->syncCollections($this->integration);

    Bus::assertDispatched(SyncShopifyCollectionsJob::class);
    Bus::assertNotDispatched(SyncShopifyProductsJob::class);
    Bus::assertNotDispatched(SyncShopifyInventoryJob::class);
});

it('syncCollections sets sync status to running with current_step collections', function () {
    Bus::fake();

    $this->orchestrator->syncCollections($this->integration);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('running');
    expect($sync['current_step'])->toBe('collections');
});

it('syncCollections throws SyncAlreadyRunningException when a sync is in progress', function () {
    Bus::fake();

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running'];
    $this->integration->update(['settings' => $settings]);

    expect(fn () => $this->orchestrator->syncCollections($this->integration))
        ->toThrow(SyncAlreadyRunningException::class);
});

it('syncInventory dispatches SyncShopifyInventoryJob only', function () {
    Bus::fake();

    $this->orchestrator->syncInventory($this->integration);

    Bus::assertDispatched(SyncShopifyInventoryJob::class);
    Bus::assertNotDispatched(SyncShopifyProductsJob::class);
    Bus::assertNotDispatched(SyncShopifyCollectionsJob::class);
});

it('syncInventory sets sync status to running with current_step inventory', function () {
    Bus::fake();

    $this->orchestrator->syncInventory($this->integration);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('running');
    expect($sync['current_step'])->toBe('inventory');
});

it('syncInventory throws SyncAlreadyRunningException when a sync is in progress', function () {
    Bus::fake();

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running'];
    $this->integration->update(['settings' => $settings]);

    expect(fn () => $this->orchestrator->syncInventory($this->integration))
        ->toThrow(SyncAlreadyRunningException::class);
});

it('allows a new sync after a previous sync completed', function () {
    Bus::fake();

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'complete'];
    $this->integration->update(['settings' => $settings]);

    $this->orchestrator->syncAll($this->integration);

    Bus::assertChained([
        SyncShopifyProductsJob::class,
        SyncShopifyCollectionsJob::class,
        SyncShopifyInventoryJob::class,
    ]);
});

// ─── SyncShopifyProductsJob ───────────────────────────────────────────────────

it('SyncShopifyProductsJob writes result to integration settings on success', function () {
    $result = new SyncResult(created: 5, updated: 2, failed: 1, errors: [
        ['entity_gid' => 'gid://shopify/Product/1', 'message' => 'Not found'],
    ]);

    $mockService = $this->mock(ShopifyProductSyncService::class);
    $mockService->shouldReceive('syncAll')->once()->andReturn($result);

    // Pre-seed sync state as the orchestrator would
    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'products'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyProductsJob($this->integration);
    $job->handle($mockService);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('complete');
    expect($sync['completed_at'])->not->toBeNull();
    expect($sync['current_step'])->toBe('products');
    expect($sync['last_result']['products']['created'])->toBe(5);
    expect($sync['last_result']['products']['updated'])->toBe(2);
    expect($sync['last_result']['products']['failed'])->toBe(1);
    expect($sync['errors'])->toHaveCount(1);
    expect($sync['errors'][0]['step'])->toBe('products');
    expect($sync['errors'][0]['entity_gid'])->toBe('gid://shopify/Product/1');
});

it('SyncShopifyProductsJob failed() sets sync status to failed', function () {
    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'products'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyProductsJob($this->integration);
    $job->failed(new RuntimeException('Connection timed out'));

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('failed');
    expect($sync['completed_at'])->not->toBeNull();
});

// ─── SyncShopifyCollectionsJob ────────────────────────────────────────────────

it('SyncShopifyCollectionsJob writes result to integration settings on success', function () {
    $result = new SyncResult(created: 3, updated: 1, failed: 0);

    $mockService = $this->mock(ShopifyCollectionSyncService::class);
    $mockService->shouldReceive('syncAll')->once()->andReturn($result);

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'collections'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyCollectionsJob($this->integration);
    $job->handle($mockService);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('complete');
    expect($sync['last_result']['collections']['created'])->toBe(3);
    expect($sync['last_result']['collections']['updated'])->toBe(1);
    expect($sync['last_result']['collections']['failed'])->toBe(0);
    expect($sync['errors'])->toBeEmpty();
});

it('SyncShopifyCollectionsJob failed() sets sync status to failed', function () {
    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'collections'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyCollectionsJob($this->integration);
    $job->failed(new RuntimeException('API error'));

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('failed');
    expect($sync['completed_at'])->not->toBeNull();
});

// ─── SyncShopifyInventoryJob ──────────────────────────────────────────────────

it('SyncShopifyInventoryJob writes result to integration settings on success', function () {
    $result = new SyncResult(updated: 10, failed: 2, errors: [
        ['entity_gid' => 'gid://shopify/ProductVariant/1', 'message' => 'Variant missing'],
        ['entity_gid' => 'gid://shopify/ProductVariant/2', 'message' => 'Quantity error'],
    ]);

    $mockService = $this->mock(InventorySyncService::class);
    $mockService->shouldReceive('syncAll')->once()->andReturn($result);

    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'inventory'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyInventoryJob($this->integration);
    $job->handle($mockService);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('complete');
    expect($sync['last_result']['inventory']['updated'])->toBe(10);
    expect($sync['last_result']['inventory']['failed'])->toBe(2);
    expect($sync['errors'])->toHaveCount(2);
    expect($sync['errors'][0]['step'])->toBe('inventory');
});

it('SyncShopifyInventoryJob failed() sets sync status to failed', function () {
    $settings = $this->integration->settings ?? [];
    $settings['sync'] = ['status' => 'running', 'current_step' => 'inventory'];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyInventoryJob($this->integration);
    $job->failed(new RuntimeException('Shopify rate limit'));

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    expect($sync['status'])->toBe('failed');
    expect($sync['completed_at'])->not->toBeNull();
});

// ─── Sync state preserves previous step results across chained jobs ───────────

it('sync result for later steps preserves earlier step results', function () {
    $collectionsResult = new SyncResult(created: 2, updated: 0, failed: 0);
    $mockService = $this->mock(ShopifyCollectionSyncService::class);
    $mockService->shouldReceive('syncAll')->once()->andReturn($collectionsResult);

    // Simulate products step already having written its results
    $settings = $this->integration->settings ?? [];
    $settings['sync'] = [
        'status' => 'running',
        'current_step' => 'collections',
        'last_result' => ['products' => ['created' => 5, 'updated' => 2, 'failed' => 0]],
        'errors' => [],
    ];
    $this->integration->update(['settings' => $settings]);

    $job = new SyncShopifyCollectionsJob($this->integration);
    $job->handle($mockService);

    $this->integration->refresh();
    $sync = $this->integration->settings['sync'];

    // Products result must be preserved
    expect($sync['last_result']['products']['created'])->toBe(5);
    // Collections result written
    expect($sync['last_result']['collections']['created'])->toBe(2);
});

// ─── Queue dispatch ───────────────────────────────────────────────────────────

it('jobs are queued when dispatched via the orchestrator', function () {
    Queue::fake();

    $this->orchestrator->syncProducts($this->integration);

    Queue::assertPushed(SyncShopifyProductsJob::class);
});
