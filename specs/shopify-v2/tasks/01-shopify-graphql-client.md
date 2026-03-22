# Task 01 — Extend ShopifyGraphqlClient for Product & Collection Queries

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/01-shopify-graphql-client.md`, then implement Task 01 in full. Work through the checklist at the bottom of the task file. Don't start the next task.



## Goal

The platform already has a `ShopifyGraphqlClient` used by `InventorySyncService`. Extend it with all the GraphQL queries needed to pull products and collections.

## What to Build

### New Methods on `ShopifyGraphqlClient`

**Products:**
```
getProducts(cursor: ?string): { products, nextCursor, hasNextPage }
getProduct(gid: string): ShopifyProduct
```

**Collections:**
```
getCollections(cursor: ?string): { collections, nextCursor, hasNextPage }
getCollectionProducts(collectionGid: string, cursor: ?string): { products, nextCursor, hasNextPage }
```

### Data Shapes Returned

**Product:**
- `gid` — `gid://shopify/Product/123`
- `title`
- `descriptionHtml`
- `status` — `ACTIVE | DRAFT | ARCHIVED`
- `handle`
- `featuredImage` — `{ url }`
- `variants` — array of:
  - `gid` — `gid://shopify/ProductVariant/456`
  - `title`
  - `price`
  - `sku`
  - `inventoryItem` — `{ gid }`
  - `inventoryQuantity`

**Collection:**
- `gid`
- `title`
- `descriptionHtml`
- `handle`

## GraphQL Queries to Write

Reference the existing TypeScript queries in:
- `shopify/app/services/sync/product-sync.server.ts`
- `shopify/app/services/sync/collection-sync.server.ts`
- `shopify/app/services/sync/bulk-import.server.ts`

Port these to PHP strings. The Admin GraphQL endpoint is:
`https://{shop}/admin/api/2025-01/graphql.json`

## Pagination Strategy

Use cursor-based pagination. Each method accepts an optional `$cursor` and returns a `$nextCursor` and `$hasNextPage`. The calling service handles the loop.

## Rate Limiting

The client should handle `429` responses with exponential backoff (already exists for inventory — follow the same pattern).

## Notes

- Page size: 50 products per request, 50 collections per request
- Collection products: 50 per request
- Keep queries minimal — only fetch fields we actually use
- The existing `ShopifyGraphqlClient` likely uses Laravel's `Http` facade — follow the same pattern

## Files Likely Affected

- `platform/app/Services/ShopifyGraphqlClient.php` (extend)
- Possibly new `platform/app/Data/ShopifyProduct.php`, `ShopifyVariant.php`, `ShopifyCollection.php` DTOs

## Tests

- Unit test each query method with mocked HTTP responses
- Test pagination cursor handling
- Test rate limit retry behavior

## Checklist

- [ ] Read existing `ShopifyGraphqlClient.php` to understand current structure and patterns
- [ ] Add `getProducts(?string $cursor)` method with pagination support
- [ ] Add `getProduct(string $gid)` method
- [ ] Add `getCollections(?string $cursor)` method
- [ ] Add `getCollectionProducts(string $collectionGid, ?string $cursor)` method
- [ ] Add `getVariantInventory(string $variantGid)` method (needed by Task 04)
- [ ] Port GraphQL query strings from TypeScript source files
- [ ] Verify rate limit / 429 retry behaviour matches existing inventory pattern
- [ ] Write tests for each method with mocked HTTP responses
- [ ] Write tests for cursor pagination
- [ ] Write tests for rate limit retry
- [ ] Run tests and confirm passing
