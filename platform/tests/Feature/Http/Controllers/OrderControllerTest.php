<?php

use App\Models\Account;
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
