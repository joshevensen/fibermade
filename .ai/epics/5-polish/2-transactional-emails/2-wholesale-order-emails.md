status: pending

# Story 5.2: Prompt 2 -- Wholesale Order Emails

## Context

Prompt 1 set up the email infrastructure (Resend provider, shared layout, Mailtrap for dev). The platform has a wholesale ordering system: stores submit orders to creators. The `Order` model has `type` (wholesale), `status` (OrderStatus enum: Draft, Open, Accepted, Fulfilled, Delivered, Cancelled), and relationships to `account` (creator), `orderable` (store, via polymorphic), `orderItems`, and `creator`/`updater` (users). The `OrderController` handles status transitions via `accept()`, `fulfill()`, `deliver()`, and `cancel()` actions which call `Order::transitionTo()`. The `StoreController::store()` creates new wholesale orders. There are no email notifications for any order events. The `Store` model has an `owner_name` and `email` fields. Users have `email` and `name`.

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
- Each Mailable receives the `Order` (with eager-loaded relationships) and any context needed
- Email views extend the shared layout from Prompt 1 (`emails.layouts.transactional`)
- **Order confirmation** (to store): includes order summary (items, quantities, totals), creator business name, order date
- **New order notification** (to creator): includes store name, order summary, link to order in Fibermade (use named route `orders.edit`)
- **Status update** (to store): includes new status, order reference, creator business name, and a brief message per status (e.g., "Your order has been accepted and is being prepared")
- Dispatch emails in:
  - `StoreController::store()` (or wherever wholesale orders are created) -- send confirmation + new order notification
  - `OrderController::accept()`, `fulfill()`, `deliver()`, `cancel()` -- send status update
- Use `Mail::to($recipient)->queue($mailable)` or the Mailable's `ShouldQueue` interface
- The store's email address comes from the Store model's owner or the User associated with the store account
- The creator's email comes from the account owner (User with `role: Owner` on the creator's account)

## Acceptance Criteria

- [ ] `WholesaleOrderConfirmationMail` Mailable created with queued delivery
- [ ] `WholesaleNewOrderNotificationMail` Mailable created with queued delivery
- [ ] `WholesaleOrderStatusUpdateMail` Mailable created with queued delivery
- [ ] Blade views created for each email, extending the shared layout
- [ ] Order confirmation sent to store email when wholesale order is submitted
- [ ] New order notification sent to creator when wholesale order is submitted
- [ ] Status update sent to store when order status changes (accepted, fulfilled, delivered, cancelled)
- [ ] Order confirmation includes: items with names and quantities, subtotal/total, creator name, order date
- [ ] New order notification includes: store name, item count, total, link to order
- [ ] Status update includes: new status name, order reference, contextual message
- [ ] All emails have appropriate subjects (e.g., "Order Confirmation -- #{order_id}", "New Wholesale Order from {store_name}")
- [ ] Emails are queued, not sent synchronously
- [ ] Tests verify emails are dispatched for each event (use `Mail::fake()`)

---

## Tech Analysis

- **Finding the store email**: The `Order` has `orderable` which is a `Store`. The Store belongs to an `Account`, which has `users()`. Get the account owner: `$order->orderable->account->users()->where('role', UserRole::Owner)->first()->email`. Alternatively, if Store has a direct email field, use that.
- **Finding the creator email**: The Order belongs to an `Account` (creator). Get the owner: `$order->account->users()->where('role', UserRole::Owner)->first()->email`.
- **Dispatch points**:
  - Order creation: Look at how wholesale orders are created. The `StoreController` or the wholesale ordering flow creates orders. Find the exact controller action and add email dispatch after successful creation.
  - Status transitions: The `OrderController` has `accept()`, `fulfill()`, `deliver()`, `cancel()` methods. Add email dispatch after `transitionTo()` succeeds in each method.
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
- `platform/resources/views/emails/layouts/transactional.blade.php` -- shared layout (from Prompt 1)
- `platform/app/Http/Controllers/OrderController.php` -- status transition actions
- `platform/app/Http/Controllers/StoreController.php` -- order creation (check for wholesale order creation)
- `platform/app/Models/Order.php` -- relationships, status enum
- `platform/app/Models/Store.php` -- store email/owner data
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
- Modify controller that creates wholesale orders -- dispatch confirmation and notification emails
- Create `platform/tests/Feature/Mail/WholesaleOrderMailTest.php` -- test email dispatch for each event
