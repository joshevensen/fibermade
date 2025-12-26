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
            : OrderItem::whereHas('order', function ($query) use ($user) {
                $query->whereIn('account_id', $user->accounts()->pluck('id'));
            })->with(['order.account', 'colorway', 'base'])->get();

        return Inertia::render('order-items/OrderItemIndexPage', [
            'orderItems' => $orderItems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', OrderItem::class);

        return Inertia::render('order-items/OrderItemCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderItemRequest $request): RedirectResponse
    {
        OrderItem::create($request->validated());

        return redirect()->route('order-items.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderItem $orderItem): Response
    {
        $this->authorize('view', $orderItem);

        return Inertia::render('order-items/OrderItemEditPage', [
            'orderItem' => $orderItem->load(['order', 'colorway', 'base']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $orderItem->update($request->validated());

        return redirect()->route('order-items.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem): RedirectResponse
    {
        $this->authorize('delete', $orderItem);

        $orderItem->delete();

        return redirect()->route('order-items.index');
    }
}
