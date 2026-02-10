<?php

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/orders/{$order->id}/items")
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->getJson("/api/v1/orders/{$order->id}/items/{$item->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $this->postJson("/api/v1/orders/{$order->id}/items", [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 2,
        'unit_price' => 25.00,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->patchJson("/api/v1/orders/{$order->id}/items/{$item->id}", ['quantity' => 5])
        ->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->deleteJson("/api/v1/orders/{$order->id}/items/{$item->id}")
        ->assertStatus(401);
});

test('index returns items for order with colorway and base loaded', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    OrderItem::factory()->count(2)->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/orders/{$order->id}/items", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()->and(count($data))->toBe(2);
    expect($data[0])->toHaveKeys(['id', 'order_id', 'colorway_id', 'base_id', 'quantity', 'colorway', 'base']);
});

test('index for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/orders/{$orderA->id}/items", withBearer($token));

    $response->assertForbidden();
});

test('show returns item with colorway and base', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/orders/{$order->id}/items/{$item->id}", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveKeys(['id', 'order_id', 'colorway_id', 'base_id', 'quantity', 'colorway', 'base']);
});

test('show for item that does not belong to order returns 404', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order1 = Order::factory()->create(['account_id' => $account->id]);
    $order2 = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $itemOnOrder2 = OrderItem::factory()->create([
        'order_id' => $order2->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/orders/{$order1->id}/items/{$itemOnOrder2->id}", withBearer($token));

    $response->assertNotFound();
});

test('show for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $itemA = OrderItem::factory()->create([
        'order_id' => $orderA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
    ]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/orders/{$orderA->id}/items/{$itemA->id}", withBearer($token));

    $response->assertForbidden();
});

test('store creates item auto-calculates line_total and recalculates order totals', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'subtotal_amount' => 0,
        'shipping_amount' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson("/api/v1/orders/{$order->id}/items", [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
        'unit_price' => 10.50,
    ], withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.quantity', 3);
    $response->assertJsonPath('data.line_total', '31.50');
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'quantity' => 3,
        'line_total' => 31.50,
    ]);
    $order->refresh();
    expect((float) $order->subtotal_amount)->toBe(31.50);
    expect((float) $order->total_amount)->toBe(31.50);
});

test('store with explicit line_total does not overwrite', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson("/api/v1/orders/{$order->id}/items", [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 2,
        'unit_price' => 20.00,
        'line_total' => 50.00,
    ], withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.line_total', '50.00');
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'line_total' => 50.00,
    ]);
});

test('store for another account order returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $colorwayB = Colorway::factory()->create(['account_id' => $accountB->id]);
    $baseB = Base::factory()->create(['account_id' => $accountB->id]);
    $token = getApiToken($userB);

    $response = $this->postJson("/api/v1/orders/{$orderA->id}/items", [
        'colorway_id' => $colorwayB->id,
        'base_id' => $baseB->id,
        'quantity' => 1,
        'unit_price' => 10,
    ], withBearer($token));

    $response->assertForbidden();
    $this->assertDatabaseMissing('order_items', ['order_id' => $orderA->id]);
});

test('store with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson("/api/v1/orders/{$order->id}/items", [
        'colorway_id' => '',
        'base_id' => '',
        'quantity' => 0,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies item and recalculates order totals', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'subtotal_amount' => 100,
        'total_amount' => 100,
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 2,
        'unit_price' => 50,
        'line_total' => 100,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/orders/{$order->id}/items/{$item->id}", [
        'quantity' => 4,
        'unit_price' => 25,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.quantity', 4);
    $response->assertJsonPath('data.line_total', '100.00');
    $order->refresh();
    expect((float) $order->subtotal_amount)->toBe(100.0);
});

test('update for item that does not belong to order returns 404', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order1 = Order::factory()->create(['account_id' => $account->id]);
    $order2 = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $itemOnOrder2 = OrderItem::factory()->create([
        'order_id' => $order2->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/orders/{$order1->id}/items/{$itemOnOrder2->id}", [
        'quantity' => 99,
    ], withBearer($token));

    $response->assertNotFound();
});

test('update for another account item returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $itemA = OrderItem::factory()->create([
        'order_id' => $orderA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/orders/{$orderA->id}/items/{$itemA->id}", [
        'quantity' => 10,
    ], withBearer($token));

    $response->assertForbidden();
});

test('destroy deletes item and recalculates order totals', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'subtotal_amount' => 75,
        'shipping_amount' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 75,
    ]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
        'unit_price' => 25,
        'line_total' => 75,
    ]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/orders/{$order->id}/items/{$item->id}", [], withBearer($token));

    $response->assertStatus(204);
    $this->assertSoftDeleted('order_items', ['id' => $item->id]);
    $order->refresh();
    expect((float) $order->subtotal_amount)->toBe(0.0);
    expect((float) $order->total_amount)->toBe(0.0);
});

test('destroy for item that does not belong to order returns 404', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order1 = Order::factory()->create(['account_id' => $account->id]);
    $order2 = Order::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $itemOnOrder2 = OrderItem::factory()->create([
        'order_id' => $order2->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/orders/{$order1->id}/items/{$itemOnOrder2->id}", [], withBearer($token));

    $response->assertNotFound();
    expect(OrderItem::find($itemOnOrder2->id))->not->toBeNull();
});

test('destroy for another account item returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $orderA = Order::factory()->create(['account_id' => $accountA->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $itemA = OrderItem::factory()->create([
        'order_id' => $orderA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
    ]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/orders/{$orderA->id}/items/{$itemA->id}", [], withBearer($token));

    $response->assertForbidden();
    expect(OrderItem::find($itemA->id))->not->toBeNull();
});
