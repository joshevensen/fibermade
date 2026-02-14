status: done

# Story 4.2: Prompt 2 -- Workflow UI

## Context

Prompt 1 built the backend for order status transitions: accept, fulfill, deliver, and cancel actions on the `OrderController` with PATCH routes and model-level transition validation. The `Order` model has `canTransitionTo()` and `transitionTo()` methods. Routes exist at `/creator/orders/{order}/accept`, `/fulfill`, `/deliver`, `/cancel`. The creator-side `OrderEditPage.vue` currently shows order details in a read-only format with status badge, order items table, orderable info sidebar, and totals sidebar. The status badge uses `getStatusBadgeClass()` (updated in Story 4.1 for the new statuses). The page has commented-out form submission and delete functionality from earlier scaffolding. No action buttons exist for workflow transitions.

## Goal

Add workflow action buttons to the `OrderEditPage.vue` that allow creators to advance orders through their lifecycle. Show the appropriate next-step button(s) based on the current order status, with a confirmation step that allows adding an optional note. Display a status progression indicator showing where the order is in the workflow.

## Non-Goals

- Do not add bulk order operations (processing multiple orders at once)
- Do not build a separate status history page or timeline component
- Do not add email notifications on status change
- Do not modify backend routes or controller logic (that's done in Prompt 1)

## Constraints

- Action buttons should be context-sensitive: only show valid next transitions for the current status
  - Open: show "Accept Order" (primary) and "Cancel Order" (danger, secondary)
  - Accepted: show "Mark as Fulfilled" (primary) and "Cancel Order" (danger, secondary)
  - Fulfilled: show "Mark as Delivered" (primary) and "Cancel Order" (danger, secondary)
  - Delivered/Cancelled/Draft: no action buttons
- For "Cancel Order", use `useConfirm().require()` with a custom message and danger severity (not `requireDelete()`).
- For accept/fulfill/deliver, use `UiDialog` (ref-controlled) with an optional notes textarea; show notes max length (1000 characters) in the UI.
- Submit transitions using Wayfinder-generated controller actions (`accept`, `fulfill`, `deliver`, `cancel` from `OrderController`) with `router.visit()` and the appropriate method and payload (e.g. `data: { note }`).
- The status progression indicator should show: Open → Accepted → Fulfilled → Delivered as horizontal steps. For Draft, do not show the stepper (status badge only).
- Use the existing UI component library (UiButton, UiCard, UiDialog, etc.).
- The controller passes `allowedTransitions` as a list of status values (e.g. `['accepted', 'cancelled']`); the frontend maps each value to route and button label.
- Disable workflow action buttons using a local ref (e.g. `isTransitioning`) set true on submit and false on success/error.
- On transition request failure (e.g. 422), show a toast with the error message and leave the user on the page.

## Acceptance Criteria

- [ ] `OrderController::edit()` passes `allowedTransitions` (list of status values, e.g. `['accepted', 'cancelled']` for an open order) from `Order::getAllowedTransitions()`
- [ ] `OrderEditPage.vue` shows action buttons based on `allowedTransitions`, mapping each value to Wayfinder action and label:
  - Primary button for forward transition (accept/fulfill/deliver)
  - Secondary danger button for cancel when `cancelled` is in `allowedTransitions`
- [ ] Clicking a primary action button opens `UiDialog` with:
  - Confirmation message (e.g., "Accept this order?")
  - Optional notes textarea with max length 1000 indicated (placeholder or helper text)
  - Confirm and Cancel buttons
- [ ] Clicking "Cancel Order" uses `useConfirm().require()` with order-specific message and danger severity
- [ ] Successful transitions redirect back to the order detail page with updated status
- [ ] On transition failure (e.g. 422), show a toast with the error message; user remains on the page
- [ ] Status progression indicator displays as horizontal steps: Open → Accepted → Fulfilled → Delivered
  - Current step highlighted; completed steps with check mark or filled style; future steps gray/unfilled
  - Cancelled orders show a "Cancelled" badge instead of the progression
  - Draft orders: do not show the stepper (status badge only)
- [ ] Action buttons are disabled while a transition request is in progress (local ref, e.g. `isTransitioning`)
- [ ] No action buttons shown for delivered, cancelled, or draft orders
- [ ] PHP feature test(s) verify `edit()` returns correct `allowedTransitions` for each status (required). Vue component tests for button/stepper rendering are optional if the project has Vue test infrastructure.

---

## Tech Analysis

- **Passing allowed transitions from controller**: Add `Order::getAllowedTransitions(): array` returning a list of status *values* (e.g. `['accepted', 'cancelled']`) so JSON/Inertia stay simple. In `OrderController::edit()`, pass `$order->getAllowedTransitions()` as `allowedTransitions`. Frontend maps each value to the corresponding Wayfinder action and button label.
- **Confirmation dialog (accept/fulfill/deliver)**: Use `UiDialog` controlled by a ref. State: e.g. `showTransitionDialog`, `pendingTransition`, `transitionNote`. Dialog contains confirmation message, optional notes textarea (max 1000 characters, show limit in placeholder or helper text), and Confirm/Cancel buttons. On confirm, call the appropriate Wayfinder action with `router.visit(..., { data: { note: transitionNote } })` and wire `onSuccess`/`onError` to close dialog, clear state, and (on error) show toast.
- **Cancel confirmation**: Use `useConfirm().require()` with a custom message (e.g. "Cancel this order? The customer will no longer be able to use it for fulfillment."), danger accept severity, and `onAccept` calling the cancel Wayfinder action.
- **Route URLs**: Use Wayfinder. Import `accept`, `fulfill`, `deliver`, `cancel` from `@/actions/App/Http/Controllers/OrderController`. Call e.g. `accept(order)` (or the correct Wayfinder signature for the order resource) to get `{ url, method }`, then `router.visit(url, { method, data: { note } })` (or equivalent per Wayfinder/Inertia docs).
- **Status progression**: Build inline in OrderEditPage. Horizontal flexbox of steps: Open → Accepted → Fulfilled → Delivered. Each step: circle + label; CSS for completed / current / upcoming. For Draft, do not render the stepper. For Cancelled, show "Cancelled" badge instead of stepper.
- **Button labels** (frontend map from `allowedTransitions` value to label):
  - `accepted` → "Accept Order"
  - `fulfilled` → "Mark as Fulfilled"
  - `delivered` → "Mark as Delivered"
  - `cancelled` → "Cancel Order"

## References

- `platform/app/Http/Controllers/OrderController.php` -- `edit()` action to add `allowedTransitions`
- `platform/app/Models/Order.php` -- `canTransitionTo()`, transition map from Prompt 1
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- main page to modify
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- reference for `useConfirm` pattern and `router` usage
- `platform/resources/js/composables/useConfirm.ts` -- `require()` for cancel confirmation
- `platform/resources/js/composables/useToast.ts` -- toast on transition failure
- `platform/resources/js/components/ui/UiButton.vue` -- button component
- `platform/resources/js/components/ui/UiCard.vue` -- card component
- `platform/resources/js/components/ui/UiDialog.vue` -- confirmation dialog for accept/fulfill/deliver
- Wayfinder-generated `OrderController` actions (`accept`, `fulfill`, `deliver`, `cancel`) for transition URLs

## Files

- Modify `platform/app/Http/Controllers/OrderController.php` -- add `allowedTransitions` to `edit()` response
- Modify `platform/app/Models/Order.php` -- add `getAllowedTransitions(): array` returning list of status values (e.g. `['accepted', 'cancelled']`)
- Modify `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- action buttons (from `allowedTransitions`), UiDialog confirmation with notes (max 1000), status progression (no stepper for Draft), local `isTransitioning` ref, toast on error
- Create `platform/tests/Feature/Http/Controllers/OrderWorkflowUiTest.php` -- PHP feature tests: `edit()` returns correct `allowedTransitions` for each status; optionally one test that performs a transition and re-loads edit. Vue component tests for button/stepper rendering are optional.
