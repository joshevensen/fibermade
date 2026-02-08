# Epic 5: Polish

## Goal

Build the merchant-facing UI within Shopify Admin, set up transactional emails, finish auth pages, create a landing page, and harden the system for production use. This epic bundles the remaining work needed to launch to beta users. It corresponds to Epics 5-8 in the master epic list.

## Current State

- **Epics 0-4 complete.** The platform API, Shopify bridge (install + product sync), store-facing wholesale ordering, and creator-facing wholesale management all work.
- **Shopify embedded app** has a basic shell (app layout, navigation, home page) but no meaningful UI beyond the account linking flow from Epic 1. The demo pages from the template are still in place.
- **No transactional emails exist.** No email provider configured, no mailables for order events.
- **Auth pages are partially built.** Registration, login, password reset, and email verification pages exist via Fortify but may need polish for production readiness. Registration is limited to a whitelist (`kristen@badfrogyarnco.com`).
- **No landing page.** The `/` route renders a basic Inertia page (`website/HomePage`).
- **IntegrationLog model exists** but no UI surfaces sync history.

## What This Epic Delivers

By the end of this epic:
- A Shopify embedded app UI with connection status, manual sync triggers, sync history, and settings
- Transactional emails for wholesale order events (confirmation, status updates, store invites)
- Polished auth pages (registration, password reset, email verification)
- A landing page with email signup for early access
- The system is ready for beta users (5-10 personally onboarded creators)

## What This Epic Does NOT Do

- No inventory sync (Epic 9 -- post-beta)
- No order/customer import from Shopify (Epic 10 -- post-beta)
- No marketing website beyond a landing page (Epic 11)
- No guided onboarding flows (Epics 12-13)
- No billing (Epic 14)

## Stories

### Story 5.1: Shopify Embedded App UI

Build the merchant-facing UI within the Shopify Admin embedded app.

- **Connection status page:** Show linked Fibermade account, Shopify store domain, integration status (active/inactive), last sync timestamp
- **Manual sync triggers:** Button to re-import all products, button to force-sync a specific product
- **Sync history:** Surface IntegrationLog data -- recent sync operations with status (success/error/warning), message, and timestamp. Filter by status.
- **Settings:** Sync preferences (e.g., which collections to sync, auto-sync on/off). Store these in the Integration's `settings` JSON field.
- Remove the demo/template pages (`app.additional.tsx`, demo product creation)

### Story 5.2: Transactional Emails

Set up an email provider and build the mailables needed for wholesale operations.

- **Email provider setup:** Configure a third-party email service (Postmark, Resend, or similar) in the Laravel platform
- **Wholesale order confirmation:** Email to store when their order is submitted (includes order summary, creator info)
- **Wholesale order status updates:** Email to store when order status changes (accepted, fulfilled, closed, cancelled)
- **Store invite email:** Email to store when a creator invites them (may partially exist via the invite system). Include link to accept invite.
- **Creator new order notification:** Email to creator when a store submits a new wholesale order
- Use Laravel Mailables and queued delivery

### Story 5.3: Auth Page Polish

Finalize the authentication pages for production readiness.

- **Registration page:** Finalize design, remove or expand the email whitelist for beta launch, add account type selection (creator)
- **Password reset flow:** Ensure the full flow works: request reset → email with link → reset form → confirmation
- **Email verification flow:** Ensure verification email sends, verify link works, unverified users are blocked from core features
- **Login page:** Polish design, error messaging
- These pages use Inertia + Vue -- ensure they match the app's design system

### Story 5.4: Landing Page

Create a landing page so something exists at the domain for beta launch.

- Replace the current `/` route with a proper landing page
- Messaging: what Fibermade is, who it's for (yarn dyers), what problems it solves (wholesale ordering, production planning)
- Email signup form for early access / waitlist
- Basic branding and visual design
- This is a static page -- no complex interactivity needed
