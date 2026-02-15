<?php

use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Mail\WholesaleNewOrderNotificationMail;
use App\Mail\WholesaleOrderConfirmationMail;
use App\Mail\WholesaleOrderStatusUpdateMail;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('order confirmation and new order notification sent when store submits draft', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id, 'email' => 'creator@example.com']);
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

    Mail::assertQueued(WholesaleOrderConfirmationMail::class, function (WholesaleOrderConfirmationMail $mail) {
        return $mail->hasTo('store@example.com')
            && $mail->order->id !== null;
    });
    Mail::assertQueued(WholesaleNewOrderNotificationMail::class, function (WholesaleNewOrderNotificationMail $mail) {
        return $mail->hasTo('creator@example.com')
            && $mail->order->id !== null;
    });
});

test('status update sent when creator accepts wholesale order', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $creatorUser = User::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway = Colorway::factory()->create(['account_id' => $creator->account_id, 'status' => ColorwayStatus::Active]);
    $base = Base::factory()->create(['account_id' => $creator->account_id, 'status' => BaseStatus::Active]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
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

    $this->actingAs($creatorUser)->patch(route('orders.accept', $order));

    Mail::assertQueued(WholesaleOrderStatusUpdateMail::class, function (WholesaleOrderStatusUpdateMail $mail) {
        return $mail->hasTo('store@example.com')
            && $mail->order->status === OrderStatus::Accepted;
    });
});

test('status update sent when creator fulfills wholesale order', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $creatorUser = User::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway = Colorway::factory()->create(['account_id' => $creator->account_id, 'status' => ColorwayStatus::Active]);
    $base = Base::factory()->create(['account_id' => $creator->account_id, 'status' => BaseStatus::Active]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Accepted,
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

    $this->actingAs($creatorUser)->patch(route('orders.fulfill', $order));

    Mail::assertQueued(WholesaleOrderStatusUpdateMail::class, function (WholesaleOrderStatusUpdateMail $mail) {
        return $mail->hasTo('store@example.com')
            && $mail->order->status === OrderStatus::Fulfilled;
    });
});

test('status update sent when creator delivers wholesale order', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $creatorUser = User::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway = Colorway::factory()->create(['account_id' => $creator->account_id, 'status' => ColorwayStatus::Active]);
    $base = Base::factory()->create(['account_id' => $creator->account_id, 'status' => BaseStatus::Active]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Fulfilled,
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

    $this->actingAs($creatorUser)->patch(route('orders.deliver', $order));

    Mail::assertQueued(WholesaleOrderStatusUpdateMail::class, function (WholesaleOrderStatusUpdateMail $mail) {
        return $mail->hasTo('store@example.com')
            && $mail->order->status === OrderStatus::Delivered;
    });
});

test('status update sent when creator cancels wholesale order', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id]);
    $creatorUser = User::factory()->create(['account_id' => $creatorAccount->id]);
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $colorway = Colorway::factory()->create(['account_id' => $creator->account_id, 'status' => ColorwayStatus::Active]);
    $base = Base::factory()->create(['account_id' => $creator->account_id, 'status' => BaseStatus::Active]);

    $order = Order::factory()->create([
        'account_id' => $creator->account_id,
        'type' => OrderType::Wholesale,
        'status' => OrderStatus::Open,
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

    $this->actingAs($creatorUser)->patch(route('orders.cancel', $order));

    Mail::assertQueued(WholesaleOrderStatusUpdateMail::class, function (WholesaleOrderStatusUpdateMail $mail) {
        return $mail->hasTo('store@example.com')
            && $mail->order->status === OrderStatus::Cancelled;
    });
});

test('status update not sent for non-wholesale order', function () {
    Mail::fake();

    $creatorAccount = Account::factory()->creator()->create();
    $creatorUser = User::factory()->create(['account_id' => $creatorAccount->id]);
    $customer = Customer::factory()->create(['account_id' => $creatorAccount->id]);

    $colorway = Colorway::factory()->create(['account_id' => $creatorAccount->id, 'status' => ColorwayStatus::Active]);
    $base = Base::factory()->create(['account_id' => $creatorAccount->id, 'status' => BaseStatus::Active]);

    $order = Order::factory()->create([
        'account_id' => $creatorAccount->id,
        'type' => OrderType::Retail,
        'status' => OrderStatus::Open,
        'orderable_type' => Customer::class,
        'orderable_id' => $customer->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 5,
        'unit_price' => 20,
        'line_total' => 100,
    ]);

    $this->actingAs($creatorUser)->patch(route('orders.accept', $order));

    Mail::assertNotQueued(WholesaleOrderStatusUpdateMail::class);
});

test('confirmation not sent when store email missing', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => '']);
    $user = User::factory()->create(['account_id' => $storeAccount->id, 'role' => UserRole::Employee]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id, 'email' => 'creator@example.com']);
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

    Mail::assertNotQueued(WholesaleOrderConfirmationMail::class);
});

test('new order notification not sent when creator email missing', function () {
    Mail::fake();

    $storeAccount = Account::factory()->storeType()->create();
    $store = Store::factory()->create(['account_id' => $storeAccount->id, 'email' => 'store@example.com']);
    $user = User::factory()->create(['account_id' => $storeAccount->id]);

    $creatorAccount = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $creatorAccount->id, 'email' => null]);

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

    Mail::assertNotQueued(WholesaleNewOrderNotificationMail::class);
});
