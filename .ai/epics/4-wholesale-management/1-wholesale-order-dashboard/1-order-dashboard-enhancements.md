status: done

# Story 4.1: Prompt 1 -- Order Dashboard Enhancements

## Context

Epic 3 built the store-side wholesale ordering flow: stores browse catalogs, build orders, and submit them. Orders are created with `type: wholesale`, `status: open`, and linked to a Store via the polymorphic `orderable` relationship. The creator-side `OrderIndexPage.vue` exists with type and status filter dropdowns, a data table showing Name, Type, Status, Order Date, # Skeins, and Total columns. The `OrderController::index()` loads all orders for the account with eager-loaded relationships and passes `orderTypeOptions` and `orderStatusOptions` derived from the enums. The current `OrderStatus` enum has only Draft, Open, Closed, and Cancelled -- it needs to be expanded to support the wholesale lifecycle: open → accepted → fulfilled → delivered. The index page lacks color-coded status badges, does not sort by most recent, and the "Name" column pulls from `orderable.name` without distinguishing between order types.

## Goal

Update the `OrderStatus` enum to include the full wholesale lifecycle statuses (accepted, fulfilled, delivered) replacing the generic "closed" status. Enhance the `OrderIndexPage.vue` with color-coded status badges, default sort by most recent order date, and a store name column that shows the orderable name for wholesale orders. Ensure the index controller passes the updated status options and orders are sorted by `order_date` descending.

## Non-Goals

- Do not build workflow actions (accept, fulfill, deliver) -- that's Story 4.2
- Do not build the enhanced order detail page -- that's Story 4.3
- Do not add email notifications
- Do not modify OrderPolicy permissions
- Do not add pagination at the database level (the existing client-side paginator on UiDataTable is sufficient)

## Constraints

- The `OrderStatus` enum at `platform/app/Enums/OrderStatus.php` must have exactly: Draft, Open, Accepted, Fulfilled, Delivered, Cancelled
- Add a migration to update any existing orders with `status: closed` to `status: delivered` (the closest semantic match)
- The `OrderController::index()` already generates `orderStatusOptions` from enum cases -- updating the enum automatically updates the filter dropdown
- Sort orders by `order_date` descending in the controller query, not client-side
- Status badge colors: extract to a shared utility (used by OrderIndexPage and OrderEditPage) with the six statuses and colors below
- Use the existing `UiDataTable` component's slot mechanism for custom cell rendering (see `#name` slot pattern)
- Follow the existing `formatEnum()` pattern for displaying status labels

## Decisions

- **Outstanding orders:** In `StoreController::transformCreatorsForHome()`, "outstanding" = Draft, Open, Accepted, and Fulfilled (all in-progress; Delivered and Cancelled are not outstanding).
- **Status badge logic:** Extract status → badge-class mapping to a shared utility; use it from both `OrderIndexPage.vue` and `OrderEditPage.vue` (no duplication).
- **Status filter order:** Status filter dropdown shows options in enum order (Draft, Open, Accepted, Fulfilled, Delivered, Cancelled); no custom ordering.

## Acceptance Criteria

- [ ] `OrderStatus` enum has exactly six cases: Draft, Open, Accepted, Fulfilled, Delivered, Cancelled
- [ ] Migration exists to convert `closed` → `delivered` for existing orders
- [ ] `OrderController::index()` sorts orders by `order_date` descending
- [ ] `OrderIndexPage.vue` shows color-coded status badges with these colors:
  - Draft: gray
  - Open: blue
  - Accepted: indigo/purple
  - Fulfilled: amber/yellow
  - Delivered: green
  - Cancelled: red
- [ ] `OrderIndexPage.vue` displays the store name in the Name column for wholesale orders (already works via `orderable.name`, just verify)
- [ ] Status filter dropdown includes all six status options in enum order
- [ ] Default sort order is most recent first
- [ ] Existing tests pass after enum changes (update any test fixtures that reference `closed` status)
- [ ] `php artisan test --filter=OrderControllerTest` passes

---

## Tech Analysis

- **Enum change scope**: The `OrderStatus` enum is referenced in: `Order` model (cast), `OrderController` (filter options), `StoreController::transformCreatorsForHome()` (outstanding status check), `OrderEditPage.vue` (badge classes). All these need to be checked when the enum changes. The `StoreController` checks for `Draft` and `Open` as "outstanding" statuses -- this should be expanded to include `Accepted` and `Fulfilled` as well since those are still active orders.
- **Migration**: Since `status` is stored as a string in the database (backed by the enum), a simple `UPDATE orders SET status = 'delivered' WHERE status = 'closed'` migration handles the transition. No column type change needed.
- **Sort order**: Currently `OrderController::index()` calls `->get()` without any ordering. Add `->orderByDesc('order_date')` before `->get()`.
- **Badge rendering**: The current `OrderIndexPage.vue` uses a `bodyTemplate` function for the status column that calls `formatEnum()` returning plain text. This needs to change to use a named slot (like the `#name` slot) so we can render a styled badge span instead of plain text.
- **Store name display**: The Name column already uses `orderable.name` which resolves to the Store name for wholesale orders, Show name for show orders, and Customer name for retail orders. This works as-is but should be verified.
- **Status badge utility**: Extract the status → badge-class mapping to a shared utility (e.g. `orderStatusBadge.js` or a composable). Both `OrderIndexPage.vue` and `OrderEditPage.vue` will use it; remove the inline `getStatusBadgeClass()` from OrderEditPage and have both pages call the shared util.

## References

- `platform/app/Enums/OrderStatus.php` -- current enum with Draft, Open, Closed, Cancelled
- `platform/app/Enums/OrderType.php` -- pattern reference for enum structure
- `platform/app/Http/Controllers/OrderController.php` -- index action, enum option generation
- `platform/app/Http/Controllers/StoreController.php` -- `transformCreatorsForHome()` references `OrderStatus::Closed` in outstanding status check
- `platform/resources/js/pages/creator/orders/OrderIndexPage.vue` -- current index page with data table, filters
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- replace `getStatusBadgeClass()` with shared util
- (New) shared order status badge utility -- to be created, used by OrderIndexPage and OrderEditPage
- `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- existing tests to update
- `platform/app/Models/Order.php` -- status cast to OrderStatus enum

## Files

- Modify `platform/app/Enums/OrderStatus.php` -- replace Closed with Accepted, Fulfilled, Delivered
- Create `platform/database/migrations/xxxx_update_order_status_closed_to_delivered.php` -- migrate closed → delivered
- Modify `platform/app/Http/Controllers/OrderController.php` -- add `orderByDesc('order_date')` to index query
- Modify `platform/app/Http/Controllers/StoreController.php` -- update outstanding statuses in `transformCreatorsForHome()` to include Accepted and Fulfilled
- Create shared status-badge utility (e.g. `platform/resources/js/utils/orderStatusBadge.js` or composable) with status → Tailwind badge classes for all six statuses
- Modify `platform/resources/js/pages/creator/orders/OrderIndexPage.vue` -- add status badge slot using shared util, verify sort order
- Modify `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- replace inline `getStatusBadgeClass()` with shared util
- Modify `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- update any fixtures referencing `closed` status
