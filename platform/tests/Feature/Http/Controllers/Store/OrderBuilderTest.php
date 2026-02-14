<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;

test('review returns 403 when store has no relationship with creator', function () {
    $storeAccount = Account::factory()->storeType()->create();
    Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'colorways' => '1',
    ]));

    $response->assertForbidden();
});

test('review redirects to step 1 when no colorways and no draft', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', ['creator' => $creator->id]));

    $response->assertRedirect(route('store.creator.order.step1', ['creator' => $creator->id]));
    $response->assertSessionHas('error', 'Select colorways or resume a draft');
});

test('review redirects when colorways empty and draft invalid', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'colorways' => '',
        'draft' => 99999,
    ]));

    $response->assertRedirect(route('store.creator.order.step1', ['creator' => $creator->id]));
});

test('review returns 403 when draft belongs to another store', function () {
    $storeAAccount = Account::factory()->storeType()->create();
    $storeA = Store::factory()->create(['account_id' => $storeAAccount->id]);
    $storeBAccount = Account::factory()->storeType()->create();
    $storeB = Store::factory()->create(['account_id' => $storeBAccount->id]);
    $userB = User::factory()->create(['account_id' => $storeBAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $storeA->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);
    $storeB->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $storeA->id,
    ]);

    $response = $this->actingAs($userB)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'draft' => $order->id,
    ]));

    $response->assertForbidden();
});

test('review returns 403 when draft is not draft status', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'draft' => $order->id,
    ]));

    $response->assertForbidden();
});

test('review returns 200 with correct data for new order', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'discount_rate' => 0.20,
        'minimum_order_quantity' => 10,
        'minimum_order_value' => 100,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
        'retail_price' => 25.00,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 50,
    ]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'colorways' => (string) $colorway->id,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/BaseQuantitySelectionPage')
        ->has('creator')
        ->where('creator.id', $creator->id)
        ->has('colorways', 1)
        ->where('colorways.0.id', $colorway->id)
        ->where('colorways.0.name', $colorway->name)
        ->has('colorways.0.bases', 1)
        ->where('colorways.0.bases.0.wholesale_price', 20)
        ->where('colorways.0.bases.0.inventory_quantity', 50)
        ->has('wholesale_terms')
        ->where('wholesale_terms.discount_rate', 0.20)
        ->where('wholesale_terms.minimum_order_quantity', 10)
        ->where('wholesale_terms.minimum_order_value', 100)
        ->where('draft', null)
    );
});

test('review returns 200 with draft data when resuming', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 20,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
        'unit_price' => 20,
        'line_total' => 100,
    ]);

    $response = $this->actingAs($user)->get(route('store.creator.order.review', [
        'creator' => $creator->id,
        'draft' => $order->id,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('store/orders/BaseQuantitySelectionPage')
        ->has('draft')
        ->where('draft.order_id', $order->id)
        ->has('draft.items', 1)
        ->where('draft.items.0.colorway_id', $colorway->id)
        ->where('draft.items.0.base_id', $base->id)
        ->where('draft.items.0.quantity', 5)
    );
});

test('save creates Order and OrderItems', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
        'retail_price' => 25.00,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 100,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.save', ['creator' => $creator->id]), [
        'items' => [
            ['colorway_id' => $colorway->id, 'base_id' => $base->id, 'quantity' => 3],
        ],
        'notes' => 'Test notes',
    ]);

    $response->assertRedirect(route('store.creator.orders', ['creator' => $creator->id]));
    $response->assertSessionHas('success', 'Order saved as draft');
    $response->assertSessionHas('order_id');

    expect(Order::count())->toBe(1);
    $order = Order::first();
    expect($order->status)->toBe(OrderStatus::Draft);
    expect($order->account_id)->toBe($creator->account_id);
    expect($order->notes)->toBe('Test notes');

    expect(OrderItem::count())->toBe(1);
    $item = OrderItem::first();
    expect($item->quantity)->toBe(3);
    expect((float) $item->unit_price)->toBe(20.0);
    expect((float) $item->line_total)->toBe(60.0);
});

test('save updates existing draft', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $colorway1 = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $colorway2 = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
        'retail_price' => 25.00,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway1->id,
        'base_id' => $base->id,
        'quantity' => 50,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway2->id,
        'base_id' => $base->id,
        'quantity' => 50,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway1->id,
        'base_id' => $base->id,
        'quantity' => 2,
        'unit_price' => 20,
        'line_total' => 40,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.save', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [
            ['colorway_id' => $colorway2->id, 'base_id' => $base->id, 'quantity' => 5],
        ],
    ]);

    $response->assertRedirect(route('store.creator.orders', ['creator' => $creator->id]));

    $order->refresh();
    expect($order->orderItems()->count())->toBe(1);
    $item = $order->orderItems()->first();
    expect($item->colorway_id)->toBe($colorway2->id);
    expect($item->quantity)->toBe(5);
});

test('save ignores zero-quantity items', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active', 'discount_rate' => 0.20]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
        'retail_price' => 25.00,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 50,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.save', ['creator' => $creator->id]), [
        'items' => [
            ['colorway_id' => $colorway->id, 'base_id' => $base->id, 'quantity' => 0],
            ['colorway_id' => $colorway->id, 'base_id' => $base->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect(route('store.creator.orders', ['creator' => $creator->id]));

    $order = Order::first();
    expect($order->orderItems()->count())->toBe(1);
    expect($order->orderItems()->first()->quantity)->toBe(2);
});

test('save returns 403 when order_id is another store draft', function () {
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
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $storeA->id,
    ]);

    $response = $this->actingAs($userB)->post(route('store.creator.order.save', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [
            ['colorway_id' => 1, 'base_id' => 1, 'quantity' => 0],
        ],
    ]);

    $response->assertForbidden();
});

test('save returns 403 when order_id is not draft', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.save', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [
            ['colorway_id' => 1, 'base_id' => 1, 'quantity' => 0],
        ],
    ]);

    $response->assertForbidden();
});

test('submit validates minimum_order_quantity', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'discount_rate' => 0.20,
        'minimum_order_quantity' => 10,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
        'unit_price' => 20,
        'line_total' => 100,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.submit', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('minimum_order_quantity');
});

test('submit validates minimum_order_value', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'discount_rate' => 0.20,
        'minimum_order_value' => 100,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 2,
        'unit_price' => 20,
        'line_total' => 40,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.submit', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('minimum_order_value');
});

test('submit accepts when minimums met', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'discount_rate' => 0.20,
        'minimum_order_quantity' => 5,
        'minimum_order_value' => 80,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
        'unit_price' => 20,
        'line_total' => 200,
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.submit', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [],
    ]);

    $response->assertRedirect(route('store.orders.show', ['order' => $order->id]));
    $response->assertSessionHas('success', 'Order submitted successfully');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Open);
});

test('submit fails when order already submitted', function () {
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'minimum_order_quantity' => null,
        'minimum_order_value' => null,
    ]);

    $colorway = Colorway::factory()->create([
        'account_id' => $creator->account_id,
        'status' => ColorwayStatus::Active,
    ]);
    $base = Base::factory()->create([
        'account_id' => $creator->account_id,
        'status' => BaseStatus::Active,
    ]);
    Inventory::factory()->create([
        'account_id' => $creator->account_id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Draft,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
        'unit_price' => 20,
        'line_total' => 100,
    ]);

    $this->actingAs($user)->post(route('store.creator.order.submit', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [],
    ]);

    $response = $this->actingAs($user)->post(route('store.creator.order.submit', ['creator' => $creator->id]), [
        'order_id' => $order->id,
        'items' => [],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('order');
});
