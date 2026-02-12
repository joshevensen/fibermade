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

### Story 5.1: Shopify Embedded App UI (4 prompts)

Build the merchant-facing UI within the Shopify Admin embedded app.

- `1-shopify-embedded-ui/1-connection-status-cleanup.md` -- Remove demo pages, update nav, add connection status dashboard to home page
- `1-shopify-embedded-ui/2-manual-sync-triggers.md` -- Re-sync all products button and single product sync form on home page
- `1-shopify-embedded-ui/3-sync-history.md` -- New sync history page surfacing IntegrationLog data with status filtering
- `1-shopify-embedded-ui/4-settings-page.md` -- Settings page for sync preferences (auto-sync toggle, collection selection)

### Story 5.2: Transactional Emails (3 prompts)

Set up an email provider and build the mailables needed for wholesale operations.

- `2-transactional-emails/1-email-provider-base-layout.md` -- Install Resend, create shared email layout, migrate existing invite email
- `2-transactional-emails/2-wholesale-order-emails.md` -- Order confirmation, new order notification, and status update emails
- `2-transactional-emails/3-invite-notification-emails.md` -- Invite accepted notification to creator, polish existing invite email

### Story 5.3: Auth Page Polish (2 prompts)

Finalize the authentication pages for production readiness.

- `3-auth-page-polish/1-registration-polish.md` -- Polish registration page, env-based whitelist, inline errors, loading states
- `3-auth-page-polish/2-login-password-reset-verification.md` -- Polish login, forgot/reset password, email verification pages, shared auth layout

### Story 5.4: Landing Page (1 prompt)

Create a landing page so something exists at the domain for beta launch.

- `4-landing-page/1-landing-page.md` -- Polish landing page, implement email signup backend (Subscriber model), wire up form
