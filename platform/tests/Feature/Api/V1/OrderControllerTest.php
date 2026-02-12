<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Show;
use App\Models\Store;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/orders')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/orders/{$order->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $store = Store::factory()->create(['account_id' => $account->id]);
    $this->postJson('/api/v1/orders', [
        'type' => OrderType::Wholesale->value,
        'status' => OrderStatus::Draft->value,
        'order_date' => now()->toDateString(),
        'orderable_id' => $store->id,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/orders/{$order->id}", [
        'status' => OrderStatus::Open->value,
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/orders/{$order->id}")
        ->assertStatus(401);
});

test('index returns paginated orders scoped to account with orderItems and orderable', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Order::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/orders', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'type', 'status', 'order_date', 'order_items', 'orderable']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return orders from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Order::factory()->count(2)->create(['account_id' => $accountA->id]);
    Order::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/orders', withBearer($token));

    $response->assertStatus(200);
    $items = $response->json('data.data') ?? $response->json('data');
    expect($items)->toBeArray()
        ->and(count($items))->toBe(2);
});

test('show returns order with orderItems and orderable loaded', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/orders/{$order->id}", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveKeys(['id', 'type', 'status', 'order_date', 'order_items', 'orderable']);
});

test('show for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/orders/{$orderA->id}", withBearer($token));

    $response->assertForbidden();
});

test('show for non-existent order returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/orders/999999', withBearer($token));

    $response->assertNotFound();
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates order with account_id created_by and resolved orderable_type for wholesale', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'type' => OrderType::Wholesale->value,
        'status' => OrderStatus::Draft->value,
        'order_date' => now()->toDateString(),
        'orderable_id' => $store->id,
    ];

    $response = $this->postJson('/api/v1/orders', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.type', OrderType::Wholesale->value);
    $response->assertJsonPath('data.orderable_type', Store::class);
    $this->assertDatabaseHas('orders', [
        'account_id' => $account->id,
        'created_by' => $user->id,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
});

test('store creates order with resolved orderable_type for retail', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'type' => OrderType::Retail->value,
        'status' => OrderStatus::Draft->value,
        'order_date' => now()->toDateString(),
        'orderable_id' => $customer->id,
    ];

    $response = $this->postJson('/api/v1/orders', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.orderable_type', Customer::class);
    $this->assertDatabaseHas('orders', [
        'account_id' => $account->id,
        'orderable_type' => Customer::class,
        'orderable_id' => $customer->id,
    ]);
});

test('store creates order with resolved orderable_type for show', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $show = Show::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'type' => OrderType::Show->value,
        'status' => OrderStatus::Draft->value,
        'order_date' => now()->toDateString(),
        'orderable_id' => $show->id,
    ];

    $response = $this->postJson('/api/v1/orders', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.orderable_type', Show::class);
    $this->assertDatabaseHas('orders', [
        'account_id' => $account->id,
        'orderable_type' => Show::class,
        'orderable_id' => $show->id,
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'invalid',
        'status' => OrderStatus::Draft->value,
        'order_date' => 'not-a-date',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies order and sets updated_by', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Draft,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/orders/{$order->id}", [
        'status' => OrderStatus::Open->value,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.status', OrderStatus::Open->value);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Open->value,
        'updated_by' => $user->id,
    ]);
});

test('update for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create([
        'account_id' => $accountA->id,
        'status' => OrderStatus::Draft,
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/orders/{$orderA->id}", [
        'status' => OrderStatus::Delivered->value,
    ], withBearer($token));

    $response->assertForbidden();
    $this->assertDatabaseHas('orders', [
        'id' => $orderA->id,
        'status' => OrderStatus::Draft->value,
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/orders/{$order->id}", [
        'status' => 'invalid-status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy soft-deletes order', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/orders/{$order->id}", [], withBearer($token));

    $response->assertStatus(204);
    expect(Order::find($order->id))->toBeNull();
    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});

test('destroy for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/orders/{$orderA->id}", [], withBearer($token));

    $response->assertForbidden();
    expect(Order::find($orderA->id))->not->toBeNull();
});
