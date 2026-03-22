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
use App\Services\Shopify\ShopifyGraphqlClient;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $this->client = Mockery::mock(ShopifyGraphqlClient::class);
    $this->service = new InventorySyncService(null, $this->client);
});

function variantInventoryResponse(string $gid, int $quantity): array
{
    return ['variantGid' => $gid, 'inventoryQuantity' => $quantity, 'inventoryItemGid' => null];
}

it('syncs all variant mappings for an integration', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 0,
    ]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    $this->client->shouldReceive('getVariantInventory')
        ->with('gid://shopify/ProductVariant/10')
        ->once()
        ->andReturn(variantInventoryResponse('gid://shopify/ProductVariant/10', 15));

    $result = $this->service->syncAll($this->integration);

    expect($result->updated)->toBe(1);
    expect($result->failed)->toBe(0);
    expect($inventory->fresh()->quantity)->toBe(15);
});

it('syncs multiple variant mappings', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base1 = Base::factory()->create(['account_id' => $this->account->id]);
    $base2 = Base::factory()->create(['account_id' => $this->account->id]);

    $inv1 = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base1->id,
        'quantity' => 0,
    ]);
    $inv2 = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base2->id,
        'quantity' => 0,
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

    $this->client->shouldReceive('getVariantInventory')
        ->with('gid://shopify/ProductVariant/10')->andReturn(variantInventoryResponse('gid://shopify/ProductVariant/10', 5));
    $this->client->shouldReceive('getVariantInventory')
        ->with('gid://shopify/ProductVariant/20')->andReturn(variantInventoryResponse('gid://shopify/ProductVariant/20', 8));

    $result = $this->service->syncAll($this->integration);

    expect($result->updated)->toBe(2);
    expect($result->failed)->toBe(0);
    expect($inv1->fresh()->quantity)->toBe(5);
    expect($inv2->fresh()->quantity)->toBe(8);
});

it('records failure and continues when one variant throws', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base1 = Base::factory()->create(['account_id' => $this->account->id]);
    $base2 = Base::factory()->create(['account_id' => $this->account->id]);

    $inv1 = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base1->id,
        'quantity' => 0,
    ]);
    $inv2 = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base2->id,
        'quantity' => 0,
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

    $this->client->shouldReceive('getVariantInventory')
        ->with('gid://shopify/ProductVariant/10')
        ->andThrow(new RuntimeException('Shopify API error'));

    $this->client->shouldReceive('getVariantInventory')
        ->with('gid://shopify/ProductVariant/20')
        ->andReturn(variantInventoryResponse('gid://shopify/ProductVariant/20', 8));

    $result = $this->service->syncAll($this->integration);

    expect($result->updated)->toBe(1);
    expect($result->failed)->toBe(1);
    expect($result->errors[0]['entity_gid'])->toBe('gid://shopify/ProductVariant/10');
    expect($inv2->fresh()->quantity)->toBe(8);
});

it('returns zero updated when no variant mappings exist', function () {
    $this->client->shouldNotReceive('getVariantInventory');

    $result = $this->service->syncAll($this->integration);

    expect($result->updated)->toBe(0);
    expect($result->failed)->toBe(0);
});

it('conflict detection still works during syncAll', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $lastSync = now()->subMinutes(10);

    $inventory = Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 7,
        'last_synced_at' => $lastSync,
    ]);
    $inventory->updated_at = now()->subMinutes(5);
    $inventory->save();

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    $this->client->shouldReceive('getVariantInventory')
        ->once()
        ->andReturn(variantInventoryResponse('gid://shopify/ProductVariant/10', 20));

    $result = $this->service->syncAll($this->integration);

    expect($result->updated)->toBe(1);
    expect($inventory->fresh()->quantity)->toBe(20);

    $log = IntegrationLog::where('loggable_id', $inventory->id)
        ->where('loggable_type', Inventory::class)
        ->where('status', IntegrationLogStatus::Warning)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->metadata['conflict'])->toBeTrue();
});
