status: pending

# Story 2.6: Prompt 2 -- Collection Webhook Handlers

## Context

Prompt 1 created the `CollectionSyncService` with `importCollection()` and `importAllCollections()` methods that import Shopify collections into Fibermade and map membership via ExternalIdentifiers. Collections are imported during the bulk import flow. After the initial import, collection changes in Shopify need to propagate to Fibermade in real-time, just like product changes (Story 2.4). The webhook handler pattern from `webhooks.products.*.tsx` is established.

## Goal

Register and handle Shopify collection webhooks (`collections/create`, `collections/update`, `collections/delete`) to keep Fibermade Collections in sync with Shopify. This completes the real-time sync pipeline for the product catalog.

## Non-Goals

- Do not push Fibermade Collections to Shopify
- Do not handle `collection_listings/*` webhooks (those are for sales channels)
- Do not add conflict resolution
- Do not modify the platform API

## Constraints

- Follow the webhook handler pattern from Story 2.4 (file naming, authentication, error handling, idempotency)
- Register webhook subscriptions in `shopify.app.toml`
- Collection webhooks require the `read_products` scope (already included via `write_products`)
- The webhook payload is REST format -- use/extend the webhook adapter from Story 2.4
- Collection update must handle: field changes and membership changes (products added/removed)
- Collection delete should mark the Fibermade Collection as "retired" (status change), not delete it

## Acceptance Criteria

- [ ] `shopify.app.toml` updated with collection webhook subscriptions:
  ```toml
  [[webhooks.subscriptions]]
  topics = ["collections/create"]
  uri = "/webhooks/collections/create"

  [[webhooks.subscriptions]]
  topics = ["collections/update"]
  uri = "/webhooks/collections/update"

  [[webhooks.subscriptions]]
  topics = ["collections/delete"]
  uri = "/webhooks/collections/delete"
  ```
- [ ] **collections/create handler** (`shopify/app/routes/webhooks.collections.create.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Check if collection is already mapped (idempotency)
  4. Convert REST payload to `ShopifyCollection` format
  5. Call `collectionSyncService.importCollection()`
  6. Return 200
- [ ] **collections/update handler** (`shopify/app/routes/webhooks.collections.update.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Look up existing Collection via ExternalIdentifier
  4. If not found: treat as a create
  5. If found: update Collection fields via `client.updateCollection(id, mappedFields)`
  6. Sync membership changes: fetch current collection products from Shopify, compare with Fibermade associations, add/remove as needed
  7. Log the operation
  8. Return 200
- [ ] **collections/delete handler** (`shopify/app/routes/webhooks.collections.delete.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Look up existing Collection via ExternalIdentifier
  4. If found: update Collection status to "retired" via `client.updateCollection(id, { status: "retired" })`
  5. Log the operation
  6. Return 200
- [ ] Webhook adapter extended to handle collection REST payloads
- [ ] All handlers are idempotent and return 200 regardless of errors
- [ ] Tests for webhook adapter extension in `shopify/app/services/sync/webhook-adapter.server.test.ts` (extend existing):
  - Test `convertRestCollection` converts REST payload to `ShopifyCollection` type: numeric `id` → GID, `body_html` → `descriptionHtml`
  - Test edge cases: missing `body_html`, null fields
- [ ] Tests for collections/create handler in `shopify/app/routes/webhooks.collections.create.test.ts`:
  - Test successful import: loads FibermadeConnection, converts payload, calls `importCollection`
  - Test idempotency: collection already mapped is skipped
  - Test no FibermadeConnection: silently returns 200
- [ ] Tests for collections/update handler in `shopify/app/routes/webhooks.collections.update.test.ts`:
  - Test field update: existing Collection fields are updated via `updateCollection`
  - Test membership sync: products added/removed are reflected in Colorway associations
  - Test collection not found: treats as create
- [ ] Tests for collections/delete handler in `shopify/app/routes/webhooks.collections.delete.test.ts`:
  - Test successful delete: Collection status set to "retired", ExternalIdentifier records preserved
  - Test collection not found: silently returns 200
  - Mock `authenticate.webhook`, `db.fibermadeConnection`, `FibermadeClient`, `CollectionSyncService`

---

## Tech Analysis

- **Shopify collection REST webhook payload** (collections/create and collections/update):
  ```json
  {
    "id": 5678901234,
    "title": "Summer Collection",
    "body_html": "<p>Our summer yarn colors</p>",
    "sort_order": "best-selling",
    "published_at": "2026-01-15T10:00:00-05:00",
    "updated_at": "2026-02-09T12:00:00-05:00"
  }
  ```
  Note: The collection webhook payload does NOT include product membership. To sync membership changes on update, a separate GraphQL query is needed to fetch the collection's current products.
- **collections/delete payload**: Minimal: `{ "id": 5678901234 }`.
- **Membership sync challenge**: When a collection is updated, the webhook doesn't tell us which products were added or removed. We need to:
  1. Fetch the current products in the Shopify collection (via GraphQL)
  2. Look up which Colorways those products map to
  3. Compare against the current Colorway associations in Fibermade
  4. Add new associations, remove old ones
  This requires the platform to support association management (the gap identified in Prompt 1). If no association API exists, skip membership updates and log a warning.
- **GID conversion**: Same as product webhooks -- convert numeric `id` to `gid://shopify/Collection/${id}`.
- **Scope requirements**: Collection webhooks don't require additional scopes beyond `write_products` / `read_products`.
- **Collection update frequency**: Collection updates fire when collection metadata changes OR when products are added/removed. The same handler covers both cases.
- **Webhook adapter extension**: Add a `convertRestCollection(payload)` function to `webhook-adapter.server.ts` that normalizes the REST payload to the `ShopifyCollection` type.

## References

- `shopify/app/routes/webhooks.products.create.tsx` -- webhook handler pattern to follow
- `shopify/app/services/sync/collection-sync.server.ts` -- CollectionSyncService from Prompt 1
- `shopify/app/services/sync/webhook-adapter.server.ts` -- REST to typed adapter (from Story 2.4)
- `shopify/app/services/sync/mapping.server.ts` -- ExternalIdentifier lookup utilities
- `shopify/app/services/fibermade-client.server.ts` -- updateCollection method
- `shopify/shopify.app.toml` -- webhook subscription configuration

## Files

- Modify `shopify/shopify.app.toml` -- add collections/create, collections/update, collections/delete webhook subscriptions
- Create `shopify/app/routes/webhooks.collections.create.tsx` -- collections/create webhook handler
- Create `shopify/app/routes/webhooks.collections.create.test.ts` -- tests for collections/create handler
- Create `shopify/app/routes/webhooks.collections.update.tsx` -- collections/update webhook handler
- Create `shopify/app/routes/webhooks.collections.update.test.ts` -- tests for collections/update handler
- Create `shopify/app/routes/webhooks.collections.delete.tsx` -- collections/delete webhook handler
- Create `shopify/app/routes/webhooks.collections.delete.test.ts` -- tests for collections/delete handler
- Modify `shopify/app/services/sync/webhook-adapter.server.ts` -- add collection REST payload adapter
- Modify `shopify/app/services/sync/webhook-adapter.server.test.ts` -- add tests for collection REST conversion
- Modify `shopify/app/services/sync/collection-sync.server.ts` -- add `updateCollection()` method for handling updates
