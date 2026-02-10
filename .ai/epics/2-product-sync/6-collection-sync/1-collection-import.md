status: pending

# Story 2.6: Prompt 1 -- Shopify → Fibermade Collection Import

## Context

Stories 2.1-2.5 handle product sync (Colorways, Bases, Inventory) in both directions. Shopify collections group products together, and Fibermade has a Collection model with a many-to-many relationship to Colorways (via `colorway_collection` pivot table). The `FibermadeClient` has CRUD methods for Collections and ExternalIdentifier mapping utilities exist. No collection sync logic exists yet.

## Goal

Build collection import: fetch Shopify collections (custom and smart), create corresponding Fibermade Collection records, and map collection membership (which Colorways belong to which Collections) based on which Shopify products are in which collections. This runs during bulk import (Story 2.3) and will also be triggered by collection webhooks (Prompt 2).

## Non-Goals

- Do not handle collection webhooks (that's Prompt 2)
- Do not push Fibermade Collections to Shopify (one-directional for Stage 1)
- Do not handle smart collection rules/conditions (import smart collections as static membership at time of sync)
- Do not modify the platform API
- Do not add UI for collection management

## Constraints

- Create a `CollectionSyncService` in `shopify/app/services/sync/collection-sync.server.ts`
- Use GraphQL to fetch Shopify collections and their product memberships
- Map Shopify collection fields to Fibermade Collection fields: `title` → `name`, `descriptionHtml` → `description`, status defaults to "active"
- After creating a Collection, associate it with the Colorways that are mapped to the products in that collection. This requires looking up ExternalIdentifier records for each product GID.
- Create ExternalIdentifier records for collection mappings (`external_type: "shopify_collection"`)
- Collections should be imported after products (during bulk import) since collection membership depends on product mappings existing
- If a collection has no products that are mapped in Fibermade, still create the Collection (it will have no Colorway associations)
- The platform Collection API needs a way to set Colorway associations. Check if the existing API supports this (likely not -- may need a platform endpoint or a workaround).

## Acceptance Criteria

- [ ] `shopify/app/services/sync/collection-sync.server.ts` exists with a `CollectionSyncService` class:
  - Constructor accepts `FibermadeClient`, `integrationId`, `shopDomain`, and Shopify Admin GraphQL client
  - `importCollection(shopifyCollection: ShopifyCollection): Promise<CollectionSyncResult>` -- imports a single collection
  - `importAllCollections(): Promise<CollectionSyncResult[]>` -- fetches and imports all collections with pagination
- [ ] `importCollection()` method:
  1. Checks if collection is already mapped (skip if so)
  2. Maps fields: `title` → `name`, `descriptionHtml` (or `body_html`) → `description`, `status` = "active"
  3. Creates Fibermade Collection via `client.createCollection()`
  4. Creates ExternalIdentifier mapping (shopify_collection → Collection)
  5. Fetches the collection's products (product GIDs)
  6. For each product GID: looks up the Fibermade Colorway via ExternalIdentifier
  7. Associates found Colorways with the Collection (mechanism TBD based on API capabilities)
  8. Returns result with Collection ID and associated Colorway count
- [ ] GraphQL queries:
  - Fetch collections with pagination: `collections(first: 50, after: $cursor) { edges { node { id, title, descriptionHtml, sortOrder } }, pageInfo { hasNextPage, endCursor } }`
  - Fetch collection products: `collection(id: $id) { products(first: 100) { edges { node { id } }, pageInfo { hasNextPage, endCursor } } }`
- [ ] `ShopifyCollection` type defined in `shopify/app/services/sync/types.ts`
- [ ] Integration with bulk import: `BulkImportService.runImport()` calls `collectionSyncService.importAllCollections()` after product import completes
- [ ] ExternalIdentifier records created for each imported collection
- [ ] Integration logging for collection import operations

---

## Tech Analysis

- **Shopify collection types**: Shopify has two collection types: `CustomCollection` (manually curated) and `SmartCollection` (rule-based). The GraphQL API provides a unified `Collection` type that covers both. Query `collections(first: 50)` returns all collections.
- **Collection GraphQL query**:
  ```graphql
  query collections($first: Int!, $after: String) {
    collections(first: $first, after: $after) {
      edges {
        node {
          id
          title
          descriptionHtml
          sortOrder
          productsCount
          products(first: 100) {
            edges { node { id } }
            pageInfo { hasNextPage, endCursor }
          }
        }
      }
      pageInfo { hasNextPage, endCursor }
    }
  }
  ```
  Note: Nesting `products` inside the collections query may be expensive (high query cost). Consider fetching collection metadata first, then fetching products per collection separately.
- **Collection-Colorway association**: The Fibermade platform has a `colorway_collection` pivot table with a many-to-many relationship. The Collection API (Story 0.4) has standard CRUD but likely doesn't have an endpoint for managing pivot associations. Options:
  1. **If the platform has an association endpoint** (e.g., `POST /api/v1/collections/{id}/colorways`): use it
  2. **If not**: Either add a platform endpoint (breaks "no platform changes" constraint) or use a workaround -- update the Collection with a `colorway_ids` field if the UpdateCollectionRequest supports it
  3. **Pragmatic approach for Stage 1**: Note this as a gap. Create the Collection and ExternalIdentifier records. The Colorway association can be managed through the platform UI or a future API endpoint. Log a warning that associations couldn't be set via API.
  Check the `UpdateCollectionRequest` and Collection controller for association support.
- **Collection product pagination**: A collection can have many products. Use cursor-based pagination to fetch all product GIDs. For large collections (100+ products), make multiple requests.
- **Bulk import integration**: After all products are imported in `BulkImportService.runImport()`, call `collectionSyncService.importAllCollections()`. This ordering ensures product→Colorway ExternalIdentifier mappings exist when we try to look up collection memberships.
- **REST webhook payload format** (for Prompt 2): Collection webhooks use REST format. The adapter pattern from Story 2.4 (`webhook-adapter.server.ts`) should be extended for collections.

## References

- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService pattern to follow
- `shopify/app/services/sync/mapping.server.ts` -- mapping utilities for ExternalIdentifier operations
- `shopify/app/services/sync/constants.ts` -- EXTERNAL_TYPES.SHOPIFY_COLLECTION constant
- `shopify/app/services/sync/bulk-import.server.ts` -- BulkImportService to integrate with
- `shopify/app/services/fibermade-client.server.ts` -- createCollection, lookupExternalIdentifier methods
- `platform/app/Http/Controllers/Api/V1/CollectionController.php` -- Collection API controller (check for association endpoints)
- `platform/app/Http/Requests/StoreCollectionRequest.php` -- required: name, status. Optional: description
- `platform/app/Http/Requests/UpdateCollectionRequest.php` -- check for colorway_ids or association fields
- `platform/app/Models/Collection.php` -- colorways() BelongsToMany relationship

## Files

- Create `shopify/app/services/sync/collection-sync.server.ts` -- CollectionSyncService class
- Modify `shopify/app/services/sync/types.ts` -- add ShopifyCollection type and CollectionSyncResult
- Modify `shopify/app/services/sync/bulk-import.server.ts` -- call collection import after product import
