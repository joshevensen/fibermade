status: done

# Story 3.3: Prompt 2 -- Base & Quantity Selection Page (Vue)

## Context

Prompt 1 built the backend: three routes (`GET review`, `POST save`, `POST submit`), controller actions that load colorway/base data with wholesale pricing, create/update draft orders with OrderItems, validate minimums on submit, and handle draft resumption. The review action returns colorways with bases (including `wholesale_price`, `inventory_quantity`), wholesale terms, and draft data if resuming. The `BaseQuantitySelectionPage.vue` stub exists but shows raw JSON only. Implementation requires the page to receive camelCase props (`wholesaleTerms`, `draftOrder` with `notes`) — the backend may need a small update if it currently returns snake_case or omits draft notes.

## Goal

Build the full implementation of `BaseQuantitySelectionPage.vue` — the full-width Vue page for step 2 of the order flow. Shows selected colorways with horizontal base rows, quantity inputs, inventory availability, preorder handling, order notes, minimum order feedback, pricing summary, and save/submit buttons. Submits to the save and submit POST routes from Prompt 1.

## Non-Goals

- Do not modify controller actions beyond any minor prop transformation needed for camelCase/notes
- Do not build the order detail page (that's Story 3.4)
- Do not add email notifications
- Do not add payment processing

## Constraints

- Page lives at `resources/js/pages/store/orders/BaseQuantitySelectionPage.vue`
- Follow existing Vue patterns: TypeScript interfaces for props, `StoreLayout`, UI components (`UiCard`, `UiButton`, `UiTag`, `UiFormFieldSelect`)
- The page receives data from the `review()` Inertia response (Prompt 1)
- Form submissions use Inertia's `<Form>` or `useForm` for validation error handling
- Use Wayfinder-generated route helpers for save and submit URLs (e.g. `saveOrder.post()`, `submitOrder.post()`)
- All pricing calculations (totals, minimum progress) are reactive and computed client-side from the quantity inputs
- When resuming a draft, `orderId` from `draftOrder` is sent with save/submit so the same draft is updated; save redirects to the order list (subsequent saves happen only when the user resumes a draft from the order list)

## Acceptance Criteria

- [ ] Vue page `store/orders/BaseQuantitySelectionPage.vue` renders with data from the review controller action
- [ ] **Props interface** matches the controller's Inertia response (camelCase; backend to transform if needed):
  - `creator`: `{ id, name }`
  - `colorways`: array of `{ id, name, primaryImageUrl, bases: [{ id, descriptor, weight, retailPrice, wholesalePrice, inventoryQuantity }] }`
  - `wholesaleTerms`: `{ discountRate, minimumOrderQuantity, minimumOrderValue, allowsPreorders }`
  - `draftOrder`: nullable `{ orderId, notes, items: [{ colorwayId, baseId, quantity }] }` (present when resuming a draft)
- [ ] **Colorway list** (full-width):
  - Each colorway rendered as a card/section with: name, primary image (or placeholder)
  - **Remove button** per colorway (removes from the list, updates all computed values)
  - Horizontal row of bases per colorway, each with:
    - Base descriptor and weight (formatted, e.g., "Merino Worsted")
    - Wholesale price displayed (formatted as currency)
    - **Number input** for quantity (min: 0, step: 1)
    - Small text below input: "{N} available" showing `inventoryQuantity`
    - If `allowsPreorders` is false and `inventoryQuantity` is 0: input is disabled/greyed out
    - If `allowsPreorders` is true: input is always enabled regardless of inventory
- [ ] **Empty state**: when the user has removed all colorways, show a clear message (e.g. "No colorways selected") with a prompt to go back and select colorways; disable Save and Submit
- [ ] **Quantity state management**:
  - Reactive object mapping `colorwayId-baseId` to quantity values
  - Pre-populated from `draftOrder.items` when resuming
  - Initialized to 0 for new orders
- [ ] **Bottom section**:
  - **Order notes** textarea (pre-populated from `draftOrder.notes` if resuming)
  - **Minimum order feedback**:
    - If `minimumOrderQuantity` is set: progress indicator showing "Skeins: {current} / {minimum}" with visual indicator (bar or text color change when met)
    - If `minimumOrderValue` is set: progress indicator showing "Total: {current} / {minimum}"
    - Both indicators show green/success when the threshold is met
  - **Validation errors**: display Inertia validation errors for `minimum_order_quantity` and `minimum_order_value` (422 from submit) near the minimum feedback area
  - **Pricing summary**: subtotal, shipping ($0.00), discount ($0.00), tax ($0.00), total — all computed reactively from current quantities and wholesale prices
  - **Action buttons**:
    - "Back" — navigates to step 1 (`/store/{creator}/order`) preserving colorways currently visible on the review page (after any removals) in URL params `?colorways=1,2,3`
    - "Save as Draft" — posts to save route with `{ order_id, notes, items }`, always enabled (except when no colorways)
    - "Submit Order" — posts to submit route with `{ order_id }`, disabled until all minimums are met
- [ ] **Computed values** (all reactive):
  - `skeinCount`: sum of all quantities
  - `colorwayCount`: number of colorways with at least one quantity > 0 (optional display, e.g. in summary or for future use)
  - `lineTotal(colorwayId, baseId)`: wholesalePrice * quantity for a specific item
  - `subtotal`: sum of all line totals
  - `total`: subtotal (same as subtotal for Stage 1)
  - `minimumsAreMet`: boolean computed from current totals vs thresholds
- [ ] **Draft tracking**: when `draftOrder` is present, use `draftOrder.orderId` in save/submit payloads; after save, user is redirected to order list (no in-page subsequent save)
- [ ] Responsive layout: on mobile, base rows stack vertically within each colorway

## Prerequisites / Cross-Cutting

- **Step 1 → Step 2 URL**: ColorwaySelectionPage (step 1) must navigate to `/store/{creator}/order/review?colorways=...` (not `/order/step-2`). Update the "Continue" action in ColorwaySelectionPage if it currently uses the wrong path.

## Tech Analysis

- **Quantity state**: Use a reactive `Map<string, number>` or object with keys like `"${colorwayId}-${baseId}"`. Initialize from draft items or default to 0. Example:
  ```typescript
  const quantities = ref<Record<string, number>>({});
  const getQuantityKey = (colorwayId: number, baseId: number) => `${colorwayId}-${baseId}`;
  ```
- **Computed pricing**: Use Vue `computed()` for all derived values. The subtotal is `Object.entries(quantities.value).reduce((sum, [key, qty]) => sum + qty * getWholesalePrice(key), 0)`. This recalculates automatically when any quantity changes.
- **Minimum feedback**: Compare computed `skeinCount` against `wholesaleTerms.minimumOrderQuantity` and `subtotal` against `wholesaleTerms.minimumOrderValue`. If either minimum is set and not met, `minimumsAreMet` is false and the submit button is disabled.
- **Form submission**: Use Inertia's `<Form>` or `useForm` for save and submit. Wire action/method via Wayfinder, e.g. `saveOrder.form(creator.id)` or `submitOrder.form(creator.id)`. Map reactive state (`orderId`, `notes`, `items`) into form fields or submit handler. Use form's `errors` to display `minimum_order_quantity` and `minimum_order_value` validation messages.
- **Wayfinder**: Import `saveOrder` and `submitOrder` from the Store controller actions. Use for URLs and form attributes.
- **Removing a colorway**: Remove the colorway from the reactive list and clean up its quantity entries. The computed values automatically update.
- **Back button**: Navigate to `/store/${creator.id}/order?colorways=${currentColorwayIds.join(',')}` where `currentColorwayIds` are the IDs of colorways still shown on the page (after any removals).
- **Number input component**: Use a standard HTML `<input type="number">` styled with Tailwind, or use a PrimeVue `InputNumber` component if available. Bind to the quantities reactive object.
- **Pre-populating from draft**: When `draftOrder` prop is present:
  ```typescript
  if (props.draftOrder) {
    orderId.value = props.draftOrder.orderId;
    notes.value = props.draftOrder.notes ?? '';
    props.draftOrder.items.forEach(item => {
      quantities.value[getQuantityKey(item.colorwayId, item.baseId)] = item.quantity;
    });
  }
  ```
- **Currency formatting**: Reuse the `formatCurrency()` helper pattern from `ColorwaySelectionPage.vue` or `HomePage.vue` (`Intl.NumberFormat`).

## References

- `platform/resources/js/pages/store/HomePage.vue` — existing component patterns, formatting helpers
- `platform/resources/js/pages/store/orders/ColorwaySelectionPage.vue` — step 1 page (from Story 3.2), navigation back, formatCurrency pattern
- `platform/resources/js/layouts/StoreLayout.vue` — layout component
- `platform/resources/js/components/ui/UiCard.vue` — card component
- `platform/resources/js/components/ui/UiButton.vue` — button component
- `platform/resources/js/components/ui/UiTag.vue` — tag component
- `platform/app/Http/Controllers/StoreController.php` — review(), saveOrder(), submitOrder() actions (Prompt 1)
- `platform/routes/store.php` — POST save and submit routes (Prompt 1)
- Wayfinder-generated Store controller actions for route helpers

## Files

- Implement `platform/resources/js/pages/store/orders/BaseQuantitySelectionPage.vue` — full-width layout with base rows, quantity inputs, pricing, notes, minimum feedback, validation errors, empty state, save/submit
- Update `platform/resources/js/pages/store/orders/ColorwaySelectionPage.vue` — fix "Continue" to navigate to `/store/{creator}/order/review?colorways=...` (if currently using step-2)
