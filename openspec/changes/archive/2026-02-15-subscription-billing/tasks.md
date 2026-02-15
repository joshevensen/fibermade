## 1. Database

- [x] 1.1 Create migration adding subscription_status column to accounts (enum: active, past_due, cancelled, inactive, refunded; nullable for existing Store/Buyer)
- [x] 1.2 Run Cashier migrations if not present (customers, subscriptions, subscription_items)
- [x] 1.3 Create webhook_events table (stripe_id unique, type, payload json, processed_at nullable) for idempotent webhook processing
- [x] 1.4 Create SubscriptionStatus enum class (or use string cast) and cast subscription_status on Account model
- [x] 1.5 Update Account model: add subscription_status to fillable and casts; add requiresSubscription() and hasActiveSubscription() helper methods

## 2. Backend — Stripe & Config

- [x] 2.1 Add Stripe price ID for $39/mo subscription to config/services.php or .env
- [x] 2.2 Add Stripe webhook signing secret to .env and config
- [x] 2.3 Ensure Cashier is configured to use Account as billable (Cashier's default is User; configure billable model)
- [x] 2.4 Document Stripe Customer Portal configuration in Stripe Dashboard (no-code; link in README or design)

## 3. Backend — Registration & Checkout Flow

- [x] 3.1 Create RegisterCheckoutController: validate registration form, store name/email/business_name/hashed password/promo in session
- [x] 3.2 Create Stripe Checkout Session in subscription mode with success_url and cancel_url; attach metadata (email, name, business_name, password_hash, promo_code from session/query)
- [x] 3.3 Pass optional promo query parameter from registration URL into session and into Checkout Session discounts when present
- [x] 3.4 Return Checkout Session URL in JSON response for frontend redirect
- [x] 3.5 Add POST /register/checkout route (guest); add GET register/success and register/cancel routes
- [x] 3.6 Create Form Request for registration validation (name, email unique, business_name, password, terms/privacy accepted)
- [x] 3.7 Register success page: Inertia view that shows "Account created" and suggests checking email or logging in (optional: poll for account ready)

## 4. Backend — Webhooks

- [x] 4.1 Create StripeWebhookController: verify signature, parse event, delegate by type
- [x] 4.2 Implement idempotency: store event in webhook_events by stripe_id; skip processing if processed_at already set; set processed_at after success
- [x] 4.3 Handle checkout.session.completed: create Account (type Creator, subscription_status active), create User (from metadata), link Stripe customer to Account; send welcome email; handle duplicate email (no duplicate account)
- [x] 4.4 Handle invoice.payment_succeeded: ensure account subscription_status remains active (update if needed)
- [x] 4.5 Handle invoice.payment_failed: set account subscription_status to past_due
- [x] 4.6 Handle customer.subscription.deleted: set subscription_status to cancelled or inactive based on period end
- [x] 4.7 Handle charge.refunded: if within 30-day window, set subscription_status to refunded and revoke access (e.g. soft delete or mark inactive)
- [x] 4.8 Register POST /webhooks/stripe route (no auth, CSRF exempt for Stripe)
- [x] 4.9 Create Welcome mail class and blade/markdown template; send from webhook handler after account creation

## 5. Backend — Subscription Middleware & Access

- [x] 5.1 Create EnsureActiveSubscription middleware: resolve user's account; if type is not Creator, pass through; if Creator and subscription_status not in [active, past_due, cancelled], redirect to subscription-expired or reactivation page
- [x] 5.2 Register middleware and apply to Creator route group (e.g. creator.* or all /creator/* routes)
- [x] 5.3 Share account.subscription_status (and optionally type) in Inertia shared props or per-route props for Creator layouts
- [x] 5.4 Create subscription-expired / reactivation route and simple Inertia page for locked-out Creators with CTA to reactivate (link to new Checkout Session)

## 6. Backend — Billing Portal

- [x] 6.1 Create BillingPortalController: ensure user is Creator; create Stripe Customer Portal session (Cashier or Stripe API); redirect to portal URL
- [x] 6.2 Add GET or POST route for billing/portal (authenticated, Creator only) that redirects to Stripe Customer Portal
- [x] 6.3 Add policy or middleware so Store/Buyer cannot access billing portal route (403 or redirect)

## 7. Backend — 90-Day Retention & Emails

- [x] 7.1 Create scheduled command or job: find Creator accounts with subscription_status inactive; compute days since subscription ended (Cashier subscription ends_at); send reminder email at day 7, 30, 60, 80; at day 90 hard-delete account and related data (colorways, bases, orders, integrations, users)
- [x] 7.2 Create Mailable(s) for retention reminders (day 7, 30, 60, 80) with appropriate copy
- [x] 7.3 Register schedule in console kernel or routes/console.php to run retention job daily
- [x] 7.4 Ensure inactive Creators have read-only access (middleware or policy) and cannot create/edit/delete; show reactivation banner (handled in frontend)

## 8. Frontend — Registration Flow

- [x] 8.1 Update RegisterPage.vue: form submit calls POST /register/checkout with form data; on success, redirect to returned Stripe Checkout URL (no account creation on submit)
- [x] 8.2 Read promo from route query (e.g. route().query.promo) and include in submit or let backend read from URL when creating session
- [x] 8.3 Do not add discount code input to registration form (promo only via URL)
- [x] 8.4 Add RegisterSuccessPage.vue (or reuse success route view) with message to check email and log in
- [x] 8.5 Handle validation errors from register/checkout (display on form)

## 9. Frontend — Payment-Failed Banner & Layout

- [x] 9.1 Create PaymentFailedBanner.vue: show when account.subscription_status === 'past_due'; message and "Update Payment Method" button that opens billing portal
- [x] 9.2 Include PaymentFailedBanner in Creator layout (above main content); only render for Creator accounts
- [x] 9.3 Ensure Creator layout receives account (with subscription_status) via shared props or page props

## 10. Frontend — Billing Management (Settings)

- [x] 10.1 Create BillingCard.vue: display subscription status (Active / Past due / Cancelled / Inactive); show $39/month and next billing date when active; "Manage Billing" button that triggers redirect to billing portal
- [x] 10.2 Add BillingCard above AccountForm on Creator settings page (Account tab); only show for Creator accounts
- [x] 10.3 Hide billing section for Store/Buyer accounts on settings page
- [x] 10.4 Add backend route/controller to return portal URL or redirect for "Manage Billing" (reuse BillingPortalController)

## 11. Frontend — Subscription Expired / Reactivation

- [x] 11.1 Create SubscriptionExpiredPage.vue or ReactivationPage.vue: explain access is expired; CTA to reactivate (link to endpoint that creates new Checkout Session for existing user)
- [x] 11.2 For inactive (90-day window) Creators: show reactivation banner with countdown on dashboard/settings; link to same reactivation flow
- [x] 11.3 Backend: reactivation route for logged-in Creator with inactive status creates Checkout Session and redirects to Stripe

## 12. Tests

- [x] 12.1 Feature test: POST register/checkout with valid data returns redirect URL and stores session; invalid data returns validation errors
- [x] 12.2 Feature test: register/checkout with email that already exists returns validation error
- [x] 12.3 Feature test: Stripe webhook checkout.session.completed creates Account and User and sends welcome email; duplicate event does not create second account (idempotency)
- [x] 12.4 Feature test: EnsureActiveSubscription allows Creator with active/past_due/cancelled; redirects Creator with inactive/refunded; allows Store/Buyer without subscription check
- [x] 12.5 Feature test: Billing portal route redirects Creator to Stripe; returns 403 or redirect for Store/Buyer
- [x] 12.6 Feature test: invoice.payment_failed webhook sets subscription_status to past_due; customer.subscription.deleted sets cancelled/inactive as designed
- [x] 12.7 Update AccountFactory: add states or attributes for subscription_status (e.g. subscribed, pastDue, inactive)
- [x] 12.8 Update seeders to create Creator accounts with subscription_status active where needed for local dev

## 13. Sync Command & Docs

- [x] 13.1 Create artisan command subscriptions:sync (optional): fetch subscriptions from Stripe and reconcile subscription_status on accounts for manual recovery
- [x] 13.2 Document Stripe test mode and webhook testing with Stripe CLI in README or docs
