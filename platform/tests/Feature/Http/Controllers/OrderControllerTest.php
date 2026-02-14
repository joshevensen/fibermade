<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;

test('user can view orders index', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('orders.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderIndexPage')
        ->has('orders', 1)
        ->where('orders.0.id', $order->id)
    );
});

test('user can view a specific order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => \App\Enums\OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->where('order.id', $order->id)
    );
});

test('user can create an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('orders.store'), [
        'type' => 'wholesale',
        'status' => 'open',
        'order_date' => now()->toDateString(),
        'orderable_id' => $store->id,
    ]);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'account_id' => $account->id,
        'created_by' => $user->id,
        'type' => 'wholesale',
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
});

test('user can update an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => \App\Enums\OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->put(route('orders.update', $order), [
        'status' => \App\Enums\OrderStatus::Delivered->value,
        'order_date' => $order->order_date->toDateString(),
    ]);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => \App\Enums\OrderStatus::Delivered->value,
        'updated_by' => $user->id,
    ]);
});

test('user can delete an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

    $response->assertRedirect(route('orders.index'));
    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});

test('admin can create, update, and delete orders', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create(['is_admin' => true, 'account_id' => $account->id]);
    $store = Store::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => \App\Enums\OrderStatus::Open,
    ]);

    $createResponse = $this->actingAs($admin)->post(route('orders.store'), [
        'type' => 'wholesale',
        'status' => 'open',
        'order_date' => now()->toDateString(),
        'orderable_id' => $store->id,
    ]);
    $createResponse->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'account_id' => $account->id,
        'created_by' => $admin->id,
        'type' => 'wholesale',
    ]);

    $updateResponse = $this->actingAs($admin)->put(route('orders.update', $order), [
        'status' => \App\Enums\OrderStatus::Delivered->value,
        'order_date' => $order->order_date->toDateString(),
    ]);
    $updateResponse->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => \App\Enums\OrderStatus::Delivered->value,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('orders.destroy', $order));
    $deleteResponse->assertRedirect(route('orders.index'));
    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});

test('wholesale order edit loads wholesale terms from creator_store pivot', function () {
    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create([
        'account_id' => $storeAccount->id,
        'name' => 'Test Store',
        'email' => 'store@example.com',
        'owner_name' => 'Store Owner',
        'address_line1' => '123 Main St',
        'city' => 'Portland',
        'state_region' => 'OR',
        'postal_code' => '97201',
        'country_code' => 'US',
    ]);
    $store->creators()->attach($creator->id, [
        'status' => 'active',
        'discount_rate' => 0.20,
        'payment_terms' => 'Net 30',
        'lead_time_days' => 14,
        'minimum_order_quantity' => 10,
        'minimum_order_value' => 100.00,
        'allows_preorders' => true,
    ]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $user = User::factory()->create(['account_id' => $creatorAccount->id]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->where('order.id', $order->id)
        ->has('wholesaleTerms')
        ->where('wholesaleTerms.discount_rate', 0.20)
        ->where('wholesaleTerms.payment_terms', 'Net 30')
        ->where('wholesaleTerms.lead_time_days', 14)
        ->where('wholesaleTerms.minimum_order_quantity', 10)
        ->where('wholesaleTerms.minimum_order_value', 100)
        ->where('wholesaleTerms.allows_preorders', true)
        ->has('order.orderable')
        ->where('order.orderable.name', 'Test Store')
        ->where('order.orderable.email', 'store@example.com')
        ->where('order.orderable.owner_name', 'Store Owner')
        ->where('order.orderable.address_line1', '123 Main St')
    );
});

test('non-wholesale order edit does not load wholesale terms', function () {
    $account = Account::factory()->creator()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create([
        'account_id' => $account->id,
        'type' => OrderType::Retail,
        'status' => OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->where('order.id', $order->id)
        ->where('wholesaleTerms', null)
    );
});

test('store info is present in response for wholesale orders', function () {
    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create([
        'account_id' => $storeAccount->id,
        'name' => 'Yarn Shop',
        'email' => 'yarn@shop.com',
        'owner_name' => 'Jane Doe',
        'address_line1' => '456 Oak Ave',
        'address_line2' => 'Suite 2',
        'city' => 'Seattle',
        'state_region' => 'WA',
        'postal_code' => '98101',
        'country_code' => 'US',
    ]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $user = User::factory()->create(['account_id' => $creatorAccount->id]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('order.orderable')
        ->where('order.orderable.name', 'Yarn Shop')
        ->where('order.orderable.email', 'yarn@shop.com')
        ->where('order.orderable.owner_name', 'Jane Doe')
        ->where('order.orderable.address_line1', '456 Oak Ave')
        ->where('order.orderable.address_line2', 'Suite 2')
        ->where('order.orderable.city', 'Seattle')
        ->where('order.orderable.state_region', 'WA')
        ->where('order.orderable.postal_code', '98101')
        ->where('order.orderable.country_code', 'US')
    );
});

test('wholesale order with account without creator returns wholesaleTerms null', function () {
    $accountWithoutCreator = Account::factory()->creator()->create();
    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id]);

    $order = Order::factory()->create([
        'account_id' => $accountWithoutCreator->id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $user = User::factory()->create(['account_id' => $accountWithoutCreator->id]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->where('order.id', $order->id)
        ->where('wholesaleTerms', null)
    );
});
