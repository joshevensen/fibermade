status: done

# Story 0.6: Prompt 2 -- OrderItem Nested Endpoints

## Context

Prompt 1 created Order CRUD endpoints and re-enabled the `OrderPolicy`. Orders can now be created and managed via the API. OrderItems are the line items within an order -- they reference a colorway, base, quantity, and pricing. In the web app, OrderItems are managed as a standalone resource. For the API, they should be nested under orders (`/api/v1/orders/{order}/items`) since items always belong to a specific order and consumers need to manage them in that context.

## Goal

Create OrderItem endpoints nested under orders: list items for an order, add items, update an item, and delete an item. After creation/update/deletion of items, recalculate the parent order's subtotal and total. After this prompt, the full order management API is complete.

## Non-Goals

- Do not modify the Order controller from Prompt 1
- Do not add bulk item operations (e.g., replacing all items at once)
- Do not modify the OrderItem model or FormRequests
- Do not add inventory deduction when items are created -- that's business logic for later epics

## Constraints

- Endpoints are nested: `GET/POST /api/v1/orders/{order}/items` and `PATCH/DELETE /api/v1/orders/{order}/items/{item}`
- The controller goes in `Api\V1\OrderItemController.php` following the namespace pattern
- Authorization: verify the parent order belongs to the user's account before any item operation. The `OrderItemPolicy` already handles this by loading the order relationship (uses `loadMissing('order')`)
- Auto-calculate `line_total` when not provided: `quantity * unit_price` -- following the web controller pattern in `OrderItemController::store()`
- After store/update/destroy, recalculate the parent order's totals: `subtotal_amount` = sum of all item `line_total` values, `total_amount` = subtotal + shipping - discount + tax. Follow the web controller's `recalculateOrderTotals()` pattern
- Reuse `StoreOrderItemRequest` and `UpdateOrderItemRequest` -- but note that `StoreOrderItemRequest` requires `order_id` in the request body. For the nested API route, the `order_id` should come from the URL parameter, so merge it into the request before validation or handle it in the controller
- Use `OrderItemResource` from Story 0.3
- Eager-load `['colorway', 'base']` on items

## Acceptance Criteria

- [ ] `GET /api/v1/orders/{order}/items` returns items for the specified order with colorway and base loaded
- [ ] `POST /api/v1/orders/{order}/items` creates an item, auto-calculates `line_total` if not provided, and recalculates order totals
- [ ] `PATCH /api/v1/orders/{order}/items/{item}` updates an item and recalculates order totals
- [ ] `DELETE /api/v1/orders/{order}/items/{item}` deletes an item and recalculates order totals
- [ ] Accessing items on another account's order returns 403
- [ ] Item that doesn't belong to the specified order returns 404
- [ ] Order totals are correctly recalculated after each item operation
- [ ] Tests cover CRUD, auto-calculation, order total recalculation, and authorization
- [ ] All existing tests still pass

---

## Tech Analysis

- **Nested routes need manual registration** -- `Route::apiResource()` doesn't handle nested resources well in Laravel. Register them manually: `Route::get('orders/{order}/items', ...)`, `Route::post('orders/{order}/items', ...)`, etc.
- **`StoreOrderItemRequest` requires `order_id`** in its validation rules (required, exists on orders). For the nested route, the order comes from the URL. Options: (a) merge the URL parameter into the request in the controller before validation, (b) create a new API-specific FormRequest, or (c) set the `order_id` in the controller after validation. Option (a) is cleanest -- use `$request->merge(['order_id' => $order->id])` before the FormRequest validates.
- **`OrderItemPolicy` uses `loadMissing('order')`** to check account ownership through the parent order. This is the correct pattern for nested resources -- no direct `account_id` on OrderItem.
- **Order total recalculation** in the web controller: `$order->subtotal_amount = $order->orderItems()->sum('line_total')`, then `$order->total_amount = $order->subtotal_amount + $order->shipping_amount - $order->discount_amount + $order->tax_amount`. This should be extracted into a private method on the API controller (or reuse if the web controller has a shared method).
- **`line_total` auto-calculation**: The web controller checks `if (!isset($validated['line_total']) && isset($validated['quantity']) && isset($validated['unit_price']))` then calculates `quantity * unit_price`. Replicate this logic.
- **OrderItem uses SoftDeletes** -- `DELETE` should soft-delete, and the order total recalculation should only sum non-deleted items (which `orderItems()` does by default with SoftDeletes).

## References

- `platform/app/Http/Controllers/OrderItemController.php` -- web controller: line_total auto-calculation, recalculateOrderTotals() method, store/update patterns
- `platform/app/Http/Requests/StoreOrderItemRequest.php` -- validation rules including order_id (will need URL param merge)
- `platform/app/Http/Requests/UpdateOrderItemRequest.php` -- update validation
- `platform/app/Policies/OrderItemPolicy.php` -- uses loadMissing('order') for account check through parent
- `platform/app/Http/Resources/Api/V1/OrderItemResource.php` -- serialization (created in Story 0.3)
- `platform/app/Http/Controllers/Api/V1/OrderController.php` -- parent controller for reference (created in Prompt 1)

## Files

- Create `platform/app/Http/Controllers/Api/V1/OrderItemController.php` -- nested CRUD with auto-calculation and order total recalculation
- Modify `platform/routes/api.php` -- add nested item routes under orders
- Create `platform/tests/Feature/Api/V1/OrderItemControllerTest.php` -- tests for nested CRUD, auto-calculation, total recalculation, authorization
