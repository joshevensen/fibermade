status: pending

# Story 3.3: Prompt 1 -- Order Builder Backend (Routes, Save, Submit)

## Context

Story 3.2 built the colorway selection page at `/store/{creator}/order`. The store browses active colorways, selects which ones to include, and clicks "Continue" which passes the selected colorway IDs to step 2. No step 2 page, order creation logic, or submission flow exists. The Order model supports draft and open statuses. The `creator_store` pivot has `discount_rate`, `minimum_order_quantity`, `minimum_order_value`, and `allows_preorders` fields. OrderItems reference a specific Colorway + Base + quantity + unit_price + line_total.

## Goal

Build the backend for step 2 of the order flow: the review route that loads colorway/base data with wholesale pricing, the save action that creates/updates draft Orders with OrderItems, the submit action that validates minimums and changes status to open, and draft order resumption. Prompt 2 builds the Vue page.

## Non-Goals

- Do not build the Vue page (that's Prompt 2)
- Do not build the order detail page (that's Story 3.4)
- Do not add email notifications (that's Epic 5)
- Do not handle order editing after submission (orders are read-only once submitted)
- Do not add payment processing
- Do not modify Order or OrderItem models

## Constraints

- Routes in `routes/store.php`:
  - `GET /store/{creator}/order/review` -- loads data for step 2
  - `POST /store/{creator}/order/save` -- saves as draft
  - `POST /store/{creator}/order/submit` -- validates minimums and submits
- The controller must verify the store-creator relationship on all three routes (403 if not)
- When resuming a draft: `GET /store/{creator}/order/review?draft={orderId}` loads existing order with items pre-populated
- OrderPolicy write actions were re-enabled in Epic 0 Story 0.6 for the API. For the web store context, the store user needs to create orders. Authorization should be handled in the controller (check store-creator relationship) since the order's `account_id` is the creator's, not the store user's.
- Wholesale price calculation: `retail_price * (1 - discount_rate)` where `discount_rate` is from the `creator_store` pivot
- The Order's `account_id` should be the **creator's** `account_id` (the order belongs to the creator's account, with the store as the orderable)

## Acceptance Criteria

- [ ] New route: `GET /store/{creator}/order/review` renders the base & quantity selection page
- [ ] New route: `POST /store/{creator}/order/save` saves the order as a draft
- [ ] New route: `POST /store/{creator}/order/submit` validates minimums and submits the order
- [ ] `review()` controller action:
  - Receives colorway IDs via `?colorways=1,2,3` query param (new order) or `?draft={orderId}` (resuming draft)
  - Verifies store-creator relationship (403 if not)
  - Loads colorways with `inventories.base` and `media`, scoped to creator's account
  - Calculates wholesale prices: `retail_price * (1 - discount_rate)`, rounded to 2 decimal places
  - Loads wholesale terms: `discount_rate`, `minimum_order_quantity`, `minimum_order_value`, `allows_preorders`
  - For draft resumption: loads existing Order with orderItems, verifies it belongs to this store+creator and is draft status
  - Returns via Inertia: colorways with bases (including wholesale_price, inventory_quantity), wholesale terms, draft data if resuming
- [ ] Colorway data shape returned to Vue:
  - `id`, `name`, `primary_image_url`
  - `bases`: array of `{ id, descriptor, weight, retail_price, wholesale_price, inventory_quantity }`
- [ ] `saveOrder()` controller action:
  - Creates or updates an Order:
    - `account_id` = creator's account_id
    - `type` = wholesale, `status` = draft
    - `orderable_type` = Store::class, `orderable_id` = store.id
    - `created_by` = authenticated user's id
  - Creates/replaces OrderItems: one per colorway+base combination with quantity > 0
    - `unit_price` = wholesale price
    - `line_total` = unit_price * quantity
  - Calculates `subtotal_amount` and `total_amount` on the Order
  - If an `order_id` is present in the request, update the existing draft (verify ownership + draft status); otherwise create a new order
  - Redirects to order list with success message, includes order_id in response for subsequent saves
- [ ] `submitOrder()` controller action:
  - Loads the draft order (from `order_id` in request), verifies ownership + draft status
  - Validates minimum_order_quantity: total skeins >= minimum (if set)
  - Validates minimum_order_value: total amount >= minimum (if set)
  - Returns validation error if minimums not met
  - On success: sets `status` = open, `order_date` = today
  - Redirects to order detail page (`/store/orders/{order}`) with success message
- [ ] Tests covering:
  - Review: authorization (403 for unrelated store), data loading with correct wholesale prices, draft resumption
  - Save: creates Order + OrderItems, updates existing draft, ignores zero-quantity items, calculates totals correctly
  - Submit: validates minimums (rejects below, accepts at/above), changes status to open, sets order_date
  - Draft resumption: can't resume non-draft orders, can't resume orders belonging to other stores

---

## Tech Analysis

- **Two-step flow data passing**: Step 1 passes colorway IDs to step 2 via URL query params: `GET /store/{creator}/order/review?colorways=1,2,3`. Parse as comma-separated integers.
- **Loading colorway data for review**: `Colorway::whereIn('id', $ids)->where('account_id', $creator->account_id)->with(['inventories.base', 'media'])->get()`. Verify all requested colorways belong to this creator's account.
- **Draft order resumption**: When `?draft={orderId}` is present:
  1. Load the Order with `orderItems.colorway` and `orderItems.base`
  2. Verify the order belongs to this store (`orderable_type/id`) and creator (`account_id`)
  3. Verify status is draft (can't resume non-draft orders)
  4. Extract the colorway IDs from existing items and load full colorway data (same as new order flow)
  5. Pass existing quantities to pre-populate the form
- **Wholesale price calculation**: Done in the controller. `wholesale_price = round(retail_price * (1 - discount_rate), 2)`.
- **Minimum order validation** (submit action):
  - `minimum_order_quantity`: total skeins (sum of all quantities) must be >= this value. If null, no minimum.
  - `minimum_order_value`: total dollar amount (sum of line_totals) must be >= this value. If null, no minimum.
  - Return validation errors if not met.
- **OrderPolicy considerations**: The order's `account_id` is the creator's, not the store user's. The existing `OrderPolicy::create()` checks `$user->account_id !== null` which passes for store users, but the order is created on a different account. Handle authorization in the controller by verifying the store-creator relationship instead of using the policy.
- **FormRequest**: The existing `StoreOrderRequest` validates order fields but requires `type` and `status` which the controller sets. Create a lightweight store-side request or validate manually in the controller. The request from the Vue page only sends: `order_id` (optional), `notes`, and `items` (array of `{ colorway_id, base_id, quantity }`).
- **OrderItem sync on save**: When updating a draft, delete existing items and re-create from the new data. This is simpler than diffing and handles additions/removals cleanly: `$order->orderItems()->delete()` then `$order->orderItems()->createMany($items)`.
- **Order totals**: `subtotal_amount = sum(line_totals)`. `total_amount = subtotal_amount` (shipping/discount/tax are 0 for Stage 1).
- **Preventing duplicate orders**: The first save returns the `order_id`. Subsequent saves include this ID and hit the update path. The Vue page (Prompt 2) will handle passing this ID back.

## References

- `platform/app/Http/Controllers/StoreController.php` -- add `review()`, `saveOrder()`, `submitOrder()` actions
- `platform/routes/store.php` -- add review, save, submit routes
- `platform/app/Models/Order.php` -- fields, casts, relationships (orderable, orderItems)
- `platform/app/Models/OrderItem.php` -- fields (order_id, colorway_id, base_id, quantity, unit_price, line_total)
- `platform/app/Models/Colorway.php` -- fields, inventories relationship, primary_image_url
- `platform/app/Models/Inventory.php` -- fields (colorway_id, base_id, quantity), base relationship
- `platform/app/Models/Base.php` -- fields (descriptor, weight, retail_price)
- `platform/app/Models/Store.php` -- `creators()` pivot with discount_rate, minimum_order_quantity, minimum_order_value, allows_preorders
- `platform/app/Http/Requests/StoreOrderRequest.php` -- existing validation (reference, may not reuse directly)
- `platform/app/Policies/OrderPolicy.php` -- existing authorization (controller handles store-side auth separately)
- `platform/app/Enums/OrderType.php` -- Wholesale
- `platform/app/Enums/OrderStatus.php` -- Draft, Open

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- add `review()`, `saveOrder()`, `submitOrder()` actions
- Modify `platform/routes/store.php` -- add GET review, POST save, POST submit routes
- Create `platform/tests/Feature/Http/Controllers/Store/OrderBuilderTest.php` -- tests for review, save, submit, minimums, draft resumption
