status: done

# Story 2.6: Prompt 1 -- Shopify → Fibermade Collection Import

## Context

Stories 2.1-2.5 handle product sync (Colorways, Bases, Inventory) in both directions. Shopify collections group products together, and Fibermade has a Collection model with a many-to-many relationship to Colorways (via `colorway_collection` pivot table). The `FibermadeClient` has CRUD methods for Collections and ExternalIdentifier mapping utilities exist. No collection sync logic exists yet.

## Goal

Build collection import: fetch Shopify collections (custom and smart), create corresponding Fibermade Collection records, and map collection membership (which Colorways belong to which Collections) based on which Shopify products are in which collections. This runs during bulk import (Story 2.3) and will also be triggered by collection webhooks (Prompt 2).

## Non-Goals

- Do not handle collection webhooks (that's Prompt 2)
- Do not push Fibermade Collections to Shopify (one-directional for Stage 1)
- Do not handle smart collection rules/conditions (import smart collections as static membership at time of sync)
- Do not add UI for collection management

## Constraints

- Create a `CollectionSyncService` in `shopify/app/services/sync/collection-sync.server.ts`
- Use GraphQL to fetch Shopify collections and their product memberships
- Map Shopify collection fields to Fibermade Collection fields: `title` → `name`, `descriptionHtml` → `description`, status always set to "active"
- Fetch collection metadata first, then fetch products per collection separately to avoid high GraphQL query costs
- After creating a Collection, associate it with the Colorways that are mapped to the products in that collection via API endpoint `POST /api/v1/collections/{id}/colorways` with `{ colorway_ids: number[] }`
- Create ExternalIdentifier records for collection mappings (`external_type: "shopify_collection"`) with collection handle stored in `data` metadata
- Collections should be imported after products (during bulk import) since collection membership depends on product mappings existing
- If a collection has no products that are mapped in Fibermade, still create the Collection (it will have no Colorway associations) and log a warning/metric for tracking sync completeness
- Add rate limiting retries (similar to `BulkImportService`) for GraphQL requests, especially when fetching products per collection

## Acceptance Criteria

- [ ] `shopify/app/services/sync/collection-sync.server.ts` exists with a `CollectionSyncService` class:
  - Constructor accepts `FibermadeClient`, `integrationId`, `shopDomain`, and Shopify Admin GraphQL client
  - `importCollection(shopifyCollection: ShopifyCollection): Promise<CollectionSyncResult>` -- imports a single collection
  - `importAllCollections(): Promise<CollectionSyncResult[]>` -- fetches and imports all collections with pagination
- [ ] `importCollection()` method:
  1. Checks if collection is already mapped (skip if so)
  2. Maps fields: `title` → `name`, `descriptionHtml` → `description`, `status` = "active"
  3. Creates Fibermade Collection via `client.createCollection()`
  4. Creates ExternalIdentifier mapping (shopify_collection → Collection) with collection handle stored in `data` metadata
  5. Fetches the collection's products (product GIDs) with pagination and rate limiting retries
  6. For each product GID: looks up the Fibermade Colorway via ExternalIdentifier
  7. Associates found Colorways with the Collection via `POST /api/v1/collections/{id}/colorways` endpoint
  8. If no products are mapped to Colorways, logs a warning/metric for tracking sync completeness
  9. Returns result with Collection ID and associated Colorway count
- [ ] GraphQL queries (two-step approach):
  - Step 1: Fetch collections metadata with pagination: `collections(first: 50, after: $cursor) { edges { node { id, title, descriptionHtml, handle } }, pageInfo { hasNextPage, endCursor } }`
  - Step 2: For each collection, fetch products separately: `collection(id: $id) { products(first: 100, after: $cursor) { edges { node { id } }, pageInfo { hasNextPage, endCursor } } }`
  - Both queries include rate limiting retries on 429 errors with exponential backoff
- [ ] `ShopifyCollection` type defined in `shopify/app/services/sync/types.ts`
- [ ] Integration with bulk import: `BulkImportService.runImport()` calls `collectionSyncService.importAllCollections()` after product import completes
- [ ] ExternalIdentifier records created for each imported collection
- [ ] Integration logging for collection import operations
- [ ] Tests in `shopify/app/services/sync/collection-sync.server.test.ts`:
  - Test `importCollection` creates Collection, ExternalIdentifier mapping (with handle in metadata), and associates Colorways via API endpoint
  - Test field mapping: `title` → `name`, `descriptionHtml` → `description`, status always "active"
  - Test skip: collection already mapped is skipped
  - Test membership: collection products are looked up via ExternalIdentifier and associated with Collection via API endpoint
  - Test collection with no mapped products: Collection created but with no Colorway associations, warning logged
  - Test `importAllCollections` paginates through all collections and imports each one
  - Test rate limiting: retries on 429 errors with exponential backoff
  - Test integration logging on success and failure
  - Mock `FibermadeClient`, GraphQL client, and mapping utilities using `vi.mock()` and `vi.fn()`

---

## Tech Analysis

- **Shopify collection types**: Shopify has two collection types: `CustomCollection` (manually curated) and `SmartCollection` (rule-based). The GraphQL API provides a unified `Collection` type that covers both. Query `collections(first: 50)` returns all collections.
- **Collection GraphQL query strategy**: Use a two-step approach to avoid high query costs:
  1. **Step 1**: Fetch collection metadata only (without products):
     ```graphql
     query collections($first: Int!, $after: String) {
       collections(first: $first, after: $after) {
         edges {
           node {
             id
             title
             descriptionHtml
             handle
           }
         }
         pageInfo { hasNextPage, endCursor }
       }
     }
     ```
  2. **Step 2**: For each collection, fetch products separately with pagination:
     ```graphql
     query collectionProducts($id: ID!, $first: Int!, $after: String) {
       collection(id: $id) {
         products(first: $first, after: $after) {
           edges { node { id } }
           pageInfo { hasNextPage, endCursor }
         }
       }
     }
     ```
  This approach avoids expensive nested queries and allows better rate limiting control.
- **Collection-Colorway association**: The Fibermade platform has a `colorway_collection` pivot table with a many-to-many relationship. Add API endpoint `POST /api/v1/collections/{id}/colorways` that accepts `{ colorway_ids: number[] }` and uses Laravel's `sync()` method to associate colorways. This endpoint will be added to the platform API as part of this story.
- **Collection product pagination**: A collection can have many products. Use cursor-based pagination to fetch all product GIDs. For large collections (100+ products), make multiple requests with rate limiting retries.
- **Rate limiting**: Add rate limiting retries similar to `BulkImportService`: retry on 429 errors with exponential backoff (1000ms, 2000ms, 3000ms delays). Apply to both collection metadata queries and per-collection product queries.
- **Collection handle storage**: Store the Shopify collection `handle` field in the ExternalIdentifier `data` metadata (similar to how product handles are stored) for future reference and potential URL generation.
- **Bulk import integration**: After all products are imported in `BulkImportService.runImport()`, call `collectionSyncService.importAllCollections()`. This ordering ensures product→Colorway ExternalIdentifier mappings exist when we try to look up collection memberships.
- **REST webhook payload format** (for Prompt 2): Collection webhooks use REST format. The adapter pattern from Story 2.4 (`webhook-adapter.server.ts`) should be extended for collections.

## References

- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService pattern to follow
- `shopify/app/services/sync/mapping.server.ts` -- mapping utilities for ExternalIdentifier operations
- `shopify/app/services/sync/constants.ts` -- EXTERNAL_TYPES.SHOPIFY_COLLECTION constant
- `shopify/app/services/sync/bulk-import.server.ts` -- BulkImportService to integrate with
- `shopify/app/services/fibermade-client.server.ts` -- createCollection, lookupExternalIdentifier methods
- `platform/app/Http/Controllers/Api/V1/CollectionController.php` -- Collection API controller (add `updateColorways` endpoint)
- `platform/app/Http/Requests/StoreCollectionRequest.php` -- required: name, status. Optional: description
- `platform/app/Http/Requests/UpdateCollectionRequest.php` -- standard update fields
- `platform/app/Http/Requests/UpdateCollectionColorwaysRequest.php` -- request validation for colorway associations (create if needed)
- `platform/app/Models/Collection.php` -- colorways() BelongsToMany relationship

## Files

- Create `shopify/app/services/sync/collection-sync.server.ts` -- CollectionSyncService class
- Create `shopify/app/services/sync/collection-sync.server.test.ts` -- tests for importCollection, importAllCollections, membership mapping
- Modify `shopify/app/services/sync/types.ts` -- add ShopifyCollection type and CollectionSyncResult
- Modify `shopify/app/services/sync/bulk-import.server.ts` -- call collection import after product import
- Modify `shopify/app/services/fibermade-client.server.ts` -- add `updateCollectionColorways(id: number, colorwayIds: number[]): Promise<void>` method
- Modify `platform/app/Http/Controllers/Api/V1/CollectionController.php` -- add `updateColorways` method
- Create `platform/app/Http/Requests/UpdateCollectionColorwaysRequest.php` -- validation for colorway_ids array (if doesn't exist)
