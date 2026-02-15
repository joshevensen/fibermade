<?php

use App\Enums\BaseStatus;
use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use App\Services\Shopify\ShopifySyncService;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('pushAllInventoryForColorway creates IntegrationLog entry on success', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id, 'status' => BaseStatus::Active]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $mockSync = \Mockery::mock(ShopifySyncService::class);
    $mockSync->shouldReceive('createProduct')
        ->once()
        ->andReturn([
            'product_id' => 'gid://shopify/Product/1',
            'variant_ids' => ['gid://shopify/ProductVariant/1'],
        ]);
    $mockSync->shouldReceive('setVariantInventory')->andReturnNull();
    $mockSync->shouldReceive('syncImages')->andReturnNull();

    $service = new InventorySyncService($mockSync);
    $service->pushAllInventoryForColorway($colorway, $this->integration, 'manual_push');

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_type', Colorway::class)
        ->where('loggable_id', $colorway->id)
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['sync_source'])->toBe('manual_push');
    expect($log->metadata['direction'])->toBe('push');
});
