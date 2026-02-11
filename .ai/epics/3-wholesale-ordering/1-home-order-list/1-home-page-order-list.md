status: pending

# Story 3.1: Prompt 1 -- Store Home Page & Order List

## Context

The store home page (`/store`) exists with a `StoreController::home()` method that renders `store/HomePage.vue`. It already shows creator cards with status filtering (active/paused/ended), current order info, past order count, and "Order History" / "New Order" buttons. However, the creator cards lack order count breakdowns (draft, open, closed), and clicking "Order History" navigates to `/store/creators/{id}/orders` which has no route or controller action. The store route file (`routes/store.php`) only has three routes: home, settings, and import. No per-creator order listing exists.

## Goal

Rework the store home page to show draft/open/closed order counts per creator, and build the per-creator orders page at `/store/{creator}/orders` with a filterable list showing order date, total, status, skein count, colorway count, and appropriate action buttons. After this prompt, stores can see their orders per creator and navigate to drafts or submitted orders.

## Non-Goals

- Do not build the order builder (that's Story 3.2-3.3)
- Do not build the order detail page (that's Story 3.4)
- Do not modify the creator-side order pages
- Do not add new routes for order creation or editing yet (the "New Order" button will link to a route built in Story 3.2)
- Do not modify models, policies, or enums

## Constraints

- Routes go in `routes/store.php` within the existing `prefix('store')->middleware(['auth', 'verified'])` group
- The orders page needs a controller action -- add it to `StoreController` (or create a dedicated `Store\OrderController` if cleaner, but follow the existing pattern of using `StoreController` for store-facing pages)
- The `creator` parameter in `/store/{creator}/orders` refers to a `Creator` model ID. The controller must verify the store has a relationship with this creator (via `creator_store` pivot)
- Use Inertia to render Vue pages in `resources/js/pages/store/`
- Follow the existing HomePage.vue patterns: TypeScript interfaces for props, `UiCard`, `UiTag`, `UiButton`, `StoreLayout` components
- The "Continue Order" button for drafts should link to `/store/{creator}/order?draft={orderId}` (the route built in Story 3.2)
- The "View Order" button should link to `/store/orders/{order}` (built in Story 3.4). For now, make it a dead link or disabled button.

## Acceptance Criteria

- [ ] `StoreController::home()` updated to include order counts per creator:
  - `draft_count`: number of draft orders for this store+creator
  - `open_count`: number of open orders
  - `closed_count`: number of closed orders
- [ ] `HomePage.vue` updated to display order counts instead of "current order" and "past orders" info:
  - Each creator card shows: draft count, open count, closed count
  - Buttons remain "View Orders" and "New Order"
- [ ] New route: `GET /store/{creator}/orders` renders the order list page
- [ ] New controller action returns orders for the authenticated store + specified creator:
  - Only wholesale orders where `orderable_type = Store` and `orderable_id = store.id` and `account_id = creator.account_id`
  - Each order includes: id, order_date, status, total_amount, skein_count (sum of orderItems quantity), colorway_count (distinct colorway_ids in orderItems)
  - Filter by status query parameter
  - Verify the store has a `creator_store` relationship with this creator (403 if not)
- [ ] New Vue page `store/orders/OrderListPage.vue`:
  - Shows creator name in header
  - List of orders with: order date, total amount, status tag, skein count, colorway count
  - Status filter dropdown (All, Draft, Open, Closed, Cancelled)
  - Draft orders show "Continue Order" button, all others show "View Order" button
  - Empty state when no orders exist
- [ ] "New Order" button on both pages links to `/store/{creator}/order` (route created in Story 3.2 -- can be a dead link for now)
- [ ] Tests: controller tests verifying authorization, data shape, and filtering

---

## Tech Analysis

- **Existing `transformCreatorsForHome`** (StoreController lines 139-183) queries orders per creator and computes `current_order` and `past_order_count`. Replace this with order count breakdowns. The query pattern stays the same: `Order::query()->where('type', OrderType::Wholesale)->where('orderable_type', Store::class)->where('orderable_id', $store->id)->where('account_id', $creator->account_id)`.
- **Order counts** can use `->where('status', OrderStatus::Draft)->count()` for each status, or more efficiently use `->selectRaw("status, count(*) as count")->groupBy('status')` and then map the results. The simpler approach (3 count queries per creator) is fine for the expected volume (few creators per store).
- **Skein count**: `$order->orderItems->sum('quantity')` -- the orderItems relationship is already loaded in the existing query pattern.
- **Colorway count**: `$order->orderItems->pluck('colorway_id')->unique()->count()` -- distinct colorway IDs across all items.
- **Creator-store authorization**: The controller needs to verify the store has a relationship with the requested creator. Use `$store->creators()->where('creator_id', $creator->id)->exists()` or check the pivot. If no relationship exists, abort with 403.
- **Route parameter**: `Route::get('{creator}/orders', ...)` where `{creator}` is a Creator model ID. Laravel's route model binding will resolve it automatically.
- **Vue page location**: Create at `resources/js/pages/store/orders/OrderListPage.vue` to keep store pages organized in subdirectories.
- **Status filter**: Use the same URL query parameter pattern as the existing home page (`?status=draft`). Default to "all" for the order list (unlike home which defaults to "active" for creator status).

## References

- `platform/app/Http/Controllers/StoreController.php` -- existing `home()` method and `transformCreatorsForHome()` to modify
- `platform/resources/js/pages/store/HomePage.vue` -- existing template to update (creator card layout, order info section)
- `platform/routes/store.php` -- add new route
- `platform/app/Models/Order.php` -- OrderType, OrderStatus enums, orderItems relationship
- `platform/app/Models/OrderItem.php` -- quantity, colorway_id fields
- `platform/app/Models/Store.php` -- `creators()` BelongsToMany relationship
- `platform/app/Models/Creator.php` -- `account_id` field for scoping orders
- `platform/resources/js/pages/creator/orders/OrderIndexPage.vue` -- reference for order list UI patterns (data table, status tags, formatting)
- `platform/resources/js/layouts/StoreLayout.vue` -- layout component for store pages
- `platform/resources/js/components/ui/UiCard.vue` -- card component pattern
- `platform/resources/js/components/ui/UiTag.vue` -- status tag component
- `platform/resources/js/components/PageFilter.vue` -- filter component with count display

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- update `home()` to return order counts; add `orders()` action for per-creator order list
- Modify `platform/resources/js/pages/store/HomePage.vue` -- update creator cards with order count display
- Create `platform/resources/js/pages/store/orders/OrderListPage.vue` -- order list page with filters and action buttons
- Modify `platform/routes/store.php` -- add `GET /{creator}/orders` route
- Create `platform/tests/Feature/Http/Controllers/Store/HomePageTest.php` -- tests for updated home page data
- Create `platform/tests/Feature/Http/Controllers/Store/OrderListTest.php` -- tests for order list authorization, data, and filtering
