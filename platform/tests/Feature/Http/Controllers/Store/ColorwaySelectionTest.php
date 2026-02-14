<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Models\Account;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\User;

test('colorway selection returns 403 when store has no relationship with creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);

    $response = $this->actingAs($user)->get(route('store.creator.order.step1', ['creator' => $creator->id]));

    $response->assertForbidden();
});

test('colorway selection returns 200 and correct data shape when store has relationship with creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $collection = Collection::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $colorway->collections()->attach($collection->id);

    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);

    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ]);

    $response = $this->actingAs($user)->get(route('store.creator.order.step1', ['creator' => $creator->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/ColorwaySelectionPage')
        ->has('creator')
        ->where('creator.id', $creator->id)
        ->where('creator.name', $creator->name)
        ->has('colorways', 1)
        ->has('colorways.0')
        ->where('colorways.0.id', $colorway->id)
        ->where('colorways.0.name', $colorway->name)
        ->has('colorways.0.colors')
        ->has('colorways.0.collections', 1)
        ->where('colorways.0.collections.0.id', $collection->id)
        ->where('colorways.0.collections.0.name', $collection->name)
        ->has('colorways.0.bases', 1)
        ->where('colorways.0.bases.0.id', $base->id)
        ->where('colorways.0.bases.0.inventory_quantity', 10)
        ->has('collections', 1)
        ->where('collections.0.id', $collection->id)
        ->has('discount_rate')
        ->where('discount_rate', 0.20)
    );
});
