<?php

use App\Enums\IntegrationType;
use App\Jobs\PushCatalogJob;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Integration;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifyGraphqlClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

// ─── Colorways step ───────────────────────────────────────────────────────────

it('calls pushAllInventoryForColorway for each colorway in the account', function () {
    Colorway::factory()->count(3)->create(['account_id' => $this->account->id]);

    $mockInventorySync = $this->mock(InventorySyncService::class);
    $mockInventorySync->shouldReceive('pushAllInventoryForColorway')
        ->times(3)
        ->andReturn(['variants_updated' => 0, 'variants_created' => 1, 'products_created' => 1, 'skipped' => 0]);

    $this->app->bind(ShopifyGraphqlClient::class, fn () => Mockery::mock(ShopifyGraphqlClient::class));

    $job = new PushCatalogJob($this->integration->id);
    $job->handle($mockInventorySync);
});

it('skips colorways from other accounts', function () {
    $otherAccount = Account::factory()->creator()->create();
    Colorway::factory()->create(['account_id' => $this->account->id]);
    Colorway::factory()->create(['account_id' => $otherAccount->id]);

    $mockInventorySync = $this->mock(InventorySyncService::class);
    $mockInventorySync->shouldReceive('pushAllInventoryForColorway')
        ->once()
        ->andReturn(['variants_updated' => 1, 'variants_created' => 0, 'products_created' => 0, 'skipped' => 0]);

    $this->app->bind(ShopifyGraphqlClient::class, fn () => Mockery::mock(ShopifyGraphqlClient::class));

    $job = new PushCatalogJob($this->integration->id);
    $job->handle($mockInventorySync);
});

it('continues after per-colorway failures without aborting', function () {
    Colorway::factory()->count(3)->create(['account_id' => $this->account->id]);

    $mockInventorySync = $this->mock(InventorySyncService::class);
    $callCount = 0;
    $mockInventorySync->shouldReceive('pushAllInventoryForColorway')
        ->times(3)
        ->andReturnUsing(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 2) {
                throw new RuntimeException('Shopify API error');
            }

            return ['variants_updated' => 1, 'variants_created' => 0, 'products_created' => 0, 'skipped' => 0];
        });

    $this->app->bind(ShopifyGraphqlClient::class, fn () => Mockery::mock(ShopifyGraphqlClient::class));

    $job = new PushCatalogJob($this->integration->id);
    $job->handle($mockInventorySync);

    // Should still complete (not throw)
    $this->integration->refresh();
    expect($this->integration->settings['push_sync']['status'])->toBe('complete');
    expect($this->integration->settings['push_sync']['last_result']['colorways']['failed'])->toBe(1);
});

// ─── Collections step ─────────────────────────────────────────────────────────

it('includes collections in last_result after push completes', function () {
    Collection::factory()->count(2)->create(['account_id' => $this->account->id]);

    $collectionCounter = 0;
    Http::fake([
        'test.myshopify.com/*' => Http::sequence()
            ->push(['data' => ['collectionCreate' => ['collection' => ['id' => 'gid://shopify/Collection/1', 'title' => 'Test', 'handle' => 'test'], 'userErrors' => []]]])
            ->push(['data' => ['collectionCreate' => ['collection' => ['id' => 'gid://shopify/Collection/2', 'title' => 'Test 2', 'handle' => 'test-2'], 'userErrors' => []]]]),
    ]);

    $mockInventorySync = $this->mock(InventorySyncService::class);
    $mockInventorySync->shouldReceive('pushAllInventoryForColorway')->andReturn([
        'variants_updated' => 0, 'variants_created' => 0, 'products_created' => 0, 'skipped' => 0,
    ]);

    $job = new PushCatalogJob($this->integration->id);
    $job->handle($mockInventorySync);

    $this->integration->refresh();
    $pushSync = $this->integration->settings['push_sync'];

    expect($pushSync['status'])->toBe('complete');
    expect($pushSync['last_result'])->toHaveKey('collections');
});

// ─── Sync state management ────────────────────────────────────────────────────

it('sets push_sync status to running then complete', function () {
    $mockInventorySync = $this->mock(InventorySyncService::class);
    $mockInventorySync->shouldReceive('pushAllInventoryForColorway')->andReturn([
        'variants_updated' => 0, 'variants_created' => 0, 'products_created' => 0, 'skipped' => 0,
    ]);

    $this->app->bind(ShopifyGraphqlClient::class, fn () => Mockery::mock(ShopifyGraphqlClient::class));

    $job = new PushCatalogJob($this->integration->id);
    $job->handle($mockInventorySync);

    $this->integration->refresh();
    $pushSync = $this->integration->settings['push_sync'];

    expect($pushSync['status'])->toBe('complete');
    expect($pushSync['current_step'])->toBeNull();
    expect($pushSync['completed_at'])->not->toBeNull();
    expect($pushSync['last_result'])->toHaveKeys(['colorways', 'collections']);
});

it('sets push_sync status to failed when job fails', function () {
    $job = new PushCatalogJob($this->integration->id);
    $job->failed(new RuntimeException('Something went wrong'));

    $this->integration->refresh();
    $pushSync = $this->integration->settings['push_sync'];

    expect($pushSync['status'])->toBe('failed');
    expect($pushSync['completed_at'])->not->toBeNull();
});

it('returns early when integration is not found', function () {
    $mockInventorySync = $this->mock(InventorySyncService::class);
    $mockInventorySync->shouldNotReceive('pushAllInventoryForColorway');

    $job = new PushCatalogJob(999999);
    $job->handle($mockInventorySync);
});

// ─── Queue dispatch ───────────────────────────────────────────────────────────

it('is queued when dispatched', function () {
    Queue::fake();

    PushCatalogJob::dispatch($this->integration->id);

    Queue::assertPushed(PushCatalogJob::class);
});
