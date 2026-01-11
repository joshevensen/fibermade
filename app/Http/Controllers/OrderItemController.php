<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', OrderItem::class);

        $user = auth()->user();
        $orderItems = $user->is_admin
            ? OrderItem::with(['order.account', 'colorway', 'base'])->get()
            : ($user->account_id ? OrderItem::whereHas('order', function ($query) use ($user) {
                $query->where('account_id', $user->account_id);
            })->with(['order.account', 'colorway', 'base'])->get() : collect());

        return Inertia::render('creator/order-items/OrderItemIndexPage', [
            'orderItems' => $orderItems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', OrderItem::class);

        return Inertia::render('creator/order-items/OrderItemCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Auto-calculate line_total if not provided
        if (! isset($validated['line_total']) && isset($validated['quantity']) && isset($validated['unit_price'])) {
            $validated['line_total'] = $validated['quantity'] * $validated['unit_price'];
        }

        $orderItem = OrderItem::create($validated);

        // Recalculate order totals
        $this->recalculateOrderTotals($orderItem->order_id);

        return redirect()->route('orders.edit', $orderItem->order_id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderItem $orderItem): Response
    {
        $this->authorize('view', $orderItem);

        return Inertia::render('creator/order-items/OrderItemEditPage', [
            'orderItem' => $orderItem->load(['order', 'colorway', 'base']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $validated = $request->validated();

        // Auto-calculate line_total if quantity or unit_price changed
        if (isset($validated['quantity']) || isset($validated['unit_price'])) {
            $quantity = $validated['quantity'] ?? $orderItem->quantity;
            $unitPrice = $validated['unit_price'] ?? $orderItem->unit_price;
            if ($quantity && $unitPrice) {
                $validated['line_total'] = $quantity * $unitPrice;
            }
        }

        $orderItem->update($validated);

        // Recalculate order totals
        $this->recalculateOrderTotals($orderItem->order_id);

        return redirect()->route('orders.edit', $orderItem->order_id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem): RedirectResponse
    {
        $this->authorize('delete', $orderItem);

        $orderId = $orderItem->order_id;
        $orderItem->delete();

        // Recalculate order totals
        $this->recalculateOrderTotals($orderId);

        return redirect()->route('orders.edit', $orderId);
    }

    /**
     * Recalculate order totals based on order items.
     */
    private function recalculateOrderTotals(int $orderId): void
    {
        $order = \App\Models\Order::findOrFail($orderId);
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
