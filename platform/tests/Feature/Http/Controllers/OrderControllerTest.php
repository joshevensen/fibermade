<?php

use App\Models\Account;
use App\Models\Order;
use App\Models\User;

// TODO: Update tests when ready to work on orders and re-enable write operations

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

test('user cannot create an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('orders.store'), [
        'type' => 'wholesale',
        'status' => 'open',
        'order_date' => now()->toDateString(),
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('orders', [
        'account_id' => $account->id,
        'type' => 'wholesale',
    ]);
});

test('user cannot update an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => \App\Enums\OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->put(route('orders.update', $order), [
        'status' => \App\Enums\OrderStatus::Closed->value,
        'order_date' => $order->order_date->toDateString(),
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => \App\Enums\OrderStatus::Open->value,
    ]);
});

test('user cannot delete an order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $order = Order::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

    $response->assertForbidden();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
    ]);
});

test('admin cannot create, update, or delete orders when ready to work on orders', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $account = Account::factory()->create();
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => \App\Enums\OrderStatus::Open,
    ]);

    // Test create
    $createResponse = $this->actingAs($admin)->post(route('orders.store'), [
        'type' => 'wholesale',
        'status' => 'open',
        'order_date' => now()->toDateString(),
    ]);
    $createResponse->assertForbidden();

    // Test update
    $updateResponse = $this->actingAs($admin)->put(route('orders.update', $order), [
        'status' => \App\Enums\OrderStatus::Closed->value,
        'order_date' => $order->order_date->toDateString(),
    ]);
    $updateResponse->assertForbidden();

    // Test delete
    $deleteResponse = $this->actingAs($admin)->delete(route('orders.destroy', $order));
    $deleteResponse->assertForbidden();

    // Verify nothing changed
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => \App\Enums\OrderStatus::Open->value,
    ]);
});
