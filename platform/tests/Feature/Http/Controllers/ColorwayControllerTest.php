<?php

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\User;
use App\Services\InventorySyncService;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\mock;

test('user can create a colorway without slug', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.store'), [
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
        'technique' => Technique::Solid->value,
        'per_pan' => 3,
    ]);

    $response->assertRedirect(route('colorways.index'));
    $this->assertDatabaseHas('colorways', [
        'account_id' => $account->id,
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
    ]);
    $this->assertDatabaseMissing('colorways', [
        'account_id' => $account->id,
        'name' => 'Test Colorway',
        'slug' => 'test-colorway',
    ]);
});

test('user can update a colorway without slug', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $colorway = Colorway::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Colorway',
    ]);

    $response = $this->actingAs($user)->put(route('colorways.update', $colorway), [
        'name' => 'Updated Colorway',
        'status' => ColorwayStatus::Active->value,
    ]);

    $response->assertRedirect(route('colorways.edit', $colorway));
    $this->assertDatabaseHas('colorways', [
        'id' => $colorway->id,
        'name' => 'Updated Colorway',
    ]);
});

test('store rejects invalid colors', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.store'), [
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
        'technique' => Technique::Solid->value,
        'per_pan' => 3,
        'colors' => ['invalid', 'red'],
    ]);

    $response->assertSessionHasErrors('colors.0');
});

test('store accepts valid Color enum values in colors', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.store'), [
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
        'technique' => Technique::Solid->value,
        'per_pan' => 3,
        'colors' => [Color::Red->value, Color::Blue->value],
    ]);

    $response->assertRedirect(route('colorways.index'));
    $colorway = Colorway::where('account_id', $account->id)->where('name', 'Test Colorway')->first();
    expect($colorway)->not->toBeNull();
    expect($colorway->colors->map(fn ($c) => $c->value)->all())->toBe(['red', 'blue']);
});

test('pushToShopify creates product via InventorySyncService when no existing product mapping', function () {
    $account = Account::factory()->creator()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $mockService = mock(InventorySyncService::class);
    $mockService->shouldReceive('pushAllInventoryForColorway')->once()->andReturn([
        'products_created' => 1, 'variants_created' => 1, 'variants_updated' => 0, 'skipped' => 0,
    ]);

    $response = $this->actingAs($user)->postJson(route('colorways.push-to-shopify', $colorway));

    $response->assertOk();
    $response->assertJson(['message' => 'Pushed to Shopify successfully.']);
});

test('pushToShopify updates product via Shopify API when product mapping exists', function () {
    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productUpdate' => [
                    'product' => ['id' => 'gid://shopify/Product/1'],
                    'userErrors' => [],
                ],
            ],
        ], 200),
    ]);

    $account = Account::factory()->creator()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    $response = $this->actingAs($user)->postJson(route('colorways.push-to-shopify', $colorway));

    $response->assertOk();
    $response->assertJson(['message' => 'Pushed to Shopify successfully.']);
});
