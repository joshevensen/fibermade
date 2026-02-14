<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
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

test('unauthenticated request redirects to login', function () {
    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->get(route('store.orders.show', ['order' => $order->id]));

    $response->assertRedirect();
});

test('store can view own order', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
        'descriptor' => 'Merino Worsted',
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
        'subtotal_amount' => 60,
        'total_amount' => 60,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
        'unit_price' => 20,
        'line_total' => 60,
    ]);

    $response = $this->actingAs($user)->get(route('store.orders.show', ['order' => $order->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/OrderDetailPage')
        ->has('id')
        ->has('order_date')
        ->has('status')
        ->where('status', 'open')
        ->has('creator')
        ->where('creator.id', $creator->id)
        ->where('creator.name', $creator->name)
        ->has('skein_count')
        ->where('skein_count', 3)
        ->has('colorway_count')
        ->where('colorway_count', 1)
        ->has('items_by_colorway')
        ->where('items_by_colorway.0.colorway.id', $colorway->id)
        ->where('items_by_colorway.0.colorway.name', $colorway->name)
        ->where('items_by_colorway.0.bases.0.quantity', 3)
        ->where('items_by_colorway.0.bases.0.unit_price', 20)
        ->where('items_by_colorway.0.bases.0.line_total', 60)
    );
});

test('store cannot view another store order', function () {
    $storeAAccount = Account::factory()->storeType()->create();
    $storeA = Store::factory()->create(['account_id' => $storeAAccount->id]);
    $storeBAccount = Account::factory()->storeType()->create();
    $storeB = Store::factory()->create(['account_id' => $storeBAccount->id]);
    $userB = User::factory()->create(['account_id' => $storeBAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $storeA->creators()->attach($creator->id, ['status' => 'active']);
    $storeB->creators()->attach($creator->id, ['status' => 'active']);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $storeA->id,
    ]);

    $response = $this->actingAs($userB)->get(route('store.orders.show', ['order' => $order->id]));

    $response->assertForbidden();
});

test('returns 404 for non-existent order', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $response = $this->actingAs($user)->get(route('store.orders.show', ['order' => 99999]));

    $response->assertNotFound();
});

test('items are grouped by colorway', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway1 = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
        'name' => 'Ocean Blue',
    ]);
    $colorway2 = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
        'name' => 'Forest Green',
    ]);
    $base1 = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    $base2 = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);

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
        'base_id' => $base1->id,
        'quantity' => 2,
        'unit_price' => 25,
        'line_total' => 50,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway1->id,
        'base_id' => $base2->id,
        'quantity' => 1,
        'unit_price' => 30,
        'line_total' => 30,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway2->id,
        'base_id' => $base1->id,
        'quantity' => 4,
        'unit_price' => 25,
        'line_total' => 100,
    ]);

    $response = $this->actingAs($user)->get(route('store.orders.show', ['order' => $order->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/OrderDetailPage')
        ->has('items_by_colorway')
        ->where('items_by_colorway.0.colorway.id', $colorway1->id)
        ->where('items_by_colorway.0.colorway.name', 'Ocean Blue')
        ->has('items_by_colorway.0.bases', 2)
        ->where('items_by_colorway.1.colorway.id', $colorway2->id)
        ->where('items_by_colorway.1.colorway.name', 'Forest Green')
        ->has('items_by_colorway.1.bases', 1)
        ->where('skein_count', 7)
        ->where('colorway_count', 2)
    );
});
