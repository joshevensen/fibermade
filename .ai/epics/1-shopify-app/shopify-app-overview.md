# Epic 1: Shopify App

## Goal

Get the Shopify bridge app installable and connected to a Fibermade account. End state: a merchant installs the app from the Shopify App Store, completes OAuth, and it creates a linked Integration record in the Fibermade platform via the API.

## Current State

- **Shopify app scaffolding exists.** The `shopify/` directory is a Shopify React Router template with OAuth, session management (Prisma + SQLite), webhook handlers (uninstall, scopes update), and embedded app shell already working.
- **No connection to Fibermade.** The app has no Fibermade API client, no account linking logic, and no custom data models beyond Session.
- **API Foundation ready (Epic 0).** The platform has Sanctum token auth, versioned API routes, CRUD endpoints for Integrations, Colorways, Bases, Inventory, Orders, Customers, Collections.
- **Integration model exists in the platform.** Stores type (shopify), encrypted credentials, settings (JSON), active flag. IntegrationLog exists for sync history.

## What This Epic Delivers

By the end of this epic:
- A Shopify merchant can install the Fibermade app
- The app authenticates with the merchant's Shopify store via OAuth (already working)
- The merchant links their Shopify store to their Fibermade account
- The app creates an Integration record in Fibermade via the API, storing the Shopify access token as credentials
- A Fibermade API client service exists in the shopify for all future API calls
- The app uninstall webhook cleans up the Integration record

## What This Epic Does NOT Do

- No product, inventory, order, or customer sync (that's Epics 2, 9, 10)
- No embedded app UI beyond the connection flow (that's Epic 5)
- No changes to the Fibermade platform API (that was Epic 0)
- No new Shopify API scopes beyond `write_products` (scopes expand in later epics as needed)

## Stories

### Story 1.1: Fibermade API Client Service

Create an HTTP client service in the shopify that communicates with the Fibermade platform API. This is the foundation for all API calls in later epics.

- Create a `FibermadeClient` service class (TypeScript)
- Authenticate with Sanctum bearer tokens
- Methods for CRUD operations: integrations, colorways, bases, inventory, orders, customers, external identifiers
- Handle rate limiting (respect `X-RateLimit-*` headers, backoff when throttled)
- Handle errors consistently (map API error responses to typed errors)
- Configure base URL from environment variables

### Story 1.2: Account Linking Flow

Build the UX for a merchant to link their Shopify store to their Fibermade account. This happens after OAuth completes.

- After Shopify OAuth, present the merchant with a linking screen in the embedded app
- Merchant enters their Fibermade email (or API token) to link accounts
- The app verifies the credentials by calling the Fibermade API
- On success, create an Integration record via `POST /api/v1/integrations` with the Shopify access token as credentials and shop domain in settings
- Store the Fibermade API token in the app's database (new Prisma model) for future API calls
- Handle edge cases: account already linked, invalid credentials, network errors

### Story 1.3: Integration Lifecycle & Webhooks

Handle the full lifecycle of the Shopify-Fibermade connection including uninstall cleanup.

- Extend the existing `app/uninstalled` webhook handler to also deactivate the Integration record via the Fibermade API (`PATCH /api/v1/integrations/{id}` with `active: false`)
- Add a connection status check on app load -- verify the Integration record exists and is active
- Handle the case where the Fibermade account is deleted or the token is revoked
- Add a disconnect/unlink option in the app settings
