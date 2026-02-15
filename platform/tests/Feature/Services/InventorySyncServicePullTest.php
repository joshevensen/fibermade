<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\InventorySyncService;
use Carbon\Carbon;

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

test('pullInventoryFromShopify updates Fibermade inventory and logs success', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $lastSync = Carbon::now()->subHour();
    $inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
        'last_synced_at' => $lastSync,
    ]);
    $inventory->updated_at = $lastSync;
    $inventory->save();

    $variantGid = 'gid://shopify/ProductVariant/123';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $service = new InventorySyncService;
    $result = $service->pullInventoryFromShopify($variantGid, 10, $this->integration, 'webhook');

    expect($result)->toBeTrue();
    $inventory->refresh();
    expect($inventory->quantity)->toBe(10);
    expect($inventory->last_synced_at)->not->toBeNull();

    $log = IntegrationLog::where('loggable_id', $inventory->id)
        ->where('loggable_type', Inventory::class)
        ->latest()
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['direction'])->toBe('pull');
    expect($log->metadata['sync_source'])->toBe('webhook');
});

test('pullInventoryFromShopify logs conflict warning when both changed since last sync', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $lastSync = Carbon::now()->subMinutes(10);
    $inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 7,
        'last_synced_at' => $lastSync,
    ]);
    $inventory->updated_at = Carbon::now()->subMinutes(5);
    $inventory->save();

    $variantGid = 'gid://shopify/ProductVariant/456';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $service = new InventorySyncService;
    $result = $service->pullInventoryFromShopify($variantGid, 12, $this->integration, 'webhook');

    expect($result)->toBeTrue();
    $inventory->refresh();
    expect($inventory->quantity)->toBe(12);

    $log = IntegrationLog::where('loggable_id', $inventory->id)
        ->where('loggable_type', Inventory::class)
        ->latest()
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Warning);
    expect($log->metadata['conflict'])->toBeTrue();
    expect($log->metadata['fibermade_quantity'])->toBe(7);
    expect($log->metadata['shopify_quantity'])->toBe(12);
});

test('pullInventoryFromShopify does not log conflict when within 60 seconds of last sync', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $lastSync = Carbon::now()->subSeconds(30);
    $inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
        'last_synced_at' => $lastSync,
    ]);
    $inventory->updated_at = Carbon::now()->subSeconds(10);
    $inventory->save();

    $variantGid = 'gid://shopify/ProductVariant/789';
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => $variantGid,
    ]);

    $service = new InventorySyncService;
    $service->pullInventoryFromShopify($variantGid, 8, $this->integration, 'webhook');

    $log = IntegrationLog::where('loggable_id', $inventory->id)
        ->where('loggable_type', Inventory::class)
        ->latest()
        ->first();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['conflict'] ?? null)->not->toBeTrue();
});

test('pullInventoryFromShopify returns false when no ExternalIdentifier for variant', function () {
    $service = new InventorySyncService;
    $result = $service->pullInventoryFromShopify('gid://shopify/ProductVariant/999', 5, $this->integration, 'webhook');

    expect($result)->toBeFalse();
});
