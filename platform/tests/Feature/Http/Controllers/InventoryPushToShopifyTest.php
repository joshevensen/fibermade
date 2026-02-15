<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\User;
use App\Services\InventorySyncService;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $this->account->id]);
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
});

test('guests cannot push to Shopify', function () {
    $response = $this->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('login'));
});

test('push to Shopify requires Shopify integration', function () {
    Colorway::factory()->create(['account_id' => $this->account->id]);
    Base::factory()->create(['account_id' => $this->account->id]);

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Shopify integration is not configured');
});

test('push to Shopify requires active integration with config', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => false,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('error');
});

test('push to Shopify returns success with variant count', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
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

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('3 variants updated');
});

test('push to Shopify requires creator account authorization', function () {
    $userNoAccount = User::factory()->create(['account_id' => null]);

    $response = $this->actingAs($userNoAccount)->post(route('inventory.pushToShopify'));

    $response->assertStatus(403);
});

test('push to Shopify creates new products for colorways without external_id', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $this->mock(InventorySyncService::class, function ($mock) use ($colorway) {
        $mock->shouldReceive('pushAllInventoryForColorway')
            ->once()
            ->with(\Mockery::on(fn ($c) => $c->id === $colorway->id), \Mockery::type(Integration::class), 'manual_push')
            ->andReturn([
                'variants_updated' => 0,
                'variants_created' => 2,
                'products_created' => 1,
                'skipped' => 0,
            ]);
    });

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('2 variants updated');
});

test('push to Shopify updates existing variants with correct quantities', function () {
    $integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/123',
    ]);

    $this->mock(InventorySyncService::class, function ($mock) use ($colorway) {
        $mock->shouldReceive('pushAllInventoryForColorway')
            ->once()
            ->with(\Mockery::on(fn ($c) => $c->id === $colorway->id), \Mockery::type(Integration::class), 'manual_push')
            ->andReturn([
                'variants_updated' => 1,
                'variants_created' => 0,
                'products_created' => 0,
                'skipped' => 0,
            ]);
    });

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('1 variants updated');
});

test('push to Shopify creates missing variants for new bases', function () {
    $integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/456',
    ]);

    $this->mock(InventorySyncService::class, function ($mock) {
        $mock->shouldReceive('pushAllInventoryForColorway')
            ->once()
            ->andReturn([
                'variants_updated' => 1,
                'variants_created' => 1,
                'products_created' => 0,
                'skipped' => 0,
            ]);
    });

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('2 variants updated');
});

test('push to Shopify creates all account bases including qty zero', function () {
    $integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $base1 = Base::factory()->create(['account_id' => $this->account->id]);
    $base2 = Base::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base1->id,
        'quantity' => 5,
    ]);
    Inventory::factory()->create([
        'account_id' => $this->account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base2->id,
        'quantity' => 0,
    ]);

    $this->mock(InventorySyncService::class, function ($mock) {
        $mock->shouldReceive('pushAllInventoryForColorway')
            ->once()
            ->andReturn([
                'variants_updated' => 1,
                'variants_created' => 1,
                'products_created' => 0,
                'skipped' => 0,
            ]);
    });

    $response = $this->actingAs($this->user)->post(route('inventory.pushToShopify'));

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');
});
