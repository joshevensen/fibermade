status: done

# Story 5.2: Prompt 2 -- Wholesale Order Emails

## Context

Prompt 1 set up the email infrastructure (Resend provider, shared layout, Mailtrap for dev). The platform has a wholesale ordering system: stores submit orders to creators. The `Order` model has `type` (wholesale), `status` (OrderStatus enum: Draft, Open, Accepted, Fulfilled, Delivered, Cancelled), and relationships to `account` (creator), `orderable` (store, via polymorphic), `orderItems`, and `creator`/`updater` (users). The `OrderController` handles status transitions via `accept()`, `fulfill()`, `deliver()`, and `cancel()` actions which call `Order::transitionTo()`. Wholesale orders are submitted via `StoreController::submitOrder()` when a store submits a draft (status transitions from Draft to Open). There are no email notifications for any order events. The `Store` model has an `owner_name` and `email` fields. Users have `email` and `name`.

## Goal

Create Mailables and dispatch emails for wholesale order events: (1) order confirmation to the store when submitted, (2) new order notification to the creator, and (3) status update emails to the store when the order status changes. Dispatch these emails at the appropriate points in the existing controllers.

## Non-Goals

- Do not modify order creation or transition logic
- Do not add email preferences or opt-out (future feature)
- Do not add email templates for non-wholesale order types
- Do not add email for draft orders (only submitted/open orders trigger emails)

## Constraints

- Create three Mailables, all implementing `ShouldQueue`:
  1. `WholesaleOrderConfirmationMail` -- sent to the store when order is submitted
  2. `WholesaleNewOrderNotificationMail` -- sent to the creator when a store submits an order
  3. `WholesaleOrderStatusUpdateMail` -- sent to the store when status changes (accepted, fulfilled, delivered, cancelled)
- Each Mailable receives the `Order` with eager-loaded relationships: `orderable`, `account.users`, `account.creator`, `orderItems.colorway`, `orderItems.base` (required for queued delivery with SerializesModels)
- Email views extend the shared layout from Prompt 1 (`emails.layout`)
- **Order confirmation** (to store): includes order summary (items, quantities, totals), creator business name, order date
- **New order notification** (to creator): includes store name, order summary, link to order in Fibermade (use named route `orders.edit`)
- **Status update** (to store): includes new status, order reference (order ID as `#123`), creator business name, and a brief message per status (e.g., "Your order has been accepted and is being prepared")
- Dispatch emails in:
  - `StoreController::submitOrder()` -- send confirmation + new order notification when a draft is submitted (status becomes Open)
  - `OrderController::accept()`, `fulfill()`, `deliver()`, `cancel()` -- send status update (wholesale orders only; see below)
- Use `Mail::to($recipient)->queue($mailable)` or the Mailable's `ShouldQueue` interface
- **Store email**: Primary `Store.email`; fallback to account owner user (`account.users()->where('role', UserRole::Owner)->first()->email`) if Store.email is empty
- **Creator email**: Primary `Creator.email` (via `order->account->creator->email`); fallback to account owner user if Creator.email is empty
- **Status update emails**: Only send when `order.type === OrderType::Wholesale` and `order.orderable` is a Store (skip for retail/show orders)

## Acceptance Criteria

- [ ] `WholesaleOrderConfirmationMail` Mailable created with queued delivery
- [ ] `WholesaleNewOrderNotificationMail` Mailable created with queued delivery
- [ ] `WholesaleOrderStatusUpdateMail` Mailable created with queued delivery
- [ ] Blade views created for each email, extending the shared layout
- [ ] Order confirmation sent to store email when wholesale order is submitted
- [ ] New order notification sent to creator when wholesale order is submitted
- [ ] Status update sent to store when wholesale order status changes (accepted, fulfilled, delivered, cancelled); skip for non-wholesale orders
- [ ] Order confirmation includes: items with names and quantities, subtotal/total, creator name, order date
- [ ] New order notification includes: store name, item count, total, link to order
- [ ] Status update includes: new status name, order reference, contextual message
- [ ] Email subjects: Confirmation `"Order Confirmation — #123"`, New order `"New Wholesale Order from {store_name}"`, Status update `"Order #123 — {status_label}"` (e.g., "Order #123 — Accepted")
- [ ] Emails are queued, not sent synchronously
- [ ] Tests verify emails are dispatched for each event (use `Mail::fake()`); tests verify status update is not sent for non-wholesale orders
- [ ] Skip sending (and optionally log) when recipient email is missing or orderable is null; do not throw

---

## Tech Analysis

- **Finding the store email**: Primary `$order->orderable->email` (Store model); fallback `$order->orderable->account->users()->where('role', UserRole::Owner)->first()?->email` if Store.email is empty.
- **Finding the creator email**: Primary `$order->account->creator?->email`; fallback `$order->account->users()->where('role', UserRole::Owner)->first()?->email`.
- **Dispatch points**:
  - Order submission: `StoreController::submitOrder()` — after the order status is updated to Open (inside the transaction, after the update). Eager-load `orderable`, `account.users`, `account.creator`, `orderItems.colorway`, `orderItems.base` before queuing.
  - Status transitions: `OrderController::accept()`, `fulfill()`, `deliver()`, `cancel()` — add email dispatch after `transitionTo()` succeeds. Only send when `$order->type === OrderType::Wholesale` and `$order->orderable` is a Store.
- **Mailable pattern**: Follow `StoreInviteMail` as a template:
  ```php
  class WholesaleOrderConfirmationMail extends Mailable implements ShouldQueue
  {
      use Queueable, SerializesModels;

      public function __construct(public Order $order) {}

      public function envelope(): Envelope
      {
          return new Envelope(subject: "Order Confirmation — #{$this->order->id}");
      }

      public function content(): Content
      {
          return new Content(view: 'emails.wholesale.order-confirmation');
      }
  }
  ```
- **Status-specific messages**: The `WholesaleOrderStatusUpdateMail` can include a `statusMessage()` helper that maps status to a human-readable message:
  - Accepted: "Your order has been accepted and is being prepared."
  - Fulfilled: "Your order has been fulfilled and is ready for shipment."
  - Delivered: "Your order has been marked as delivered."
  - Cancelled: "Your order has been cancelled."

## References

- `platform/app/Mail/StoreInviteMail.php` -- existing Mailable pattern
- `platform/resources/views/emails/layout.blade.php` -- shared layout (from Prompt 1)
- `platform/app/Http/Controllers/OrderController.php` -- status transition actions
- `platform/app/Http/Controllers/StoreController.php` -- submitOrder() for wholesale order submission
- `platform/app/Models/Order.php` -- relationships, status enum
- `platform/app/Models/Store.php` -- store email
- `platform/app/Models/Creator.php` -- creator email (for new order notification)
- `platform/app/Enums/OrderStatus.php` -- status cases
- `platform/app/Enums/UserRole.php` -- Owner role for finding account owner

## Files

- Create `platform/app/Mail/WholesaleOrderConfirmationMail.php`
- Create `platform/app/Mail/WholesaleNewOrderNotificationMail.php`
- Create `platform/app/Mail/WholesaleOrderStatusUpdateMail.php`
- Create `platform/resources/views/emails/wholesale/order-confirmation.blade.php`
- Create `platform/resources/views/emails/wholesale/new-order-notification.blade.php`
- Create `platform/resources/views/emails/wholesale/order-status-update.blade.php`
- Modify `platform/app/Http/Controllers/OrderController.php` -- dispatch status update emails in transition actions
- Modify `platform/app/Http/Controllers/StoreController.php` -- dispatch confirmation and notification emails in submitOrder()
- Create `platform/tests/Feature/Mail/WholesaleOrderMailTest.php` -- test email dispatch for each event
