status: pending

# Story 2.4: Prompt 1 -- Shopify Product Webhook Handlers

## Context

Stories 2.2-2.3 handle importing products from Shopify into Fibermade (individual and bulk). Once imported, product changes in Shopify need to propagate to Fibermade in real-time. The Shopify app already has webhook infrastructure: `webhooks.app.uninstalled.tsx` and `webhooks.app.scopes_update.tsx` demonstrate the pattern. Webhook subscriptions are declared in `shopify.app.toml` and the app framework handles HMAC signature verification via `authenticate.webhook(request)`. No product webhooks exist yet.

## Goal

Register and handle Shopify product webhooks (`products/create`, `products/update`, `products/delete`) to keep Fibermade in sync with Shopify product changes in real-time. Created products go through the existing import pipeline, updated products sync changed fields, and deleted products are retired in Fibermade.

## Non-Goals

- Do not handle variant-level webhooks separately (product webhooks include variant data)
- Do not handle collection webhooks (that's Story 2.6)
- Do not handle inventory webhooks (that's Epic 9)
- Do not add conflict resolution (last-write-wins for Stage 1)
- Do not add a sync queue or background processing (handle webhooks synchronously)
- Do not modify the platform API

## Constraints

- Webhook routes follow the existing file naming convention: `webhooks.products.create.tsx`, `webhooks.products.update.tsx`, `webhooks.products.delete.tsx`
- Register webhook subscriptions in `shopify.app.toml` under `[webhooks]`
- All webhook handlers must be idempotent: receiving the same webhook twice must produce the same result (no duplicate records, no errors)
- Webhook handlers authenticate via `authenticate.webhook(request)` which returns `{ shop, session, topic, payload }`
- The webhook payload is a REST API payload (not GraphQL) -- field names differ from GraphQL (e.g., `body_html` not `descriptionHtml`, `id` is a numeric ID not a GID)
- The handler needs to load the `FibermadeConnection` for the shop to get the API token and integration ID
- If no `FibermadeConnection` exists for the shop (app not linked), silently ignore the webhook
- Product update must handle: field changes, new variants added, existing variants updated, variants removed
- Product delete should mark the Colorway as "retired" (status change), not delete it from Fibermade

## Acceptance Criteria

- [ ] `shopify.app.toml` updated with webhook subscriptions:
  ```toml
  [[webhooks.subscriptions]]
  topics = ["products/create"]
  uri = "/webhooks/products/create"

  [[webhooks.subscriptions]]
  topics = ["products/update"]
  uri = "/webhooks/products/update"

  [[webhooks.subscriptions]]
  topics = ["products/delete"]
  uri = "/webhooks/products/delete"
  ```
- [ ] **products/create handler** (`shopify/app/routes/webhooks.products.create.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Check if product already exists in Fibermade (idempotency)
  4. If not: convert REST payload to the `ShopifyProduct` format and call `ProductSyncService.importProduct()`
  5. Return 200 regardless of success/failure (Shopify expects 200)
- [ ] **products/update handler** (`shopify/app/routes/webhooks.products.update.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Look up existing Colorway via ExternalIdentifier (Shopify product ID → Fibermade Colorway)
  4. If not found: treat as a create (import the product)
  5. If found: update the Colorway fields via `client.updateColorway(id, mappedFields)`
  6. Sync variants: for each variant in the payload:
     - If variant has a mapping: update the Base fields
     - If variant is new (no mapping): create Base + Inventory + mapping
  7. Handle removed variants: variants in Fibermade that are no longer in the Shopify payload should have their Base status set to "retired"
  8. Update metafields for any new variants
  9. Log the sync operation
- [ ] **products/delete handler** (`shopify/app/routes/webhooks.products.delete.tsx`):
  1. Authenticate webhook
  2. Load FibermadeConnection for the shop
  3. Look up existing Colorway via ExternalIdentifier
  4. If found: update Colorway status to "retired" via `client.updateColorway(id, { status: "retired" })`
  5. Log the sync operation
  6. Do NOT delete ExternalIdentifier records (preserve history)
- [ ] **Webhook payload adapter**: Create a utility that converts the Shopify REST webhook payload to the `ShopifyProduct` TypeScript type used by `ProductSyncService`. Key differences:
  - REST `id` (numeric) vs GraphQL `id` (GID) -- convert: `gid://shopify/Product/${id}`
  - REST `body_html` vs GraphQL `descriptionHtml`
  - REST `status` is lowercase (`active`/`draft`/`archived`) vs GraphQL uppercase
  - REST `variants` is a flat array, not edges/nodes
  - REST `images` is a flat array, not edges/nodes
- [ ] All handlers return `new Response()` (200) even on errors (logged, not thrown)
- [ ] All handlers are idempotent (safe to receive duplicate webhooks)
- [ ] Tests for webhook adapter in `shopify/app/services/sync/webhook-adapter.server.test.ts`:
  - Test REST-to-GraphQL product conversion: numeric `id` → GID, `body_html` → `descriptionHtml`, lowercase `status` → uppercase, flat `variants` array → edges/nodes, flat `images` array → edges/nodes
  - Test edge cases: missing fields, null values, empty variants array
- [ ] Tests for products/create handler in `shopify/app/routes/webhooks.products.create.test.ts`:
  - Test successful import: loads FibermadeConnection, converts payload, calls `importProduct`
  - Test idempotency: product already mapped is skipped
  - Test no FibermadeConnection: silently returns 200
  - Test always returns 200 even on errors
  - Mock `authenticate.webhook`, `db.fibermadeConnection`, `ProductSyncService`, and mapping utilities
- [ ] Tests for products/update handler in `shopify/app/routes/webhooks.products.update.test.ts`:
  - Test field update: existing Colorway fields are updated via `updateColorway`
  - Test new variant: variant without mapping creates new Base + Inventory + mapping
  - Test removed variant: variant in Fibermade but not in payload marks Base as "retired"
  - Test product not found: treats as create (imports the product)
  - Test idempotency: duplicate webhook produces same result
- [ ] Tests for products/delete handler in `shopify/app/routes/webhooks.products.delete.test.ts`:
  - Test successful delete: Colorway status set to "retired", ExternalIdentifier records preserved
  - Test product not found: silently returns 200
  - Mock `authenticate.webhook`, `db.fibermadeConnection`, `FibermadeClient`

---

## Tech Analysis

- **Shopify REST webhook payload** (products/create and products/update):
  ```json
  {
    "id": 1234567890,
    "title": "Red Merino Yarn",
    "body_html": "<p>Beautiful hand-dyed yarn</p>",
    "vendor": "Fibermade",
    "product_type": "Yarn",
    "status": "active",
    "tags": "red, merino, hand-dyed",
    "variants": [
      {
        "id": 9876543210,
        "product_id": 1234567890,
        "title": "Default Title",
        "sku": "RED-MER-001",
        "price": "29.99",
        "weight": 100.0,
        "weight_unit": "g",
        "barcode": null
      }
    ],
    "images": [
      {
        "id": 111222333,
        "product_id": 1234567890,
        "src": "https://cdn.shopify.com/s/files/1/.../red-yarn.jpg",
        "alt": "Red Merino Yarn"
      }
    ]
  }
  ```
- **products/delete payload** is minimal: `{ "id": 1234567890 }`. Only the product ID is provided.
- **GID conversion**: The webhook `id` is a numeric ID. Convert to GID format: `gid://shopify/Product/${id}`. Variant IDs similarly: `gid://shopify/ProductVariant/${id}`. This is needed because ExternalIdentifier records store GIDs.
- **Existing webhook pattern** from `webhooks.app.uninstalled.tsx`:
  ```typescript
  export const action = async ({ request }: ActionFunctionArgs) => {
    const { shop, session, topic } = await authenticate.webhook(request);
    // ... handler logic
    return new Response();
  };
  ```
  The payload is available via `request.json()` or from the webhook auth result. Check the Shopify React Router docs for exact payload access.
- **Webhook payload access**: In the `@shopify/shopify-app-react-router` framework, the webhook payload may be available as `payload` from `authenticate.webhook(request)`. If not, read it via `await request.json()`.
- **Variant removal detection**: Compare the variant IDs in the webhook payload against the ExternalIdentifier records. Variants that have a mapping but aren't in the payload have been removed. Mark their corresponding Base as "retired" and/or the Inventory quantity as 0.
- **Idempotency**: The `products/create` handler should check `mappingExists()` before importing. The `products/update` handler naturally handles duplicate delivery since it updates (idempotent) rather than creates.
- **`shopify.app.toml` structure**: Webhook subscriptions need to be added alongside the existing `app/uninstalled` and `app/scopes_update` subscriptions.
- **Request scope requirement**: Adding `products/create`, `products/update`, and `products/delete` webhooks requires the `read_products` scope (in addition to the existing `write_products`). Since `write_products` implies `read_products`, no scope change is needed.

## References

- `shopify/app/routes/webhooks.app.uninstalled.tsx` -- existing webhook handler pattern
- `shopify/shopify.app.toml` -- webhook subscription configuration
- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService.importProduct()
- `shopify/app/services/sync/mapping.server.ts` -- findFibermadeIdByShopifyGid, mappingExists, createMapping
- `shopify/app/services/sync/types.ts` -- ShopifyProduct type (may need adapter from REST format)
- `shopify/app/services/fibermade-client.server.ts` -- updateColorway, updateBase methods
- `shopify/app/db.server.ts` -- Prisma client for loading FibermadeConnection
- `shopify/prisma/schema.prisma` -- FibermadeConnection model

## Files

- Modify `shopify/shopify.app.toml` -- add products/create, products/update, products/delete webhook subscriptions
- Create `shopify/app/routes/webhooks.products.create.tsx` -- products/create webhook handler
- Create `shopify/app/routes/webhooks.products.update.tsx` -- products/update webhook handler
- Create `shopify/app/routes/webhooks.products.delete.tsx` -- products/delete webhook handler
- Create `shopify/app/services/sync/webhook-adapter.server.ts` -- converts REST webhook payloads to ShopifyProduct type
- Create `shopify/app/services/sync/webhook-adapter.server.test.ts` -- tests for REST-to-GraphQL payload conversion
- Create `shopify/app/routes/webhooks.products.create.test.ts` -- tests for products/create webhook handler
- Create `shopify/app/routes/webhooks.products.update.test.ts` -- tests for products/update webhook handler
- Create `shopify/app/routes/webhooks.products.delete.test.ts` -- tests for products/delete webhook handler
- Modify `shopify/app/services/sync/product-sync.server.ts` -- add `updateProduct()` method for handling product updates (field changes, variant add/update/remove)
