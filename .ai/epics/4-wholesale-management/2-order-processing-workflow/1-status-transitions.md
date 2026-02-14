status: done

# Story 4.2: Prompt 1 -- Status Transitions (Backend)

## Context

Story 4.1 updated the `OrderStatus` enum to include Draft, Open, Accepted, Fulfilled, Delivered, and Cancelled. The `OrderController` handles CRUD operations but has no workflow actions for transitioning order status. The `Order` model has a `status` field cast to `OrderStatus` and an `updated_by` field for tracking who last modified the order. The `notes` field exists on the order for general notes. Routes are defined via `Route::resource('orders', OrderController::class)` in `routes/creator.php`. The `OrderPolicy` allows update operations for admin users and users belonging to the same account. Currently there is no state machine or transition validation on the Order model.

## Goal

Implement the backend for order status transitions: submit (draft → open), accept (open → accepted), fulfill (accepted → fulfilled), deliver (fulfilled → delivered), and cancel (any active status → cancelled). Add transition validation to the Order model, create controller actions for each transition, register routes, and write tests. Each transition updates the order status, records who performed it via `updated_by`, and optionally accepts a note.

## Non-Goals

- Do not build the frontend UI for transitions -- that's Prompt 2
- Do not add email notifications on status change
- Do not deduct inventory when fulfilling
- Do not add a status history/audit log table (use `updated_by` and `updated_at` on the order itself)
- Do not modify the OrderPolicy beyond what's needed for transitions

## Constraints

- Add transition methods directly on the `Order` model (e.g., `transitionTo(OrderStatus $status)`) rather than a separate service class -- keep it simple
- Define allowed transitions as a static map on the model: each status lists which statuses it can transition to
- Controller actions should be named routes: `orders.submit`, `orders.accept`, `orders.fulfill`, `orders.deliver`, `orders.cancel`
- Use `PATCH` method for all transition routes (they're partial updates to the order)
- Each transition action should:
  1. Authorize via `OrderPolicy::update()`
  2. Validate the transition is allowed
  3. Update status, `updated_by`, and optionally append to `notes`
  4. Return redirect to `orders.edit` (the order detail page)
- If a transition is invalid, the model throws an exception; the controller catches it and returns 422
- Cancel should set `cancelled_at` timestamp in addition to changing status
- Follow the existing controller pattern: `$this->authorize()`, validate, update, redirect
- Tests should use Pest syntax matching `OrderControllerTest.php` patterns

## Acceptance Criteria

- [ ] `Order` model has a static `$transitions` map defining allowed transitions:
  - `draft` → `[open]`
  - `open` → `[accepted, cancelled]`
  - `accepted` → `[fulfilled, cancelled]`
  - `fulfilled` → `[delivered, cancelled]`
  - `delivered`, `cancelled` → `[]` (terminal states)
- [ ] `Order::canTransitionTo(OrderStatus $status): bool` method validates transitions
- [ ] `Order::transitionTo(OrderStatus $status, ?int $userId = null, ?string $note = null): void` method performs the transition
- [ ] `transitionTo()` throws an exception for invalid transitions; controller catches it and returns 422
- [ ] When a note is provided, append to `notes` using this format: `\n\n---\n[Status Change: {from} → {to}] {date}\n{note}` (date in a consistent format, e.g. ISO or app default)
- [ ] Five new routes in `routes/creator.php`:
  - `PATCH /creator/orders/{order}/submit` → `orders.submit`
  - `PATCH /creator/orders/{order}/accept` → `orders.accept`
  - `PATCH /creator/orders/{order}/fulfill` → `orders.fulfill`
  - `PATCH /creator/orders/{order}/deliver` → `orders.deliver`
  - `PATCH /creator/orders/{order}/cancel` → `orders.cancel`
- [ ] `OrderController` has `submit()`, `accept()`, `fulfill()`, `deliver()`, `cancel()` actions, each delegating to a private `performTransition(Order $order, OrderStatus $targetStatus, Request $request): RedirectResponse` helper
- [ ] Each action authorizes, validates transition (via helper), updates order, redirects to `orders.edit`
- [ ] Cancel action sets `cancelled_at` to `now()`
- [ ] Notes parameter is optional on all transitions; validate with inline `$request->validate(['note' => 'nullable|string|max:1000'])`
- [ ] Invalid transitions return 422 status
- [ ] Tests cover:
  - Valid transitions (draft→open, open→accepted, accepted→fulfilled, fulfilled→delivered, any→cancelled)
  - Invalid transitions (delivered→accepted, cancelled→open, draft→fulfilled)
  - Authorization (user from different account gets 403)
  - `updated_by` is set correctly
  - `cancelled_at` is set on cancel
  - Notes appended when provided
- [ ] `php artisan test --filter=Order` passes

---

## Tech Analysis

- **Transition map design**: A static array on the model is the simplest approach. The map keys are OrderStatus values, values are arrays of allowed target statuses. This avoids external packages and keeps the logic close to the model.
- **Note handling**: The `notes` field on Order is a text field. When a transition includes a note, append using the required format: `\n\n---\n[Status Change: {from} → {to}] {date}\n{note}`. This keeps a running log without a separate status history table.
- **Route registration**: The existing `Route::resource('orders', ...)` generates standard CRUD routes. The workflow routes need to be registered separately as named PATCH routes. Add them after the resource registration.
- **Controller method pattern**: The controller must use a private `performTransition(Order $order, OrderStatus $targetStatus, Request $request): RedirectResponse` helper. Each public method (submit, accept, fulfill, deliver, cancel) calls this with the appropriate target status. The helper authorizes, validates the optional `note` inline, calls `$order->transitionTo()`, and redirects to `orders.edit` (or back with 422 on invalid transition).
- **Cancel specifics**: Cancel additionally needs to set `cancelled_at`. The `transitionTo()` method on the model can handle this: if the target status is Cancelled, also set `cancelled_at`.
- **Request validation**: Use inline `$request->validate(['note' => 'nullable|string|max:1000'])` in the helper; no FormRequest.

## References

- `platform/app/Models/Order.php` -- add transition map, `canTransitionTo()`, `transitionTo()` methods
- `platform/app/Enums/OrderStatus.php` -- the six status cases
- `platform/app/Http/Controllers/OrderController.php` -- add submit/accept/fulfill/deliver/cancel actions and performTransition() helper
- `platform/app/Policies/OrderPolicy.php` -- existing `update()` method handles authorization
- `platform/routes/creator.php` -- add PATCH routes for transitions
- `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- existing test patterns

## Files

- Modify `platform/app/Models/Order.php` -- add `$transitions` map, `canTransitionTo()`, `transitionTo()` methods
- Modify `platform/app/Http/Controllers/OrderController.php` -- add `submit()`, `accept()`, `fulfill()`, `deliver()`, `cancel()` actions with shared `performTransition()` helper
- Modify `platform/routes/creator.php` -- add five PATCH routes (submit, accept, fulfill, deliver, cancel) after the orders resource
- Create `platform/tests/Feature/Http/Controllers/OrderTransitionTest.php` -- tests for all transitions, authorization, invalid transitions, notes
