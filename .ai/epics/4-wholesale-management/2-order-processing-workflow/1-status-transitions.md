status: pending

# Story 4.2: Prompt 1 -- Status Transitions (Backend)

## Context

Story 4.1 updated the `OrderStatus` enum to include Draft, Open, Accepted, Fulfilled, Delivered, and Cancelled. The `OrderController` handles CRUD operations but has no workflow actions for transitioning order status. The `Order` model has a `status` field cast to `OrderStatus` and an `updated_by` field for tracking who last modified the order. The `notes` field exists on the order for general notes. Routes are defined via `Route::resource('orders', OrderController::class)` in `routes/creator.php`. The `OrderPolicy` allows update operations for admin users and users belonging to the same account. Currently there is no state machine or transition validation on the Order model.

## Goal

Implement the backend for order status transitions: accept (open → accepted), fulfill (accepted → fulfilled), deliver (fulfilled → delivered), and cancel (any active status → cancelled). Add transition validation to the Order model, create controller actions for each transition, register routes, and write tests. Each transition updates the order status, records who performed it via `updated_by`, and optionally accepts a note.

## Non-Goals

- Do not build the frontend UI for transitions -- that's Prompt 2
- Do not add email notifications on status change
- Do not deduct inventory when fulfilling
- Do not add a status history/audit log table (use `updated_by` and `updated_at` on the order itself)
- Do not modify the OrderPolicy beyond what's needed for transitions

## Constraints

- Add transition methods directly on the `Order` model (e.g., `transitionTo(OrderStatus $status)`) rather than a separate service class -- keep it simple
- Define allowed transitions as a static map on the model: each status lists which statuses it can transition to
- Controller actions should be named routes: `orders.accept`, `orders.fulfill`, `orders.deliver`, `orders.cancel`
- Use `PATCH` method for all transition routes (they're partial updates to the order)
- Each transition action should:
  1. Authorize via `OrderPolicy::update()`
  2. Validate the transition is allowed
  3. Update status, `updated_by`, and optionally append to `notes`
  4. Return redirect to `orders.edit` (the order detail page)
- If a transition is invalid, return back with an error (use `abort(422)` or validation exception)
- Cancel should set `cancelled_at` timestamp in addition to changing status
- Follow the existing controller pattern: `$this->authorize()`, validate, update, redirect
- Tests should use Pest syntax matching `OrderControllerTest.php` patterns

## Acceptance Criteria

- [ ] `Order` model has a static `$transitions` map defining allowed transitions:
  - `open` → `[accepted, cancelled]`
  - `accepted` → `[fulfilled, cancelled]`
  - `fulfilled` → `[delivered, cancelled]`
  - `draft`, `delivered`, `cancelled` → `[]` (terminal states)
- [ ] `Order::canTransitionTo(OrderStatus $status): bool` method validates transitions
- [ ] `Order::transitionTo(OrderStatus $status, ?int $userId = null, ?string $note = null): void` method performs the transition
- [ ] `transitionTo()` throws an exception (or returns false) for invalid transitions
- [ ] Four new routes in `routes/creator.php`:
  - `PATCH /creator/orders/{order}/accept` → `orders.accept`
  - `PATCH /creator/orders/{order}/fulfill` → `orders.fulfill`
  - `PATCH /creator/orders/{order}/deliver` → `orders.deliver`
  - `PATCH /creator/orders/{order}/cancel` → `orders.cancel`
- [ ] `OrderController` has `accept()`, `fulfill()`, `deliver()`, `cancel()` actions
- [ ] Each action authorizes, validates transition, updates order, redirects to `orders.edit`
- [ ] Cancel action sets `cancelled_at` to `now()`
- [ ] Notes parameter is optional on all transitions -- when provided, appended to existing notes
- [ ] Invalid transitions return 422 status
- [ ] Tests cover:
  - Valid transitions (open→accepted, accepted→fulfilled, fulfilled→delivered, any→cancelled)
  - Invalid transitions (delivered→accepted, cancelled→open, draft→fulfilled)
  - Authorization (user from different account gets 403)
  - `updated_by` is set correctly
  - `cancelled_at` is set on cancel
  - Notes appended when provided
- [ ] `php artisan test --filter=Order` passes

---

## Tech Analysis

- **Transition map design**: A static array on the model is the simplest approach. The map keys are OrderStatus values, values are arrays of allowed target statuses. This avoids external packages and keeps the logic close to the model.
- **Note handling**: The `notes` field on Order is a text field. When a transition includes a note, it should be appended with a separator (e.g., newline + timestamp + note text) rather than replacing existing notes. Format: `\n\n---\n[Status Change: accepted → fulfilled] {date}\n{note}`. This keeps a running log in the notes field without needing a separate status history table.
- **Route registration**: The existing `Route::resource('orders', ...)` generates standard CRUD routes. The workflow routes need to be registered separately as named PATCH routes. Add them after the resource registration.
- **Controller method pattern**: Each transition method follows the same structure. Consider a private `performTransition()` helper on the controller to reduce duplication:
  ```php
  private function performTransition(Order $order, OrderStatus $targetStatus, Request $request): RedirectResponse
  ```
  Each public method (accept, fulfill, deliver, cancel) calls this with the appropriate target status.
- **Cancel specifics**: Cancel additionally needs to set `cancelled_at`. The `transitionTo()` method on the model can handle this: if the target status is Cancelled, also set `cancelled_at`.
- **Request validation**: The transition actions accept an optional `note` field. Use a simple `$request->validate(['note' => 'nullable|string|max:1000'])` inline rather than creating a FormRequest class for this.

## References

- `platform/app/Models/Order.php` -- add transition map, `canTransitionTo()`, `transitionTo()` methods
- `platform/app/Enums/OrderStatus.php` -- the six status cases
- `platform/app/Http/Controllers/OrderController.php` -- add accept/fulfill/deliver/cancel actions
- `platform/app/Policies/OrderPolicy.php` -- existing `update()` method handles authorization
- `platform/routes/creator.php` -- add PATCH routes for transitions
- `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- existing test patterns

## Files

- Modify `platform/app/Models/Order.php` -- add `$transitions` map, `canTransitionTo()`, `transitionTo()` methods
- Modify `platform/app/Http/Controllers/OrderController.php` -- add `accept()`, `fulfill()`, `deliver()`, `cancel()` actions with shared `performTransition()` helper
- Modify `platform/routes/creator.php` -- add four PATCH routes after the orders resource
- Create `platform/tests/Feature/Http/Controllers/OrderTransitionTest.php` -- tests for all transitions, authorization, invalid transitions, notes
