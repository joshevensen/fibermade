<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;

test('store home returns creators with order counts', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Delivered,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->actingAs($user)->get(route('store.home'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/HomePage')
        ->has('creators', 1)
        ->where('creators.0.id', $creator->id)
        ->where('creators.0.draft_count', 1)
        ->where('creators.0.open_count', 0)
        ->where('creators.0.delivered_count', 1)
    );
});

test('store home aggregates order counts per creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creator1Account = Account::factory()->creator()->create();
    $creator1 = Creator::factory()->create(['account_id' => $creator1Account->id]);
    $store->creators()->attach($creator1->id, ['status' => 'active']);

    $creator2Account = Account::factory()->creator()->create();
    $creator2 = Creator::factory()->create(['account_id' => $creator2Account->id]);
    $store->creators()->attach($creator2->id, ['status' => 'active']);

    Order::factory()->count(2)->create([
        'account_id' => $creator1->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    Order::factory()->create([
        'account_id' => $creator2->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->actingAs($user)->get(route('store.home'));

    $response->assertSuccessful();
    $creators = $response->inertiaProps('creators');
    expect($creators)->toHaveCount(2);

    $c1 = collect($creators)->firstWhere('id', $creator1->id);
    $c2 = collect($creators)->firstWhere('id', $creator2->id);
    expect($c1)->toHaveKey('open_count', 2);
    expect($c1)->toHaveKey('draft_count', 0);
    expect($c2)->toHaveKey('draft_count', 1);
    expect($c2)->toHaveKey('open_count', 0);
});
