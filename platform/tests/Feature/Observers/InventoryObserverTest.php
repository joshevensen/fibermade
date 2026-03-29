<?php

use App\Enums\IntegrationType;
use App\Jobs\PushInventoryJob;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);
    Queue::fake();

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com', 'catalog_sync_enabled' => true],
    ]);

    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);

    $this->inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ]);
});

test('InventoryObserver dispatches PushInventoryJob when quantity changes', function () {
    $this->inventory->update(['quantity' => 20]);

    Queue::assertPushed(PushInventoryJob::class, function ($job) {
        return $job->inventoryId === $this->inventory->id
            && $job->integrationId === $this->integration->id;
    });
});

test('InventoryObserver does not dispatch when quantity did not change', function () {
    $this->inventory->update(['sync_status' => 'synced', 'last_synced_at' => now()]);

    Queue::assertNotPushed(PushInventoryJob::class);
});

test('InventoryObserver does not dispatch when no active Shopify integration exists', function () {
    $otherAccount = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $otherAccount->id]);
    $base = Base::factory()->create(['account_id' => $otherAccount->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $otherAccount->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
    ]);

    $inventory->update(['quantity' => 15]);

    Queue::assertNotPushed(PushInventoryJob::class);
});

test('InventoryObserver does not dispatch when catalog sync is disabled', function () {
    $this->integration->update([
        'settings' => ['shop' => 'test.myshopify.com', 'catalog_sync_enabled' => false],
    ]);

    $this->inventory->update(['quantity' => 30]);

    Queue::assertNotPushed(PushInventoryJob::class);
});

test('PushInventoryJob calls pushInventoryToShopify with correct arguments', function () {
    $mockService = Mockery::mock(InventorySyncService::class);
    $mockService->shouldReceive('pushInventoryToShopify')
        ->once()
        ->withArgs(function ($inv, $integration, string $syncSource) {
            return $inv->id === $this->inventory->id
                && $integration->id === $this->integration->id
                && $syncSource === 'observer';
        });

    $job = new PushInventoryJob($this->inventory->id, $this->integration->id);
    $job->handle($mockService);
});

test('PushInventoryJob returns early when integration no longer exists', function () {
    $mockService = Mockery::mock(InventorySyncService::class);
    $mockService->shouldNotReceive('pushInventoryToShopify');

    $job = new PushInventoryJob($this->inventory->id, 99999);
    $job->handle($mockService);
});

test('PushInventoryJob returns early when inventory record no longer exists', function () {
    $mockService = Mockery::mock(InventorySyncService::class);
    $mockService->shouldNotReceive('pushInventoryToShopify');

    $job = new PushInventoryJob(99999, $this->integration->id);
    $job->handle($mockService);
});
