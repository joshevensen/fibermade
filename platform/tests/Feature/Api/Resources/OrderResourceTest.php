<?php

use App\Http\Resources\Api\V1\OrderItemResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Order;
use App\Models\OrderItem;

test('OrderResource serializes core fields without relationships', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'subtotal_amount' => 100.00,
        'shipping_amount' => 10.00,
        'discount_amount' => 5.00,
        'tax_amount' => 8.50,
        'total_amount' => 113.50,
    ]);
    $order->unsetRelations();

    $resource = OrderResource::make($order);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'type', 'status', 'order_date', 'subtotal_amount', 'shipping_amount',
        'discount_amount', 'tax_amount', 'total_amount', 'refunded_amount',
        'payment_method', 'source', 'notes', 'orderable_type', 'orderable_id',
        'taxes', 'cancelled_at', 'created_at', 'updated_at',
    ])
        ->and($data)->not->toHaveKey('order_items')
        ->and($data)->not->toHaveKey('orderable');
});

test('OrderResource serializes decimal amounts as strings', function () {
    $account = Account::factory()->create();
    $order = Order::factory()->create([
        'account_id' => $account->id,
        'subtotal_amount' => 99.50,
        'shipping_amount' => 12.00,
        'discount_amount' => 5.25,
        'tax_amount' => 7.10,
        'total_amount' => 113.35,
        'refunded_amount' => 0,
    ]);
    $order->unsetRelations();

    $resource = OrderResource::make($order);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data['subtotal_amount'])->toBe('99.50')
        ->and($data['shipping_amount'])->toBe('12.00')
        ->and($data['discount_amount'])->toBe('5.25')
        ->and($data['tax_amount'])->toBe('7.10')
        ->and($data['total_amount'])->toBe('113.35')
        ->and($data['refunded_amount'])->toBe('0.00');
});

test('OrderResource includes orderItems when loaded', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'unit_price' => 25.00,
        'line_total' => 50.00,
    ]);

    $order->load('orderItems');

    $resource = OrderResource::make($order);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKey('order_items')
        ->and($data['order_items']['data'] ?? $data['order_items'])->toBeArray()
        ->and(count($data['order_items']['data'] ?? $data['order_items']))->toBeGreaterThan(0);
});

test('OrderItemResource serializes core fields without relationships', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 3,
        'unit_price' => 20.50,
        'line_total' => 61.50,
    ]);
    $orderItem->unsetRelations();

    $resource = OrderItemResource::make($orderItem);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'order_id', 'colorway_id', 'base_id', 'quantity', 'unit_price',
        'line_total', 'created_at', 'updated_at',
    ])
        ->and($data['unit_price'])->toBe('20.50')
        ->and($data['line_total'])->toBe('61.50')
        ->and($data)->not->toHaveKey('colorway')
        ->and($data)->not->toHaveKey('base');
});

test('OrderItemResource includes colorway and base when loaded', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create();
    $order = Order::factory()->create(['account_id' => $account->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $orderItem->load(['colorway', 'base']);

    $resource = OrderItemResource::make($orderItem);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys(['colorway', 'base'])
        ->and($data['colorway'])->toBeArray()
        ->and($data['base'])->toBeArray();
});
