status: pending

# Story 3.3: Prompt 2 -- Order Review Page (Vue)

## Context

Prompt 1 built the backend: three routes (`GET review`, `POST save`, `POST submit`), controller actions that load colorway/base data with wholesale pricing, create/update draft orders with OrderItems, validate minimums on submit, and handle draft resumption. The review action returns colorways with bases (including `wholesale_price`, `inventory_quantity`), wholesale terms (`discount_rate`, `minimum_order_quantity`, `minimum_order_value`, `allows_preorders`), and draft data if resuming. No Vue page exists for step 2.

## Goal

Build `OrderReviewPage.vue` -- the full-width Vue page for step 2 of the order flow. Shows selected colorways with horizontal base rows, quantity inputs, inventory availability, preorder handling, order notes, minimum order feedback, pricing summary, and save/submit buttons. Submits to the save and submit POST routes from Prompt 1.

## Non-Goals

- Do not modify controller actions (those are done in Prompt 1)
- Do not build the order detail page (that's Story 3.4)
- Do not add email notifications
- Do not add payment processing

## Constraints

- Page lives at `resources/js/pages/store/orders/OrderReviewPage.vue`
- Follow existing Vue patterns: TypeScript interfaces for props, `StoreLayout`, UI components (`UiCard`, `UiButton`, `UiTag`, `UiFormFieldSelect`)
- The page receives data from the `review()` Inertia response (Prompt 1)
- Form submissions use Inertia's `router.post()` to the save and submit routes
- All pricing calculations (totals, minimum progress) are reactive and computed client-side from the quantity inputs
- The `order_id` from a save response must be tracked so subsequent saves update the same draft instead of creating a new one

## Acceptance Criteria

- [ ] Vue page `store/orders/OrderReviewPage.vue` renders with data from the review controller action
- [ ] **Props interface** matches the controller's Inertia response:
  - `creator`: `{ id, name }`
  - `colorways`: array of `{ id, name, primary_image_url, bases: [{ id, descriptor, weight, retail_price, wholesale_price, inventory_quantity }] }`
  - `wholesaleTerms`: `{ discount_rate, minimum_order_quantity, minimum_order_value, allows_preorders }`
  - `draftOrder`: nullable `{ id, notes, items: [{ colorway_id, base_id, quantity }] }` (present when resuming a draft)
- [ ] **Colorway list** (full-width):
  - Each colorway rendered as a card/section with: name, primary image (or placeholder)
  - **Remove button** per colorway (removes from the list, updates all computed values)
  - Horizontal row of bases per colorway, each with:
    - Base descriptor and weight (formatted, e.g., "Merino Worsted")
    - Wholesale price displayed (formatted as currency)
    - **Number input** for quantity (min: 0, step: 1)
    - Small text below input: "{N} available" showing `inventory_quantity`
    - If `allows_preorders` is false and `inventory_quantity` is 0: input is disabled/greyed out
    - If `allows_preorders` is true: input is always enabled regardless of inventory
- [ ] **Quantity state management**:
  - Reactive object mapping `colorway_id-base_id` to quantity values
  - Pre-populated from `draftOrder.items` when resuming
  - Initialized to 0 for new orders
- [ ] **Bottom section**:
  - **Order notes** textarea (pre-populated from draft if resuming)
  - **Minimum order feedback**:
    - If `minimum_order_quantity` is set: progress indicator showing "Skeins: {current} / {minimum}" with visual indicator (bar or text color change when met)
    - If `minimum_order_value` is set: progress indicator showing "Total: {current} / {minimum}"
    - Both indicators show green/success when the threshold is met
  - **Pricing summary**: subtotal, shipping ($0.00), discount ($0.00), tax ($0.00), total -- all computed reactively from current quantities and wholesale prices
  - **Action buttons**:
    - "Back" -- navigates to step 1 (`/store/{creator}/order`) preserving current colorway selection in URL params
    - "Save as Draft" -- posts to save route with `{ order_id, notes, items }`, always enabled
    - "Submit Order" -- posts to submit route with `{ order_id }`, disabled until all minimums are met
- [ ] **Computed values** (all reactive):
  - `skeinCount`: sum of all quantities
  - `colorwayCount`: number of colorways with at least one quantity > 0
  - `lineTotal(colorwayId, baseId)`: wholesale_price * quantity for a specific item
  - `subtotal`: sum of all line totals
  - `total`: subtotal (same as subtotal for Stage 1)
  - `minimumsAreMet`: boolean computed from current totals vs thresholds
- [ ] **Draft tracking**: after a successful save, store the returned `order_id` so subsequent saves update the same draft
- [ ] Responsive layout: on mobile, base rows stack vertically within each colorway

---

## Tech Analysis

- **Quantity state**: Use a reactive `Map<string, number>` or object with keys like `"${colorwayId}-${baseId}"`. Initialize from draft items or default to 0. Example:
  ```typescript
  const quantities = ref<Record<string, number>>({});
  const getQuantityKey = (colorwayId: number, baseId: number) => `${colorwayId}-${baseId}`;
  ```
- **Computed pricing**: Use Vue `computed()` for all derived values. The subtotal is `Object.entries(quantities.value).reduce((sum, [key, qty]) => sum + qty * getWholesalePrice(key), 0)`. This recalculates automatically when any quantity changes.
- **Minimum feedback**: Compare computed `skeinCount` against `wholesaleTerms.minimum_order_quantity` and `subtotal` against `wholesaleTerms.minimum_order_value`. If either minimum is set and not met, `minimumsAreMet` is false and the submit button is disabled.
- **Form submission**: Use Inertia's `router.post()`:
  ```typescript
  router.post(`/store/${creator.id}/order/save`, {
    order_id: orderId.value,
    notes: notes.value,
    items: buildItemsPayload(),
  });
  ```
  The `buildItemsPayload()` function filters to items with quantity > 0 and maps to `{ colorway_id, base_id, quantity }`.
- **Draft ID tracking**: After save, the redirect includes the order ID (either via flash data or URL param). Use `usePage().props` to read flash data, or the redirect URL. Alternatively, the save action could return JSON with the order_id.
- **Removing a colorway**: Remove the colorway from the reactive list and clean up its quantity entries. The computed values automatically update.
- **Back button**: Navigate to `/store/${creator.id}/order?colorways=${currentColorwayIds.join(',')}` to preserve the selection in step 1.
- **Number input component**: Use a standard HTML `<input type="number">` styled with Tailwind, or use a PrimeVue `InputNumber` component if available. Bind to the quantities reactive object.
- **Pre-populating from draft**: When `draftOrder` prop is present:
  ```typescript
  if (props.draftOrder) {
    orderId.value = props.draftOrder.id;
    notes.value = props.draftOrder.notes ?? '';
    props.draftOrder.items.forEach(item => {
      quantities.value[getQuantityKey(item.colorway_id, item.base_id)] = item.quantity;
    });
  }
  ```
- **Currency formatting**: Reuse the `formatCurrency()` helper pattern from `HomePage.vue` (`Intl.NumberFormat`).

## References

- `platform/resources/js/pages/store/HomePage.vue` -- existing component patterns, formatting helpers
- `platform/resources/js/pages/store/orders/ColorwaySelectionPage.vue` -- step 1 page (from Story 3.2), navigation back
- `platform/resources/js/layouts/StoreLayout.vue` -- layout component
- `platform/resources/js/components/ui/UiCard.vue` -- card component
- `platform/resources/js/components/ui/UiButton.vue` -- button component
- `platform/resources/js/components/ui/UiTag.vue` -- tag component
- `platform/app/Http/Controllers/StoreController.php` -- review(), saveOrder(), submitOrder() actions (Prompt 1)
- `platform/routes/store.php` -- POST save and submit routes (Prompt 1)

## Files

- Create `platform/resources/js/pages/store/orders/OrderReviewPage.vue` -- full-width layout with base rows, quantity inputs, pricing, notes, minimum feedback, save/submit
