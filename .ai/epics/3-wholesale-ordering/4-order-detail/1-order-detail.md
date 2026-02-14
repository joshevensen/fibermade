status: done

# Story 3.4: Prompt 1 -- Order Detail Page

## Context

Stories 3.1-3.3 built the store home page, order list, colorway selection, and base & quantity selection with order creation and submission. After submitting an order (status changes from draft to open), the store is redirected to the order detail page. The order list page has "View Order" buttons that also link here. Draft orders use "Continue Order" on the list (goes to the order builder); the detail page displays any order read-only when visited directly, including drafts. No order detail page exists for the store context. The creator-side has `OrderEditPage.vue` which shows order details in a read-only format with order items, orderable info, and totals -- this serves as a visual reference but the store-side page has its own layout requirements: status at the top, colorway-grouped base rows (matching the step 2 layout), and pricing summary.

## Goal

Build the read-only order detail page for stores at `/store/orders/{order}`. It displays the order status prominently, lists colorways with their base rows (quantities, prices, line totals), shows the pricing summary, order notes, and a status progression indicator. The layout matches the step 2 review page but is not editable.

## Non-Goals

- Do not add order editing capabilities (orders are read-only for stores after submission)
- Do not add order cancellation (that's Epic 4 -- creator-side)
- Do not add email notifications
- Do not modify models, policies, or enums

## Constraints

- Route: `GET /store/orders/{order}` in `routes/store.php`. Define this route before `store/{creator}/orders` so Laravel does not match "orders" as the creator segment.
- Authorization: the order must belong to the authenticated store (orderable_type = Store, orderable_id = store.id). Use the existing `OrderPolicy::view()` or add custom authorization in the controller.
- Load order with: orderItems (with colorway and base), orderable
- Group order items by colorway for display (matching the step 2 layout where each colorway has a row of bases)
- Status progression indicator shows the order's journey: draft → open → accepted → fulfilled → delivered (or cancelled). Highlight the current status.
- Follow the same visual patterns as `BaseQuantitySelectionPage.vue` (from Story 3.3) for consistency
- The page is purely read-only -- no forms, no inputs, no action buttons

## Acceptance Criteria

- [ ] New route: `GET /store/orders/{order}` renders the order detail page
- [ ] Controller action:
  - Loads order with `orderItems.colorway.media`, `orderItems.base`, `orderable`, `account.creator`
  - Verifies the order belongs to the authenticated store. Returns 404 if order does not exist (route model binding). Returns 403 if order exists but belongs to another store.
  - Groups order items by colorway for display
  - Returns data via Inertia
- [ ] Order data shape passed to Vue:
  - `id`, `order_date`, `status`, `notes`
  - `subtotal_amount`, `shipping_amount`, `discount_amount`, `tax_amount`, `total_amount`
  - `creator`: `{ id, name }` (from `$order->account->creator`, needed for Back button and page context)
  - `skein_count`, `colorway_count` (computed by controller: sum of item quantities; distinct colorway count)
  - `items_by_colorway`: array of `{ colorway: { id, name, primary_image_url }, bases: [{ id, descriptor, weight, quantity, unit_price, line_total }] }`
- [ ] Vue page `store/orders/OrderDetailPage.vue`:
  - **Status banner** at the top: prominent status tag (draft/open/accepted/fulfilled/delivered/cancelled) with color coding
  - **Status progression indicator**: horizontal steps showing draft → open → accepted → fulfilled → delivered, with the current step highlighted. If cancelled, show that state distinctly.
  - **Order metadata**: order date, creator name
  - **Colorway list** (full-width): same layout as step 2 review page but read-only
    - Each colorway shows: name, primary image
    - Horizontal row of bases: descriptor, weight, quantity, unit price, line total
    - No quantity inputs -- just display values
  - **Summary section** at the bottom:
    - Order notes (if present)
    - Pricing summary: subtotal, shipping, discount, tax, total
  - **Back button**: returns to order list (`/store/{creator}/orders`)
- [ ] Skein count and colorway count displayed somewhere visible (header or summary), using `skein_count` and `colorway_count` from props
- [ ] Tests: controller tests for authorization (store can view own orders; returns 403 for another store's orders; returns 404 for non-existent order), data loading, correct grouping

---

## Tech Analysis

- **Grouping items by colorway**: The OrderItems table has `colorway_id` and `base_id`. Multiple items can share the same `colorway_id` (different bases). Group with `$order->orderItems->groupBy('colorway_id')`. Then for each group, extract the colorway data from the first item and collect the base data from all items.
- **Authorization**: The `OrderPolicy::view()` checks `$user->account_id === $order->account_id`. However, the order's `account_id` is the **creator's** account_id (not the store's). The store user's account_id is different. This means the policy won't work as-is for store users. Options:
  1. Modify `OrderPolicy::view()` to also check if the order's orderable is the user's store
  2. Handle authorization directly in the controller: check `$order->orderable_type === Store::class && $order->orderable_id === $store->id`
  **Recommendation**: Add a check in the controller. The policy handles creator-side authorization; the controller handles store-side authorization for this specific route.
- **Status progression**: The OrderStatus enum has Draft, Open, Accepted, Fulfilled, Delivered, Cancelled. The progression is linear: Draft → Open → Accepted → Fulfilled → Delivered. Cancelled can happen from any active status. The Vue component should render this as a step indicator:
  - Step 1: Draft (completed if status is beyond draft)
  - Step 2: Open (completed if status is accepted or beyond; active if current)
  - Step 3: Accepted (completed if status is fulfilled or beyond; active if current)
  - Step 4: Fulfilled (completed if status is delivered; active if current)
  - Step 5: Delivered (active if current status is delivered)
  - If cancelled: show a "Cancelled" badge instead of the step indicator, or cross out remaining steps
- **Creator**: The order's `account_id` links to an Account, which has a `creator()` HasOne relationship. Pass `creator: { id, name }` from `$order->account->creator` for the Back button and metadata display.
- **Primary image URL**: Colorway's `primary_image_url` accessor. Load `orderItems.colorway.media` to ensure the accessor has data.
- **Page consistency**: This page should visually match the `BaseQuantitySelectionPage.vue` from Story 3.3 as closely as possible, just without the inputs, buttons, and minimums feedback. Consider extracting shared layout components (colorway row, base row, pricing summary) to reusable Vue components that both pages can use.
- **Route parameter**: `{order}` uses Laravel route model binding for the Order model. The controller receives an `Order` instance.

## References

- `platform/app/Http/Controllers/StoreController.php` -- add `showOrder()` action
- `platform/routes/store.php` -- add detail route
- `platform/app/Models/Order.php` -- fields, orderItems relationship, orderable MorphTo, account relationship
- `platform/app/Models/OrderItem.php` -- fields (colorway_id, base_id, quantity, unit_price, line_total), colorway/base relationships
- `platform/app/Models/Colorway.php` -- primary_image_url accessor, media relationship
- `platform/app/Models/Base.php` -- descriptor, weight fields
- `platform/app/Models/Account.php` -- `creator()` HasOne relationship
- `platform/app/Policies/OrderPolicy.php` -- existing view authorization (may not cover store-side access)
- `platform/app/Enums/OrderStatus.php` -- Draft, Open, Accepted, Fulfilled, Delivered, Cancelled
- `platform/resources/js/pages/store/orders/BaseQuantitySelectionPage.vue` -- step 2 layout to match (from Story 3.3)
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- creator-side order detail for reference
- `platform/resources/js/layouts/StoreLayout.vue` -- layout component

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- add `showOrder()` action
- Modify `platform/routes/store.php` -- add `GET /orders/{order}` route
- Create `platform/resources/js/pages/store/orders/OrderDetailPage.vue` -- read-only order detail with status, colorway rows, pricing
- Create `platform/tests/Feature/Http/Controllers/Store/OrderDetailTest.php` -- tests for authorization (store can view own orders, can't view others), data shape, colorway grouping
