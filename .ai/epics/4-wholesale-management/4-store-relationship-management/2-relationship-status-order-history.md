status: pending

# Story 4.4: Prompt 2 -- Relationship Status & Order History

## Context

Prompt 1 wired up the wholesale terms management: `StoreController::edit()` loads pivot data from `creator_store`, the form saves to the pivot table, and the index page shows key terms. The `StoreEditPage.vue` has a status select button (active/paused/ended) in the wholesale settings form and an orders sidebar card that lists past orders. The `StoreController::edit()` loads `$store->orders` and passes them mapped to the page. However, the orders listed are all orders associated with the store (via morphMany), not filtered to the current creator's account. The status field on the pivot controls the relationship state but there's no confirmation or guard when changing from active to ended (which should be a significant action). The `StoreIndexPage.vue` has status-based filtering via server-side query parameter (`?status=active`).

## Goal

Enhance the store relationship management with: (1) proper order history scoped to the current creator's orders with the store, (2) confirmation guards for significant status changes (pausing and ending relationships), and (3) improved order history display with status badges and key details. Ensure the order list on the store edit page shows only wholesale orders belonging to the authenticated creator's account.

## Non-Goals

- Do not add a dedicated order history page for stores (the sidebar list is sufficient for Stage 1)
- Do not add relationship re-activation from "ended" to "active" (ended is terminal for now)
- Do not add analytics or reporting on store relationships
- Do not modify the invite flow
- Do not add order count or revenue metrics to the index page

## Constraints

- The order history on `StoreEditPage.vue` must filter orders to: `type: wholesale`, `account_id: creator's account`, `orderable_id: store.id`, `orderable_type: Store`
- Orders in the sidebar should show: order date, status (with color-coded badge), skein count, total amount
- Sort orders by `order_date` descending (most recent first)
- Status change guards:
  - Pausing: show a confirmation dialog ("Pausing will prevent the store from placing new orders. Continue?")
  - Ending: show a confirmation dialog ("Ending this relationship is permanent. The store will lose access to your catalog. Continue?")
  - Activating (from paused): no confirmation needed
- The status select button in the wholesale settings form should be replaced with explicit action buttons for status changes (clearer UX than a select widget for state transitions)
- Follow the existing `useConfirm` composable pattern for confirmations
- Status change should be a separate action from the terms update form -- don't mix term editing with status changes in the same submit

## Acceptance Criteria

- [ ] `StoreController::edit()` filters orders to the creator's account:
  - Only wholesale orders (`type: wholesale`)
  - Only orders for the creator's account (`account_id: $creator->account_id`)
  - Ordered by `order_date` descending
  - Each order includes: id, order_date, status, total_amount, skein_count (sum of order item quantities)
- [ ] `StoreEditPage.vue` order history sidebar:
  - Shows order date, status badge (color-coded), skein count, and total amount
  - Each order is clickable (links to the order edit page)
  - Empty state: "No orders yet"
  - Orders sorted most recent first
- [ ] Status management in `StoreEditPage.vue`:
  - Current status displayed as a badge
  - Action buttons based on current status:
    - Active: "Pause Relationship" button
    - Paused: "Reactivate" and "End Relationship" buttons
    - Ended: no action buttons (read-only badge showing "Ended")
  - Pause confirmation dialog via `useConfirm`
  - End confirmation dialog via `useConfirm`
- [ ] Separate route for status changes: `PATCH /creator/stores/{store}/status` with `status` field
  - Validates status is one of: active, paused, ended
  - Guards: cannot go from ended to any other status
  - Updates the `creator_store` pivot status
  - Returns redirect to `stores.edit`
- [ ] Remove the status select button from the wholesale settings form (replaced by action buttons)
- [ ] Tests:
  - Order history is scoped to the creator's account
  - Status change route validates transitions (ended is terminal)
  - Status change updates pivot correctly
  - Non-creator users can't change status (403)
- [ ] `php artisan test --filter=StoreControllerTest` passes

---

## Tech Analysis

- **Scoped order history**: Currently `StoreController::edit()` loads `$store->orders` which returns ALL orders for that store (from any creator). For a creator viewing the page, we need `Order::where('type', OrderType::Wholesale)->where('account_id', $creator->account_id)->where('orderable_type', Store::class)->where('orderable_id', $store->id)->orderByDesc('order_date')->get()`. Load `orderItems` to calculate skein count.
- **Skein count**: Use `$order->orderItems->sum('quantity')` for each order. This is already loaded -- just calculate and include in the mapped array.
- **Status action route**: Add a dedicated `PATCH /creator/stores/{store}/status` route that maps to `StoreController::updateStatus()`. This keeps status changes separate from the terms update form. The method:
  1. Gets the creator from `auth()->user()->account->creator`
  2. Validates the requested status
  3. Checks the current pivot status allows the transition (ended → anything is blocked)
  4. Updates the pivot: `$creator->stores()->updateExistingPivot($store->id, ['status' => $status])`
  5. Redirects back
- **Status transitions**: Simple rules:
  - `active` → `paused`, `ended`
  - `paused` → `active`, `ended`
  - `ended` → nothing (terminal)
  These are simple enough that a conditional check in the controller suffices -- no need for a state machine.
- **Replacing status select button**: The current form has a `UiSelectButton` for status with options (active, paused, ended). Replace this with: a status badge showing the current state, plus contextual action buttons below it. This makes the state changes more intentional and allows for confirmation dialogs.
- **Order item click navigation**: Each order in the sidebar should link to `/creator/orders/{orderId}` (the order edit/detail page). Use the existing `editOrder.url(order.id)` action URL helper.

## References

- `platform/app/Http/Controllers/StoreController.php` -- `edit()` order loading, add `updateStatus()` action
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- order sidebar, status management
- `platform/routes/creator.php` -- add status change route
- `platform/app/Models/Order.php` -- scoping query
- `platform/app/Enums/OrderType.php` -- `Wholesale` case
- `platform/app/Enums/OrderStatus.php` -- status badge coloring reference
- `platform/resources/js/composables/useConfirm.ts` -- confirmation composable
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- `getStatusBadgeClass()` pattern for order status badges
- `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- existing tests to extend

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- scope order history in `edit()`, add `updateStatus()` action
- Modify `platform/routes/creator.php` -- add `PATCH stores/{store}/status` route
- Modify `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- enhanced order history sidebar, replace status select with action buttons + confirmations
- Modify `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- tests for scoped order history, status transitions, terminal state guard
