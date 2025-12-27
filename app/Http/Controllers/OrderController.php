<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Order::class);

        $user = auth()->user();
        $orders = $user->is_admin
            ? Order::with(['account', 'orderItems'])->get()
            : ($user->account_id ? Order::where('account_id', $user->account_id)->with(['account', 'orderItems'])->get() : collect());

        return Inertia::render('orders/OrderIndexPage', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Order::class);

        $orderTypeOptions = collect(OrderType::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        $orderStatusOptions = collect(OrderStatus::cases())
            ->map(fn ($case) => [
                'label' => Str::title(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $case->name))),
                'value' => $case->value,
            ])
            ->toArray();

        return Inertia::render('orders/OrderCreatePage', [
            'orderTypeOptions' => $orderTypeOptions,
            'orderStatusOptions' => $orderStatusOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = Order::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('orders.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('orders/OrderEditPage', [
            'order' => $order->load(['account', 'orderItems']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());
        $order->updated_by = $request->user()->id;
        $order->save();

        return redirect()->route('orders.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return redirect()->route('orders.index');
    }
}
