<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrderItemController extends ApiController
{
    public function index(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $items = $order->orderItems()->with(['colorway', 'base'])->get();

        return $this->successResponse(OrderItemResource::collection($items));
    }

    public function show(Order $order, OrderItem $orderItem): JsonResponse
    {
        if ($orderItem->order_id !== $order->id) {
            return $this->notFoundResponse('Resource not found.');
        }

        $this->authorize('view', $orderItem);

        $orderItem->load(['colorway', 'base']);

        return $this->successResponse(new OrderItemResource($orderItem));
    }

    public function store(StoreOrderItemRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validated();

        if (! isset($validated['line_total']) && isset($validated['quantity']) && isset($validated['unit_price'])) {
            $validated['line_total'] = $validated['quantity'] * $validated['unit_price'];
        }

        $orderItem = OrderItem::create($validated);
        $this->recalculateOrderTotals($order->id);

        $orderItem->load(['colorway', 'base']);

        return $this->createdResponse(new OrderItemResource($orderItem));
    }

    public function update(UpdateOrderItemRequest $request, Order $order, OrderItem $orderItem): JsonResponse
    {
        if ($orderItem->order_id !== $order->id) {
            return $this->notFoundResponse('Resource not found.');
        }

        $this->authorize('update', $orderItem);

        $validated = $request->validated();

        if (isset($validated['quantity']) || isset($validated['unit_price'])) {
            $quantity = $validated['quantity'] ?? $orderItem->quantity;
            $unitPrice = $validated['unit_price'] ?? $orderItem->unit_price;
            if ($quantity && $unitPrice) {
                $validated['line_total'] = $quantity * $unitPrice;
            }
        }

        $orderItem->update($validated);
        $this->recalculateOrderTotals($order->id);

        $orderItem->load(['colorway', 'base']);

        return $this->successResponse(new OrderItemResource($orderItem));
    }

    public function destroy(Order $order, OrderItem $orderItem): JsonResponse
    {
        if ($orderItem->order_id !== $order->id) {
            return $this->notFoundResponse('Resource not found.');
        }

        $this->authorize('delete', $orderItem);

        $orderItem->delete();
        $this->recalculateOrderTotals($order->id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function recalculateOrderTotals(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->load('orderItems');

        $subtotal = $order->orderItems->sum('line_total');
        $shipping = $order->shipping_amount ?? 0;
        $discount = $order->discount_amount ?? 0;
        $tax = $order->tax_amount ?? 0;
        $total = $subtotal + $shipping - $discount + $tax;

        $order->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => $total,
        ]);
    }
}
