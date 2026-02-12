<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;

test('order list returns 403 when store has no relationship with creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    // Do not attach creator to store

    $response = $this->actingAs($user)->get(route('store.creator.orders', ['creator' => $creator->id]));

    $response->assertForbidden();
});

test('order list returns 200 and data shape when store has relationship with creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway1 = Colorway::factory()->create(['account_id' => $creator->account_id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $creator->account_id]);
    $base = Base::factory()->create(['account_id' => $creator->account_id]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway1->id,
        'base_id' => $base->id,
        'quantity' => 5,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway2->id,
        'base_id' => $base->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('store.creator.orders', ['creator' => $creator->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/OrderListPage')
        ->has('creator')
        ->where('creator.id', $creator->id)
        ->where('creator.name', $creator->name)
        ->has('orders', 1)
        ->has('orders.0')
        ->where('orders.0.id', $order->id)
        ->where('orders.0.status', 'open')
        ->where('orders.0.skein_count', 8)
        ->where('orders.0.colorway_count', 2)
        ->has('orderStatusOptions')
    );
});

test('order list filters by status', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $draftOrder = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    $openOrder = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $responseAll = $this->actingAs($user)->get(route('store.creator.orders', ['creator' => $creator->id]));
    $responseAll->assertSuccessful();
    $responseAll->assertInertia(fn ($page) => $page->has('orders', 2));

    $responseDraft = $this->actingAs($user)->get(route('store.creator.orders', ['creator' => $creator->id, 'status' => 'draft']));
    $responseDraft->assertSuccessful();
    $responseDraft->assertInertia(fn ($page) => $page
        ->has('orders', 1)
        ->where('orders.0.id', $draftOrder->id)
        ->where('orders.0.status', 'draft')
    );

    $responseOpen = $this->actingAs($user)->get(route('store.creator.orders', ['creator' => $creator->id, 'status' => 'open']));
    $responseOpen->assertSuccessful();
    $responseOpen->assertInertia(fn ($page) => $page
        ->has('orders', 1)
        ->where('orders.0.id', $openOrder->id)
    );
});
