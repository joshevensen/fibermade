**Sub-project**: platform

## Why

Fibermade needs a revenue model to sustain the platform and fund ongoing development. Creators using Fibermade to manage wholesale relationships and sync products with Shopify need a simple, affordable subscription plan that provides value without complicated pricing tiers.

**Important**: Only Creator accounts require subscriptions. Store and Buyer accounts have free access to place wholesale orders.

## What Changes

- Add $39/month subscription tier for Creator accounts only (Store/Buyer accounts are free)
- Integrate Stripe Checkout for payment method collection during registration
- Create Creator accounts AFTER successful Stripe Checkout (via webhook) - no abandoned accounts
- Add Stripe Customer Portal (no-code) for self-service billing management (update payment method, view invoices, cancel subscription)
- Support discount codes via URL parameter pass-through to Stripe Checkout (hidden from UI, enables marketing flexibility)
- Implement 30-day money-back guarantee with fully automatic refunds via Stripe webhooks
- Add subscription status checks (middleware) for Creator accounts only
- Add billing management page (Creators only) linking to Stripe Customer Portal
- Account model already has `Billable` trait - extend with subscription helper methods
- Implement webhook handlers for subscription lifecycle events (creation, renewal, failure, cancellation, refunds)
- Add site-wide banner for Creators when payment fails during grace period
- Welcome email sent when Creator account is created (only non-Stripe email in this change)
- 90-day data retention period for inactive accounts with email reminders before deletion

**Subscription States** (Creator accounts only):
- `active`: Paid and in good standing (full access)
- `past_due`: Payment failed, in Stripe retry period (~7-14 days grace period, full access with warning banner)
- `cancelled`: Cancelled but access until period ends (full access until billing period ends)
- `inactive`: No active subscription (limited read-only access, 90-day grace period before data deletion)
- `refunded`: Refunded within 30-day guarantee (account closed)

**Key Decisions**: 
- Only Creator accounts require subscriptions ($39/mo) - Store/Buyer accounts are free
- Accounts created after payment succeeds (no abandoned/pending accounts)
- Registration flow: Form → Session storage → Stripe Checkout → Webhook creates account
- Only track subscription status, not plan tiers
- Users inherit access from their account's subscription status
- Cancellations grant access until end of billing period
- Payment failures have ~7-14 day grace period with prominent warning banner (Creators only)
- 90-day data retention for inactive accounts with email reminder sequence
- Stripe handles all payment-related emails; Fibermade only sends welcome email and retention reminders

## Capabilities

### New Capabilities
- `subscription-management`: Managing Creator subscriptions (Store/Buyer accounts exempt), tracking subscription status, enforcing subscription requirements via middleware, 90-day data retention with email reminders
- `stripe-checkout-integration`: Registration flow using Stripe Checkout, account creation via webhook after payment success, session-based registration data storage
- `stripe-customer-portal`: Self-service billing management for Creators via Stripe's no-code customer portal (payment methods, invoices, cancellation)
- `discount-codes`: Promotional and partner discount code support via URL parameter pass-through to Stripe Checkout

### Modified Capabilities

None - this is new functionality.

## Impact

**Models**:
- `Account` model - already has `Billable` trait; extend with subscription helper methods
- New `subscription_status` field to track state (active, past_due, cancelled, inactive, refunded)
- Helper method to check if account type requires subscription (Creator only)

**Routes & Controllers**:
- New registration flow: POST /register/checkout (stores in session, creates Stripe Checkout Session)
- Success/cancel URLs for Stripe Checkout redirect
- Billing management routes for Creators only (checkout, portal redirect)
- Webhook endpoint: POST /webhooks/stripe
- Subscription status checks on Creator-authenticated routes only

**Middleware**:
- New middleware to verify active subscription status (Creator accounts only)
- Middleware bypassed for Store/Buyer account types

**UI (Creator accounts only)**:
- Registration flow: Form → Session → Redirect to Stripe Checkout → Webhook creates account → Success page
- New billing management page with link to Stripe Customer Portal
- Site-wide banner when payment fails (past_due state) with "Update Payment Method" button
- Subscription status indicators in navigation/dashboard
- Store/Buyer accounts see no billing UI

**Emails** (Fibermade-sent, not Stripe):
- Welcome email when Creator account created via webhook
- 90-day retention sequence for inactive accounts:
  - Day 7: "Your subscription has ended. Reactivate within 90 days to keep your data."
  - Day 30: "60 days left to reactivate your Fibermade subscription."
  - Day 60: "30 days left to reactivate before your data is deleted."
  - Day 80: "10 days left! Reactivate now to keep your wholesale orders and settings."
  - Day 90: Account and data hard deleted

**External Services**:
- Stripe integration (Cashier, Checkout, Customer Portal)
- Stripe test mode for local/staging environments
- Critical webhook handlers: 
  - `checkout.session.completed` (creates Creator account + user)
  - `invoice.payment_succeeded` (confirms renewal)
  - `invoice.payment_failed` (sets past_due status)
  - `customer.subscription.deleted` (sets cancelled/inactive status)
  - `charge.refunded` (handles 30-day money-back guarantee)
- Idempotent webhook processing with logging and retry mechanism

**Database**:
- Cashier migration tables (subscriptions, subscription_items, customers)
- New `subscription_status` enum field on accounts table (active, past_due, cancelled, inactive, refunded)
- Session storage for registration data before checkout completion

**Configuration**:
- Stripe API keys (test mode for local/staging, live for production)
- Stripe Customer Portal configuration in Dashboard
- Stripe webhook endpoints with signing secret
- Email templates for welcome and retention sequence

**Testing**:
- Stripe test mode with fake card numbers
- Factory/seeder updates for different subscription states
- Webhook testing with Stripe CLI for local development
