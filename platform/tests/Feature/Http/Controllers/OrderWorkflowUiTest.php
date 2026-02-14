<?php

use App\Enums\OrderStatus;
use App\Models\Account;
use App\Models\Order;
use App\Models\User;

test('edit returns allowedTransitions for draft order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Draft,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', ['open'])
    );
});

test('edit returns allowedTransitions for open order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', ['accepted', 'cancelled'])
    );
});

test('edit returns allowedTransitions for accepted order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Accepted,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', ['fulfilled', 'cancelled'])
    );
});

test('edit returns allowedTransitions for fulfilled order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Fulfilled,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', ['delivered', 'cancelled'])
    );
});

test('edit returns allowedTransitions for delivered order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Delivered,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', [])
    );
});

test('edit returns allowedTransitions for cancelled order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Cancelled,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->has('allowedTransitions')
        ->where('allowedTransitions', [])
    );
});

test('after accept transition edit returns updated allowedTransitions', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
    ]);

    $patchResponse = $this->actingAs($user)->patch(route('orders.accept', $order), [
        'note' => 'Accepted for fulfillment',
    ]);
    $patchResponse->assertRedirect(route('orders.edit', $order));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Accepted);

    $getResponse = $this->actingAs($user)->get(route('orders.edit', $order));
    $getResponse->assertSuccessful();
    $getResponse->assertInertia(fn ($page) => $page
        ->component('creator/orders/OrderEditPage')
        ->where('allowedTransitions', ['fulfilled', 'cancelled'])
    );
});
