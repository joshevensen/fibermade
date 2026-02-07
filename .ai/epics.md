# Fibermade Stage 1 Epics

## Epic 0: API Foundation

Stand up the API layer in the platform that all external clients will use. This is not Shopify-specific -- it's platform infrastructure.

- Sanctum authentication setup (token creation, middleware, guards)
- API versioning structure (`/api/v1/`)
- Base API controller with consistent response formatting and error handling
- First resource endpoints: Integrations, Colorways, Bases, Inventory, Orders, Customers, Collections
- Request validation and authorization via policies
- API tests

## Epic 1: Shopify App

Get the shopify installable and connected to a Fibermade account. End state: a merchant installs the app and it creates a linked Integration record.

- Shopify OAuth flow (already partially scaffolded in shopify template)
- Account linking UX -- merchant connects their Shopify store to their Fibermade account
- Integration record creation via the Fibermade API
- Fibermade API client service in shopify (authenticated HTTP client for calling platform endpoints)
- App uninstall webhook handler (clean up Integration record)

## Epic 2: Product Sync

Bidirectional product sync between Shopify and Fibermade using metafields + ExternalIdentifier. This is the highest-value sync and the first real proof the bridge works.

- ProductSyncService: map Shopify products to Colorways, variants to Bases (via Inventory records)
- Metafield writes (`fibermade.colorway_id`, `fibermade.base_id`) for Shopify-side mapping
- ExternalIdentifier records for Fibermade-side mapping
- Fibermade → Shopify: create Shopify products from Colorways via GraphQL with metafields
- Image sync to Media table
- Collection mapping
- Initial bulk import flow (import all existing products on first connect)
- Webhook handlers: `products/create`, `products/update`, `products/delete`

## Epic 3: Wholesale Ordering (Store-Facing)

Build the store-facing wholesale experience. End state: a store can log in, browse a creator's catalog, and submit a wholesale order.

- Store authentication (login via invite)
- Catalog browsing -- stores see a creator's Colorways and Bases with wholesale pricing
- Order builder -- add items, set quantities, review order
- Order submission
- Order history and status tracking for stores

## Epic 4: Wholesale Management (Creator-Facing)

Build the creator-facing wholesale order management. End state: a creator can view incoming wholesale orders and process them.

- Enable order write operations (currently policy-blocked as read-only)
- Incoming orders dashboard (view wholesale orders from stores)
- Order detail view (line items, store info, totals)
- Order processing workflow (accept, fulfill, complete)
- Store relationship management (invites, active stores)

## Epic 5: Shopify Polish

Build the merchant-facing UI within Shopify Admin and harden the system for production use.

- Connection status dashboard (linked Fibermade account, sync health)
- Manual sync triggers (re-import products, force inventory sync)
- Sync history and logs (surface IntegrationLog data)
- Settings for sync preferences (e.g., which products/collections to sync)
- Error handling improvements (exponential backoff, retry queues)
- Rate limiting awareness for Shopify API calls

## Epic 6: Transactional Emails

Set up the email provider and build the mailables needed for launch.

- Third-party email service setup (e.g., Postmark, Resend, etc.)
- Wholesale order confirmation emails
- Wholesale order status update emails
- Store invite emails (may already exist partially)

## Epic 7: Auth Polish

Finish the auth pages so registration and password flows are production-ready.

- Finalize registration page
- Password reset flow
- Email verification flow

## Epic 8: Landing Page

Initial landing page so something exists at the domain. Primarily a "coming soon" page to start building an email list.

- Coming soon page with email signup
- Basic branding and messaging

---

**Launch to beta users after Epic 8 (5-10 personally onboarded creators)**

---

## Epic 9: Inventory Sync

Bidirectional inventory sync between Shopify and Fibermade. This is the core pain point that motivated Fibermade -- better inventory management than Shopify offers.

- InventorySyncService: push Fibermade inventory levels to Shopify variant quantities
- Pull Shopify inventory changes into Fibermade
- Conflict resolution strategy (decide which source wins)
- Webhook handler: `inventory_levels/update`
- Guard against sync loops (change from sync doesn't trigger reverse sync)

## Epic 10: Orders & Customers

Pull Shopify retail orders into Fibermade. Customers come along as a dependency of orders.

- CustomerSyncService: map Shopify customers to Fibermade Customers
- OrderSyncService: map Shopify orders to Fibermade Orders (type: retail), line items to OrderItems
- Link OrderItems to Colorway + Base combinations via ExternalIdentifiers
- ExternalIdentifier records for orders and customers
- Webhook handlers: `orders/paid`, `customers/create`, `customers/update`

## Epic 11: Marketing Website

Expand the landing page into a proper marketing website to drive signups.

- Feature overview pages
- Pricing page
- Expand email list capture

## Epic 12: Creator Onboarding

Guided onboarding flow for new creators so they can self-serve instead of being personally walked through setup.

- Welcome flow after registration
- Shopify connection prompt
- First product sync walkthrough

## Epic 13: Store Onboarding

Guided onboarding flow for stores accepting creator invites.

- Invite acceptance flow polish
- First catalog browse walkthrough

## Epic 14: Billing

Payment processing so creators can be charged for the service.

- Subscription plans and pricing (Cashier is already installed)
- Payment method collection
- Billing management page
- Trial period / free tier logic

## Epic 15: Final Polish & Launch

Harden the full system, fix issues surfaced by beta users, and prepare for public launch.

- Bug fixes and UX improvements from beta feedback
- Performance audit and optimization
- Security review (auth flows, API access, data isolation)
- Production environment hardening (error monitoring, logging, backups)
- App store listing for Shopify app 
- Launch checklist and go-live
