<?php

use App\Enums\OrderStatus;
use App\Models\Account;
use App\Models\Order;
use App\Models\User;

test('user can submit order from draft to open', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Draft,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.submit', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Open->value,
        'updated_by' => $user->id,
    ]);
});

test('user can accept order from open to accepted', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.accept', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Accepted->value,
        'updated_by' => $user->id,
    ]);
});

test('user can fulfill order from accepted to fulfilled', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Accepted,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.fulfill', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Fulfilled->value,
        'updated_by' => $user->id,
    ]);
});

test('user can deliver order from fulfilled to delivered', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Fulfilled,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.deliver', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Delivered->value,
        'updated_by' => $user->id,
    ]);
});

test('user can cancel order from open', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'cancelled_at' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.cancel', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
        'updated_by' => $user->id,
    ]);
    $order->refresh();
    expect($order->cancelled_at)->not->toBeNull();
});

test('user can cancel order from accepted', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Accepted,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.cancel', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
    ]);
});

test('user can cancel order from fulfilled', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Fulfilled,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.cancel', $order));

    $response->assertRedirect(route('orders.edit', $order));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
    ]);
});

test('invalid transition delivered to accepted returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Delivered,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.accept', $order));

    $response->assertStatus(422);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Delivered->value,
    ]);
});

test('invalid transition cancelled to open returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Cancelled,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.submit', $order));

    $response->assertStatus(422);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
    ]);
});

test('invalid transition draft to fulfilled returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Draft,
    ]);

    $response = $this->actingAs($user)->patch(route('orders.fulfill', $order));

    $response->assertStatus(422);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Draft->value,
    ]);
});

test('user from different account gets 403 on transition', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $order = Order::factory()->create([
        'account_id' => $accountA->id,
        'status' => OrderStatus::Open,
    ]);

    $response = $this->actingAs($userB)->patch(route('orders.accept', $order));

    $response->assertForbidden();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Open->value,
    ]);
});

test('updated_by is set correctly after transition', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'updated_by' => null,
    ]);

    $this->actingAs($user)->patch(route('orders.accept', $order));

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'updated_by' => $user->id,
    ]);
});

test('cancelled_at is set when cancelling', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'cancelled_at' => null,
    ]);

    $this->actingAs($user)->patch(route('orders.cancel', $order));

    $order->refresh();
    expect($order->cancelled_at)->not->toBeNull();
});

test('note is appended to order when provided', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'notes' => null,
    ]);
    $note = 'Accepted by warehouse team.';

    $this->actingAs($user)->patch(route('orders.accept', $order), [
        'note' => $note,
    ]);

    $order->refresh();
    expect($order->notes)->toContain('[Status Change: open → accepted]');
    expect($order->notes)->toContain($note);
});
