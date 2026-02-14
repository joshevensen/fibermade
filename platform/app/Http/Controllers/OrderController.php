<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Show;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $query = $user->is_admin
            ? Order::with(['account', 'orderItems', 'orderable', 'externalIdentifiers.integration'])
            : ($user->account_id ? Order::where('account_id', $user->account_id)->with(['account', 'orderItems', 'orderable', 'externalIdentifiers.integration']) : null);
        $orders = $query ? $query->orderByDesc('order_date')->get() : collect();

        $orders = $orders->map(function ($order) {
            $orderArray = $order->toArray();
            $orderArray['external_identifiers'] = $order->externalIdentifiers->map(fn ($identifier) => [
                'integration_type' => $identifier->integration->type->value,
                'external_type' => $identifier->external_type,
                'external_id' => $identifier->external_id,
                'data' => $identifier->data,
            ])->toArray();

            return $orderArray;
        });

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

        return Inertia::render('creator/orders/OrderIndexPage', [
            'orders' => $orders,
            'orderTypeOptions' => $orderTypeOptions,
            'orderStatusOptions' => $orderStatusOptions,
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

        return Inertia::render('creator/orders/OrderCreatePage', [
            'orderTypeOptions' => $orderTypeOptions,
            'orderStatusOptions' => $orderStatusOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * TODO: Re-enable when ready to work on orders.
     * Currently disabled via OrderPolicy.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $type = OrderType::from($validated['type']);

        // Set orderable_type based on order type
        if ($type === OrderType::Wholesale) {
            $validated['orderable_type'] = Store::class;
        } elseif ($type === OrderType::Retail) {
            $validated['orderable_type'] = Customer::class;
        } elseif ($type === OrderType::Show) {
            $validated['orderable_type'] = Show::class;
        }

        $order = Order::create([
            ...$validated,
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

        $order->load(['account', 'orderItems.colorway', 'orderItems.base', 'orderable', 'externalIdentifiers.integration']);
        $orderArray = $order->toArray();
        $orderArray['external_identifiers'] = $order->externalIdentifiers->map(fn ($identifier) => [
            'integration_type' => $identifier->integration->type->value,
            'external_type' => $identifier->external_type,
            'external_id' => $identifier->external_id,
            'data' => $identifier->data,
        ])->toArray();

        // Add colorways and bases for order item dropdowns
        $colorways = \App\Models\Colorway::select('id', 'name')->get();
        $bases = \App\Models\Base::select('id', 'code', 'descriptor')->get();

        return Inertia::render('creator/orders/OrderEditPage', [
            'order' => $orderArray,
            'orderTypeOptions' => $orderTypeOptions,
            'orderStatusOptions' => $orderStatusOptions,
            'colorways' => $colorways,
            'bases' => $bases,
            'allowedTransitions' => $order->getAllowedTransitions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * TODO: Re-enable when ready to work on orders.
     * Currently disabled via OrderPolicy.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        // Update orderable_type if type is being changed
        if (isset($validated['type'])) {
            $type = OrderType::from($validated['type']);

            if ($type === OrderType::Wholesale) {
                $validated['orderable_type'] = Store::class;
            } elseif ($type === OrderType::Retail) {
                $validated['orderable_type'] = Customer::class;
            } elseif ($type === OrderType::Show) {
                $validated['orderable_type'] = Show::class;
            }
        }

        // Calculate subtotal from order items
        $order->load('orderItems');
        $subtotal = $order->orderItems->sum('line_total');
        $validated['subtotal_amount'] = $subtotal;

        // Recalculate total = subtotal + shipping - discount + tax
        $shipping = $validated['shipping_amount'] ?? $order->shipping_amount ?? 0;
        $discount = $validated['discount_amount'] ?? $order->discount_amount ?? 0;
        $tax = $validated['tax_amount'] ?? $order->tax_amount ?? 0;
        $validated['total_amount'] = $subtotal + $shipping - $discount + $tax;

        $order->update($validated);
        $order->updated_by = $request->user()->id;
        $order->save();

        return redirect()->route('orders.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * TODO: Re-enable when ready to work on orders.
     * Currently disabled via OrderPolicy.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return redirect()->route('orders.index');
    }

    public function submit(Request $request, Order $order): RedirectResponse
    {
        return $this->performTransition($order, OrderStatus::Open, $request);
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        return $this->performTransition($order, OrderStatus::Accepted, $request);
    }

    public function fulfill(Request $request, Order $order): RedirectResponse
    {
        return $this->performTransition($order, OrderStatus::Fulfilled, $request);
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        return $this->performTransition($order, OrderStatus::Delivered, $request);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        return $this->performTransition($order, OrderStatus::Cancelled, $request);
    }

    private function performTransition(Order $order, OrderStatus $targetStatus, Request $request): RedirectResponse
    {
        $this->authorize('update', $order);

        $request->validate(['note' => 'nullable|string|max:1000']);

        try {
            $order->transitionTo($targetStatus, $request->user()->id, $request->input('note'));
        } catch (InvalidOrderTransitionException $e) {
            abort(422, $e->getMessage());
        }

        return redirect()->route('orders.edit', $order);
    }
}
