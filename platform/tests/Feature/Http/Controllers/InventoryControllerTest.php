<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\User;
use App\Services\InventorySyncService;

test('creator can view inventory index', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('inventory.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/inventory/InventoryIndexPage')
        ->has('colorways')
        ->has('colorwayStatusOptions')
        ->has('collectionOptions')
    );
});

test('creator can update inventory quantity', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->patch(route('inventory.updateQuantity'), [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ]);

    $response->assertRedirect();
    $inventory = Inventory::where('account_id', $account->id)
        ->where('colorway_id', $colorway->id)
        ->where('base_id', $base->id)
        ->first();
    expect($inventory)->not->toBeNull();
    expect($inventory->quantity)->toBe(10);
});

test('creator can push inventory to Shopify and gets success', function () {
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
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $this->mock(InventorySyncService::class, function ($mock) {
        $mock->shouldReceive('pushAllInventoryForColorway')
            ->andReturn([
                'variants_updated' => 2,
                'variants_created' => 1,
                'products_created' => 0,
                'skipped' => 0,
            ]);
    });

    $response = $this->actingAs($user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('3 variants updated');
});
