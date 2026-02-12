status: pending

# Story 5.1: Prompt 2 -- Manual Sync Triggers

## Context

The Shopify app syncs products via webhooks (create/update/delete) and has a bulk import flow for initial setup. However, there's no way for a merchant to manually trigger a re-sync of all products or force-sync a specific product after the initial import. The `BulkImportService` handles the full product import (fetches all Shopify products via GraphQL, syncs each to Fibermade). The `ProductSyncService` handles individual product syncs. The `app.import.tsx` route orchestrates bulk imports with progress tracking via the `FibermadeConnection.initialImportProgress` field. The home page (`app._index.tsx`) shows connection status and initial import state.

## Goal

Add manual sync triggers to the home page: a "Re-sync All Products" button that re-imports all products from Shopify, and a "Sync Product" form that accepts a Shopify product URL or ID and force-syncs that single product. These give merchants a way to recover from sync issues without contacting support.

## Non-Goals

- Do not modify the BulkImportService or ProductSyncService internals
- Do not add collection re-sync (products only for now)
- Do not build a queue/background job system -- the existing synchronous approach in the import route is fine
- Do not add sync progress polling (the bulk re-sync uses the same pattern as initial import)

## Constraints

- The "Re-sync All Products" button should reuse the existing `BulkImportService` logic. Create a new action on the home page that triggers a bulk re-import. This is distinct from the initial import -- it should work even when `initialImportStatus` is "complete".
- The single product sync should accept either a Shopify product GID (e.g., `gid://shopify/Product/123`) or a numeric product ID. Use the existing `ProductSyncService.importProduct()` to sync it.
- Both actions are triggered from the home page via form submissions (Polaris `Button` + `useFetcher`)
- Show success/error feedback using Polaris `Banner` components
- The re-sync all button should show a confirmation before proceeding (use Polaris `Modal` or inline confirmation)
- Both actions require the `FibermadeConnection` to exist and be connected
- Use `intent` field in form data to distinguish actions (matching the existing disconnect pattern: `intent: "disconnect"`)

## Acceptance Criteria

- [ ] Home page has a "Sync" section below the connection status card
- [ ] "Re-sync All Products" button triggers a full product re-import
- [ ] Confirmation step before re-sync all (modal or inline "Are you sure?")
- [ ] Re-sync updates `initialImportProgress` with fresh counts
- [ ] "Sync Product" form with a text input for product ID/URL and a submit button
- [ ] Single product sync accepts Shopify product GID or numeric ID
- [ ] Single product sync extracts numeric ID from GID format if provided
- [ ] Success feedback shown after sync completes (Banner with product name or count)
- [ ] Error feedback shown if sync fails (Banner with error message)
- [ ] Both actions return appropriate error if not connected
- [ ] Existing disconnect action still works alongside new actions

---

## Tech Analysis

- **Action routing**: The `app._index.tsx` already uses an `intent` field to handle disconnect. Add two new intents: `"resync-all"` and `"sync-product"`. The action function switches on `intent` to route to the appropriate logic.
- **Re-sync all**: Instantiate `BulkImportService` (same as `app.import.tsx`) and call its import method. Update `initialImportProgress` on the `FibermadeConnection` with the results. The existing `app.import.tsx` action is a good reference for how to wire this up.
- **Single product sync**: Use the Shopify Admin GraphQL API (via `admin.graphql()` from the authenticated session) to fetch the product by ID, then pass it to `ProductSyncService.importProduct()`. The product sync service already handles creating/updating the colorway on Fibermade and creating external identifier mappings.
- **Product ID parsing**: Accept input like `gid://shopify/Product/8234567890` or just `8234567890`. Extract the numeric ID and construct the GID for the GraphQL query: `gid://shopify/Product/{id}`.
- **Polaris UI**: Use a `Card` with title "Sync" containing two sections. First section has the re-sync all button with a `Modal` confirmation. Second section has an `InlineStack` with `TextField` and `Button` for single product sync. Use `Banner` component for feedback messages.
- **Fetcher pattern**: Use `useFetcher()` for each action so they don't cause page navigation. Check `fetcher.data` for success/error states and render appropriate `Banner`.

## References

- `shopify/app/routes/app._index.tsx` -- home page with existing disconnect action pattern
- `shopify/app/routes/app.import.tsx` -- bulk import orchestration (reference for BulkImportService usage)
- `shopify/app/services/sync/BulkImportService.ts` -- full product import service
- `shopify/app/services/sync/ProductSyncService.ts` -- individual product sync
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient for API calls
- `shopify/app/db.server.ts` -- Prisma client for FibermadeConnection updates

## Files

- Modify `shopify/app/routes/app._index.tsx` -- add resync-all and sync-product intents to action, add sync section to UI with buttons, forms, and feedback banners
