status: pending

# Story 4.3: Prompt 1 -- Order Detail Page (Creator)

## Context

Stories 4.1 and 4.2 updated the `OrderStatus` enum, enhanced the order dashboard with badges and sorting, and built the order processing workflow (backend transitions + UI action buttons). The creator-side `OrderEditPage.vue` was enhanced in Story 4.2 with workflow action buttons, confirmation dialogs, and a status progression indicator. However, the page was originally scaffolded as a generic order edit page -- it shows status, order date, notes, order items table, orderable info sidebar, and totals sidebar. It lacks wholesale-specific information: store contact details, full address, the wholesale terms negotiated between the creator and store (discount rate, payment terms, lead time from the `creator_store` pivot), and the base `descriptor` is truncated. The `OrderController::edit()` loads the order with `orderItems.colorway`, `orderItems.base`, and `orderable` but does not load the `creator_store` pivot data for wholesale orders. The store-side has its own `OrderDetailPage.vue` (Epic 3, Story 3.4) which is a separate page.

## Goal

Enhance the creator-side `OrderEditPage.vue` to be a comprehensive wholesale order detail page. Add store contact information (name, owner, email, address), wholesale terms from the `creator_store` pivot (discount rate, payment terms, lead time, minimums, preorder settings), and improve the line items display with base descriptor and weight. The controller loads the pivot data for wholesale orders and passes it to the page.

## Non-Goals

- Do not build a separate show page -- enhance the existing `OrderEditPage.vue`
- Do not add order editing capabilities for line items (orders are read-only for creators after store submission)
- Do not build a status history timeline or audit log
- Do not add packing slips, invoices, or printable views
- Do not modify the store-side order detail page

## Constraints

- The `OrderController::edit()` method already loads `orderItems.colorway`, `orderItems.base`, `orderable`, and `externalIdentifiers.integration`. For wholesale orders, additionally load the `creator_store` pivot data by querying the pivot table where `creator_id` matches the order's account creator and `store_id` matches the orderable.
- Only load and display wholesale terms when `order.type === 'wholesale'` -- the page should still work for retail and show orders without the wholesale-specific sections
- Store info section should show: store name (linked to store edit page), owner name, email, full address (line1, line2, city, state, postal, country)
- Wholesale terms section should show: discount rate (%), payment terms, lead time (days), minimum order quantity, minimum order value ($), preorder allowance
- Follow the existing sidebar card pattern in `OrderEditPage.vue` -- store info and terms go in the sidebar
- The line items table should show: Colorway name, Base descriptor (e.g., "Sock - 100g"), Quantity, Unit Price, Line Total

## Acceptance Criteria

- [ ] `OrderController::edit()` loads wholesale terms for wholesale orders:
  - Retrieves the `creator_store` pivot record matching the order's creator and store
  - Passes `wholesaleTerms` object to the page with: discount_rate, payment_terms, lead_time_days, minimum_order_quantity, minimum_order_value, allows_preorders
  - For non-wholesale orders, `wholesaleTerms` is null
- [ ] `OrderEditPage.vue` store info sidebar card (only for wholesale orders):
  - Store name as a link to the store edit page
  - Owner name
  - Email
  - Full formatted address
- [ ] `OrderEditPage.vue` wholesale terms sidebar card (only for wholesale orders):
  - Discount rate displayed as percentage
  - Payment terms
  - Lead time in days
  - Minimum order quantity
  - Minimum order value formatted as currency
  - Preorder status (yes/no)
- [ ] Line items table shows base descriptor (e.g., "Sock - 100g") instead of just base code
- [ ] Non-wholesale orders (retail, show) render without the wholesale-specific sidebar cards
- [ ] Tests verify:
  - Wholesale order edit loads pivot data correctly
  - Non-wholesale order edit does not load pivot data
  - Store info is present in the response for wholesale orders
- [ ] `php artisan test --filter=OrderControllerTest` passes

---

## Tech Analysis

- **Loading the creator_store pivot**: The order's `account_id` belongs to the creator. The order's `orderable_id` is the store's ID (when type is wholesale). The `creator_store` pivot links `creator_id` ↔ `store_id`. To get the pivot data:
  1. Get the creator from the order's account: `$order->account->creator`
  2. Query the pivot: `$creator->stores()->where('store_id', $order->orderable_id)->first()?->pivot`
  This gives access to all the pivot fields. Alternatively, query `DB::table('creator_store')` directly, but using the relationship is cleaner.
- **Creator model relationship**: The `Account` model has a `creator()` HasOne relationship. The `Creator` model has a `stores()` BelongsToMany through `creator_store` with pivot fields. Need to verify the pivot fields are declared in the `withPivot()` call on the relationship.
- **Conditional loading**: In the controller, check `$order->type === OrderType::Wholesale` before loading pivot data. This keeps the logic clean and avoids unnecessary queries for other order types.
- **Store info from orderable**: The order's `orderable` relationship already loads the Store model for wholesale orders. This gives us name, email, owner_name, and address fields directly -- no additional query needed for store info.
- **Base descriptor display**: The `OrderItem` belongs to a `Base` which has `descriptor` and `weight` fields. The `orderItems.base` relationship is already eager-loaded. Just update the template to show `base.descriptor` instead of `base.code`.
- **Page Props**: Add `wholesaleTerms` to the Props interface as an optional object. The existing Props interface already has `order.orderable` typed as `OrderableStore` for wholesale orders. The wholesale terms are separate from the orderable since they come from the pivot table (creator-specific terms for that store).

## References

- `platform/app/Http/Controllers/OrderController.php` -- `edit()` action to enhance with pivot loading
- `platform/app/Models/Order.php` -- relationships, type checking
- `platform/app/Models/Account.php` -- `creator()` HasOne relationship
- `platform/app/Models/Creator.php` -- `stores()` BelongsToMany with pivot
- `platform/app/Models/Store.php` -- fields (name, email, owner_name, address fields)
- `platform/database/migrations/2026_01_10_173438_create_creator_store_table.php` -- pivot table structure
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- page to enhance
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- reference for store info display and wholesale terms form layout
- `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- existing test patterns

## Files

- Modify `platform/app/Http/Controllers/OrderController.php` -- load and pass `wholesaleTerms` in `edit()` for wholesale orders
- Modify `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- add store info card, wholesale terms card, improve line items display
- Modify `platform/tests/Feature/Http/Controllers/OrderControllerTest.php` -- add tests for wholesale terms loading
