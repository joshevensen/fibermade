status: pending

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
- Use the existing `useConfirm` composable pattern for confirmation before destructive actions (cancel)
- For accept/fulfill/deliver, show a simple confirmation dialog with an optional notes textarea
- Submit transitions via `router.patch()` to the appropriate route
- Follow the existing Inertia form patterns in the codebase (see `StoreEditPage.vue` for `router.patch`/`router.post` usage)
- The status progression indicator should show: Open → Accepted → Fulfilled → Delivered as horizontal steps
- Use the existing UI component library (UiButton, UiCard, etc.)
- The controller needs to pass `allowedTransitions` to the page so the frontend knows which buttons to show

## Acceptance Criteria

- [ ] `OrderController::edit()` passes `allowedTransitions` array to the page (e.g., `['accepted', 'cancelled']` for an open order)
- [ ] `OrderEditPage.vue` shows action buttons based on `allowedTransitions`:
  - Primary action button for the forward transition (accept/fulfill/deliver) with appropriate label
  - Secondary danger button for cancel (when available)
- [ ] Clicking a primary action button shows a confirmation dialog with:
  - Confirmation message (e.g., "Accept this order?")
  - Optional notes textarea
  - Confirm and Cancel buttons
- [ ] Clicking "Cancel Order" uses the `useConfirm` composable for a destructive confirmation
- [ ] Successful transitions redirect back to the order detail page with updated status
- [ ] Status progression indicator displays as horizontal steps: Open → Accepted → Fulfilled → Delivered
  - Current step highlighted
  - Completed steps shown with a check mark or filled style
  - Future steps shown as gray/unfilled
  - Cancelled orders show a "Cancelled" badge instead of the progression
- [ ] Action buttons are disabled while a transition request is in progress (prevent double-clicks)
- [ ] No action buttons shown for delivered, cancelled, or draft orders
- [ ] Vue component tests verify button rendering based on `allowedTransitions` prop

---

## Tech Analysis

- **Passing allowed transitions from controller**: The `OrderController::edit()` method loads the order. After loading, call `$order->canTransitionTo()` for each potential next status to build an `allowedTransitions` array. Alternatively, add a method on the Order model like `getAllowedTransitions(): array` that returns valid next statuses based on the current status. Pass this as a prop to the Inertia page.
- **Confirmation dialog**: The codebase uses `useConfirm` composable for delete confirmations (see `StoreEditPage.vue`). For the workflow transitions, we need a slightly different pattern: a modal/dialog with a textarea for notes. Options:
  1. Use a simple `ref`-controlled modal component with a form inside
  2. Use `window.confirm()` for simplicity (no notes support)
  **Recommendation**: Use a ref-controlled modal approach. Create a small inline confirmation state (`showConfirmation`, `pendingTransition`, `transitionNote`) and render a modal overlay with the note textarea. This avoids creating a new reusable component while supporting the notes requirement.
- **Route URLs**: The transition routes follow the pattern `/creator/orders/{order}/accept`. Use Inertia's `router.patch()` with the constructed URL. The Ziggy route helper or manual URL construction works: `/creator/orders/${order.id}/accept`.
- **Status progression component**: Build this inline in the OrderEditPage rather than extracting to a separate component (it's specific to order workflow). Use a flexbox row of step indicators. Each step is a circle + label. CSS classes toggle based on whether the step is completed, current, or upcoming.
- **Button labels**: Use clear, action-oriented labels:
  - "Accept Order" (open → accepted)
  - "Mark as Fulfilled" (accepted → fulfilled)
  - "Mark as Delivered" (fulfilled → delivered)
  - "Cancel Order" (any → cancelled)

## References

- `platform/app/Http/Controllers/OrderController.php` -- `edit()` action to add `allowedTransitions`
- `platform/app/Models/Order.php` -- `canTransitionTo()`, transition map from Prompt 1
- `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- main page to modify
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- reference for `useConfirm` pattern and `router` usage
- `platform/resources/js/composables/useConfirm.ts` -- confirmation composable
- `platform/resources/js/components/ui/UiButton.vue` -- button component
- `platform/resources/js/components/ui/UiCard.vue` -- card component

## Files

- Modify `platform/app/Http/Controllers/OrderController.php` -- add `allowedTransitions` to `edit()` response
- Modify `platform/app/Models/Order.php` -- add `getAllowedTransitions(): array` method
- Modify `platform/resources/js/pages/creator/orders/OrderEditPage.vue` -- add action buttons, confirmation dialog, status progression indicator
- Create `platform/tests/Feature/Http/Controllers/OrderWorkflowUiTest.php` -- test that `edit()` returns correct `allowedTransitions` for each status
