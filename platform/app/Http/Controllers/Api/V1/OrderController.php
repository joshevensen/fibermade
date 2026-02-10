<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Show;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class OrderController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['orderItems', 'orderable']);
        $orders = $this->scopeToAccount($query)->paginate();

        return $this->successResponse(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['orderItems.colorway', 'orderItems.base', 'orderable']);

        return $this->successResponse(new OrderResource($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = OrderType::from($validated['type']);

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

        $order->load(['orderItems', 'orderable']);

        return $this->createdResponse(new OrderResource($order));
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $validated = $request->validated();

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

        $order->update(array_merge($validated, [
            'updated_by' => $request->user()->id,
        ]));

        $order->load(['orderItems.colorway', 'orderItems.base', 'orderable']);

        return $this->successResponse(new OrderResource($order));
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return response()->json(null, 204);
    }
}
