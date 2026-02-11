status: complete

# Story 2.3: Prompt 1 -- Initial Bulk Import

## Context

Story 2.2 created the `ProductSyncService` with a complete `importProduct()` pipeline: field mapping, Colorway/Base/Inventory creation, ExternalIdentifier mappings, metafield writes, and integration logging. The service handles one product at a time. When a merchant first connects their Shopify store (Epic 1), their existing catalog needs to be imported into Fibermade. There's no mechanism to fetch all products from Shopify and run them through the sync service.

## Goal

Build an initial bulk import flow that fetches all existing Shopify products via GraphQL pagination and imports each one through the `ProductSyncService`. Track progress, handle errors gracefully, and mark the import as complete so it doesn't re-run. This runs once per shop, triggered after the account linking flow (Story 1.2).

## Non-Goals

- Do not use Shopify's Bulk Operations GraphQL API (individual queries are fine for Stage 1 volumes)
- Do not build a queue/job system (synchronous processing with pagination is acceptable for Stage 1)
- Do not add a detailed progress UI (a simple loading/complete state is sufficient)
- Do not handle conflict resolution (the catalog is empty on first connect, so there are no conflicts)
- Do not import collections in this story (that's Story 2.6)

## Constraints

- The import runs server-side, triggered from a React Router action
- Use GraphQL cursor-based pagination (`first: 50, after: cursor`) to fetch products in batches
- Process products sequentially within each batch (parallel processing can hit API rate limits)
- Track import state in the `FibermadeConnection` Prisma model: add an `initialImportStatus` field (values: "pending", "in_progress", "complete", "failed") and `initialImportProgress` (JSON with counts)
- The import should be resumable: if it fails partway through, re-running should skip already-imported products (the `mappingExists()` check in `ProductSyncService.importProduct()` handles this)
- Show import progress on the dashboard: total products, imported count, error count
- Set a reasonable timeout -- if a shop has hundreds of products, the import may take minutes. Use incremental fetching so the request doesn't time out.
- The import should be triggered automatically after account linking (from Story 1.2's connect action), or manually from the dashboard

## Acceptance Criteria

- [ ] New Prisma fields on `FibermadeConnection`:
  - `initialImportStatus` (String, default: "pending") -- tracks import lifecycle
  - `initialImportProgress` (String/JSON, nullable) -- stores progress: `{ total, imported, failed, errors[] }`
- [ ] Prisma migration for the new fields
- [ ] `shopify/app/services/sync/bulk-import.server.ts` exists with a `BulkImportService` class:
  - Constructor accepts `FibermadeClient`, `integrationId`, `shopDomain`, and the Shopify Admin GraphQL client
  - `runImport(): Promise<BulkImportResult>` fetches all products and imports them
  - Fetches products in batches of 50 using cursor-based pagination
  - Calls `productSyncService.importProduct()` for each product
  - Updates `FibermadeConnection.initialImportProgress` after each batch
  - Sets `initialImportStatus` to "complete" on success or "failed" on critical error
  - Returns `BulkImportResult` with total, imported, failed counts
- [ ] GraphQL query for fetching products with pagination:
  ```graphql
  products(first: 50, after: $cursor) {
    edges {
      node { id, title, descriptionHtml, status, productType, vendor, tags,
        variants(first: 100) { edges { node { id, title, sku, price, weight, weightUnit } } },
        images(first: 10) { edges { node { id, url, altText } } }
      }
      cursor
    }
    pageInfo { hasNextPage, endCursor }
  }
  ```
- [ ] Import route at `/app/import` with:
  - Action: triggers the bulk import (or resumes if failed), updates progress
  - Loader: returns current import status and progress
- [ ] Dashboard (`/app`) shows:
  - "Import your products" prompt when `initialImportStatus` is "pending"
  - Progress indicator when import is "in_progress"
  - "Import complete" state when done (with counts)
  - "Import failed" state with option to retry
- [ ] After successful import, the dashboard transitions to the connected/idle state
- [ ] The connect action (Story 1.2) sets `initialImportStatus` to "pending" when creating the connection
- [ ] Products already imported (ExternalIdentifier exists) are skipped without error
- [ ] Tests in `shopify/app/services/sync/bulk-import.server.test.ts`:
  - Test `runImport` fetches products using cursor-based pagination and processes each through `ProductSyncService.importProduct()`
  - Test pagination: handles `hasNextPage: true` with correct cursor, stops when `hasNextPage: false`
  - Test progress tracking: `initialImportProgress` is updated after each batch with correct counts
  - Test completion: `initialImportStatus` set to "complete" after all products processed
  - Test failure handling: single product failure increments failed count but does not abort the import
  - Test critical failure: GraphQL pagination error sets `initialImportStatus` to "failed"
  - Test resumability: already-imported products are skipped (via `mappingExists` in ProductSyncService)
  - Test empty store: handles zero products gracefully (status set to "complete")
  - Mock GraphQL client, `ProductSyncService`, and Prisma `FibermadeConnection` updates using `vi.mock()` and `vi.fn()`

---

## Tech Analysis

- **GraphQL pagination**: Shopify Admin API uses Relay-style cursor pagination. Query `products(first: 50, after: $cursor)`, read `pageInfo.hasNextPage` and `pageInfo.endCursor` to determine if more pages exist and what cursor to use.
- **Request timeout considerations**: A React Router action has no hard timeout, but the HTTP connection to Shopify has limits. Processing 50 products per GraphQL request, and each product requiring ~4-5 API calls to Fibermade (create colorway, create base(s), create inventory, create mapping, write metafields), a batch of 50 could take 30-60 seconds. For shops with hundreds of products, the total import could take several minutes.
  **Approach for Stage 1**: Run the import in the action handler synchronously. Use a long timeout. Update progress after each batch. If the action times out, the merchant can re-trigger it and it will resume (skipping already-imported products).
  **Future improvement**: Move to background jobs or Shopify's Bulk Operations API for large catalogs.
- **Prisma JSON field in SQLite**: SQLite doesn't have native JSON support, but Prisma handles JSON fields as strings. Store `initialImportProgress` as a JSON string. Parse it when reading: `JSON.parse(connection.initialImportProgress || '{}')`.
- **Admin GraphQL client threading**: The GraphQL client from `authenticate.admin(request)` is tied to the request. Pass it to the `BulkImportService` constructor. The service uses it for product fetching and metafield writes.
- **Error handling strategy**:
  - If a single product fails to import (API error, validation error), log the error, increment the failed count, and continue with the next product. Don't abort the entire import.
  - If the GraphQL pagination fails (Shopify API down), mark the import as "failed" with the cursor position so it can resume.
  - Store error details in `initialImportProgress.errors[]` (limit to last 50 errors to avoid bloat).
- **Import trigger timing**: The overview says "on first connect." The cleanest approach: after the Story 1.2 connect action succeeds, redirect to `/app` which shows an "Import your products" CTA. The merchant clicks to start. This gives them a chance to review before importing.
- **GraphQL rate limiting**: Shopify's Admin API uses a points-based rate limit (typically 1000 points, queries cost varies). A `products` query with nested variants and images costs roughly 50-100 points. With 1000 points and a 50-per-request batch, rate limits shouldn't be an issue. But handle 429 responses with a retry.

## References

- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService.importProduct() to call for each product
- `shopify/app/services/sync/mapping.server.ts` -- mappingExists() for skip logic
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient for API calls
- `shopify/prisma/schema.prisma` -- FibermadeConnection model to extend
- `shopify/app/routes/app._index.tsx` -- dashboard page to update with import status
- `shopify/app/routes/app.connect.tsx` -- connect action that sets initialImportStatus
- `shopify/app/routes/app._index.tsx` -- existing pattern for authenticate.admin + GraphQL usage

## Files

- Modify `shopify/prisma/schema.prisma` -- add initialImportStatus and initialImportProgress fields to FibermadeConnection
- Create `shopify/prisma/migrations/<timestamp>_add_import_tracking/migration.sql` -- via prisma migrate dev
- Create `shopify/app/services/sync/bulk-import.server.ts` -- BulkImportService class
- Create `shopify/app/services/sync/bulk-import.server.test.ts` -- tests for pagination, progress tracking, failure handling, resumability
- Create `shopify/app/routes/app.import.tsx` -- import route with action and loader
- Modify `shopify/app/routes/app._index.tsx` -- show import status/trigger on dashboard
- Modify `shopify/app/routes/app.connect.tsx` -- set initialImportStatus to "pending" on connect
