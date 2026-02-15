status: done

# Story 5.1: Prompt 2 -- Manual Sync Triggers

## Context

The Shopify app syncs products via webhooks (create/update/delete) and has a bulk import flow for initial setup. However, there's no way for a merchant to manually trigger a re-sync of all products or force-sync a specific product after the initial import. The `BulkImportService` handles the full product import (fetches all Shopify products via GraphQL, syncs each to Fibermade). The `ProductSyncService` handles individual product syncs. The `app.import.tsx` route orchestrates bulk imports with progress tracking via the `FibermadeConnection.initialImportProgress` field. The home page (`app._index.tsx`) shows connection status and initial import state.

## Goal

Add manual sync triggers to the home page: a "Re-sync All Products" button that re-imports all products from Shopify, and a "Sync Product" form that accepts a Shopify product URL or ID and force-syncs that single product. These give merchants a way to recover from sync issues without contacting support.

## Non-Goals

- Do not modify the BulkImportService or ProductSyncService internals
- Do not add a separate "Re-sync collections" control (re-sync all runs full `runImport()`: products then collections)
- Do not build a queue/background job system -- the existing synchronous approach in the import route is fine
- Do not add sync progress polling (the bulk re-sync uses the same pattern as initial import)

## Constraints

- The "Re-sync All Products" button should reuse the existing `BulkImportService` logic. Create a new action on the home page that triggers a bulk re-import. This is distinct from the initial import -- it should work even when `initialImportStatus` is "complete".
- The single product sync should accept either a Shopify product GID (e.g., `gid://shopify/Product/123`) or a numeric product ID. Force-sync: if a mapping already exists, call `ProductSyncService.updateProduct(product)`; otherwise call `ProductSyncService.importProduct(product)`.
- Both actions are triggered from the home page via form submissions (existing app `s-button` + `useFetcher`)
- Show success/error feedback using existing app `s-banner` components
- The re-sync all button must show a confirmation modal before proceeding (use existing app `s-modal`, same pattern as disconnect)
- Both actions require the `FibermadeConnection` to exist and be connected
- Use `intent` field in form data to distinguish actions (matching the existing disconnect pattern: `intent: "disconnect"`)

## Acceptance Criteria

- [x] Home page has a "Sync" section below the connection status card
- [x] "Re-sync All Products" button triggers a full re-import (products then collections via `BulkImportService.runImport()`)
- [x] Re-sync all is disabled or returns an error when `initialImportStatus` is `in_progress`
- [x] Confirmation modal before re-sync all ("Are you sure?" / Confirm / Cancel)
- [x] Re-sync all returns JSON (no redirect); success/error and updated counts shown on the home page (e.g. banner + loader revalidation)
- [x] Re-sync updates the same `initialImportProgress` field with fresh counts
- [x] "Sync Product" form with a text input for product ID/URL and a submit button
- [x] Single product sync accepts Shopify product GID or numeric ID
- [x] Single product sync extracts numeric ID from GID format if provided
- [x] Success feedback shown after sync completes (banner: product name for single product; counts for re-sync all)
- [x] Error feedback shown if sync fails (banner with error message)
- [x] Both actions return appropriate error if not connected
- [x] Existing disconnect action still works alongside new actions

---

## Tech Analysis

- **Action routing**: The `app._index.tsx` already uses an `intent` field to handle disconnect. Add two new intents: `"resync-all"` and `"sync-product"`. The action function switches on `intent` to route to the appropriate logic.
- **Re-sync all**: Instantiate `BulkImportService` (same as `app.import.tsx`) and call `runImport()`. Do not redirect; return JSON (e.g. `{ success: true, progress }` or `{ success: false, error }`). Update `initialImportProgress` on the `FibermadeConnection` as the run progresses. Reject or return error when `initialImportStatus === "in_progress"`. Revalidate loader after success so the page shows updated progress.
- **Single product sync**: Fetch the product by GID via Shopify Admin GraphQL (single-product query returning a shape compatible with `ShopifyProduct`). If a mapping exists for that product GID, call `ProductSyncService.updateProduct(product)`; otherwise call `ProductSyncService.importProduct(product)`.
- **Product ID parsing**: Accept input like `gid://shopify/Product/8234567890` or just `8234567890`. Extract the numeric ID and construct the GID for the GraphQL query: `gid://shopify/Product/{id}`.
- **UI**: Use existing app components: an `s-section` with heading "Sync" containing (1) re-sync all button that opens an `s-modal` for confirmation, (2) a form with a text input for product ID/URL and an `s-button` for single product sync. Use `s-banner` for success/error feedback.
- **Fetcher pattern**: Use `useFetcher()` for each action so they don't cause page navigation. Check `fetcher.data` for success/error and render the appropriate `s-banner`. Use a union action return type (e.g. per-intent success/error shapes) and narrow by `intent` when reading `fetcher.data`.

## References

- `shopify/app/routes/app._index.tsx` -- home page with existing disconnect action pattern
- `shopify/app/routes/app.import.tsx` -- bulk import orchestration (reference for BulkImportService usage)
- `shopify/app/services/sync/bulk-import.server.ts` -- full product/collection import service
- `shopify/app/services/sync/product-sync.server.ts` -- individual product sync (importProduct, updateProduct)
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient for API calls
- `shopify/app/db.server.ts` -- Prisma client for FibermadeConnection updates

## Files

- Modify `shopify/app/routes/app._index.tsx` -- add resync-all and sync-product intents to action, add sync section to UI with buttons, forms, and feedback banners
