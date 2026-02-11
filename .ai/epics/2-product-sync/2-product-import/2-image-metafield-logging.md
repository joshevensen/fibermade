status: done

# Story 2.2: Prompt 2 -- Image Sync, Metafield Writes & Integration Logging

## Context

Prompt 1 created the `ProductSyncService` with an `importProduct()` method that creates Colorway, Base, and Inventory records in Fibermade from a Shopify product. ExternalIdentifier mappings are created for each record. Images are not synced, metafields are not written back to Shopify, and no integration logs are created. The service returns a `ProductSyncResult` with IDs of all created records.

## Goal

Complete the product import pipeline by adding: (1) image sync from Shopify product images to the Fibermade Media system, (2) writing `fibermade.colorway_id` and `fibermade.base_id` metafields back to Shopify products/variants for Shopify-side reference, and (3) logging all sync operations to IntegrationLog for audit and debugging.

## Non-Goals

- Do not build bulk import or webhook handlers (Stories 2.3-2.4)
- Do not handle product updates or deletes (update logic is added in Story 2.4)
- Do not modify the platform API or add new endpoints
- Do not add a media upload API endpoint to the platform (store Shopify image URLs in the `metadata` field for now)
- Do not build a sync UI or progress indicator

## Constraints

- Image sync: since the platform doesn't have a media upload API endpoint yet, store the Shopify image URL in the Media `metadata` field and set `file_path` to the Shopify CDN URL. The `primary_image_url` accessor on Colorway will need to handle external URLs (or we store just the URL for now and handle proper upload in a later epic).
  **Alternatively**: If the platform has no Media API endpoint, create the media record via a new `createMedia()` method on the FibermadeClient that we need to add, or skip image sync and note it as a future enhancement. Decide based on available API endpoints.
- Metafield writes use Shopify's Admin GraphQL API `metafieldsSet` mutation
- Metafields must be written after the Colorway/Base records are created (we need the Fibermade IDs)
- Integration logs should be created via `client.createIntegrationLog()` (this method may need to be added to the FibermadeClient if not present)
- Log entries should include: integration_id, loggable_type + loggable_id (the Colorway), status (success/error), message, metadata (Shopify GID, variant count, etc.), synced_at
- All additions should integrate into the existing `importProduct()` flow, not be separate functions

## Acceptance Criteria

- [ ] **Image sync**: After creating a Colorway, download the primary product image URL from Shopify and associate it with the Colorway. For now, store the image as a reference (Shopify CDN URL in metadata) since there's no platform media upload API. Create a TODO/placeholder for proper image upload.
- [ ] **Metafield writes**: After creating Colorway and Base records, write metafields back to the Shopify product:
  - `metafieldsSet` mutation writes `fibermade.colorway_id` (integer) on the product
  - `metafieldsSet` mutation writes `fibermade.base_id` (integer) on each variant
  - Metafield type should be `number_integer`
  - Define the metafield namespace as `fibermade` (custom namespace, not `$app:` reserved)
- [ ] **Metafield GraphQL**: Create a helper function/method that writes metafields to a Shopify resource (product or variant) using the Admin GraphQL API
- [ ] **Integration logging**: After each product import:
  - On success: create log with `status: "success"`, message describing what was imported, metadata with Shopify GID and created record IDs
  - On failure: create log with `status: "error"`, message with error details, metadata with Shopify GID and partial results
  - On partial success: create log with `status: "warning"`, message noting which parts failed
- [ ] **FibermadeClient additions** (if needed):
  - Add `createIntegrationLog(data)` method if not already present (POST /api/v1/integrations/{id}/logs or similar endpoint)
- [ ] Error handling: metafield write failures should not fail the entire import. Log the error and continue. The product data is already in Fibermade -- the metafield is a convenience backreference.
- [ ] Tests in `shopify/app/services/sync/metafields.server.test.ts`:
  - Test metafield write helper calls GraphQL `metafieldsSet` mutation with correct variables (ownerId, namespace, key, value, type)
  - Test batching: product and variant metafields are written in a single mutation call
  - Test metafield write failure does not throw (logs error, continues)
  - Test metafield write with correct namespace (`fibermade`) and keys (`colorway_id`, `base_id`)
  - Mock the Shopify Admin GraphQL client using `vi.fn()`
- [ ] Tests for integration logging in `shopify/app/services/sync/product-sync.server.test.ts` (extend existing):
  - Test successful import creates a log entry with status "success" and correct metadata
  - Test failed import creates a log entry with status "error" and error details
  - Test partial success creates a log entry with status "warning"
  - Mock `client.createIntegrationLog` using `vi.fn()`

---

## Tech Analysis

- **Shopify `metafieldsSet` mutation** (Admin API 2026-04):
  ```graphql
  mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
    metafieldsSet(metafields: $metafields) {
      metafields {
        id
        namespace
        key
        value
      }
      userErrors {
        field
        message
      }
    }
  }
  ```
  Input: `{ ownerId: "gid://shopify/Product/123", namespace: "fibermade", key: "colorway_id", value: "42", type: "number_integer" }`
- **Metafield batch writes**: The `metafieldsSet` mutation accepts an array, so product and variant metafields can be written in one call. Group all metafield writes for a product into a single mutation for efficiency.
- **Shopify CDN image URLs**: Product images have URLs like `https://cdn.shopify.com/s/files/1/...`. These are publicly accessible. For Stage 1, storing the URL as a reference is acceptable. Proper image upload (downloading and re-hosting) would require a file upload endpoint on the platform.
- **Platform Media table**: The `media` table has `file_path`, `file_name`, `mime_type`, `size`, `is_primary`, `metadata` fields. For URL references, we could store:
  - `file_path`: the Shopify CDN URL (non-standard, but functional as a reference)
  - `metadata`: `{ source: "shopify", original_url: "https://cdn.shopify.com/..." }`
  - `is_primary`: true for the first image
  However, there's no API endpoint for creating Media records yet. This will need to be deferred or a platform endpoint added.
- **IntegrationLog creation**: The platform has `IntegrationLog` model with a `HasMany` relationship from `Integration`. The API endpoint structure is likely `POST /api/v1/integrations/{id}/logs`. Check if this exists in the platform routes -- if not from Story 0.7 Prompt 2, note it as a dependency.
- **Integration logging payloads**:
  ```typescript
  {
    loggable_type: "App\\Models\\Colorway",
    loggable_id: 42,
    status: "success",
    message: "Imported Shopify product 'Red Merino Yarn' as Colorway #42 with 3 variants",
    metadata: {
      shopify_gid: "gid://shopify/Product/123",
      variant_count: 3,
      bases_created: [1, 2, 3],
      inventory_created: [1, 2, 3]
    },
    synced_at: "2026-02-09T12:00:00Z"
  }
  ```
- **GraphQL client access**: The Shopify Admin GraphQL client is available via `authenticate.admin(request)` which returns `{ admin }`. The `admin.graphql()` method takes a query string and variables. This is available in route actions/loaders but needs to be passed to the sync service.
- **Metafield namespace**: Using `fibermade` as a custom namespace. Note: Shopify reserves `$app:` for app-owned metafields. Using a plain namespace like `fibermade` works but isn't protected from other apps. For production, consider using `$app:fibermade` for app-protected metafields. For Stage 1, `fibermade` is fine.

## References

- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService to extend with image, metafield, and logging support
- `shopify/app/services/sync/constants.ts` -- METAFIELD_NAMESPACE, METAFIELD_KEYS constants
- `shopify/app/services/fibermade-client.server.ts` -- may need to add createIntegrationLog method
- `shopify/app/services/fibermade-client.types.ts` -- may need IntegrationLogData types
- `shopify/app/routes/app._index.tsx` -- example of admin.graphql() usage for mutations
- `platform/app/Models/IntegrationLog.php` -- fillable: integration_id, loggable_type, loggable_id, status, message, metadata, synced_at
- `platform/app/Models/Media.php` -- fillable: mediable_type, mediable_id, file_path, file_name, mime_type, size, is_primary, metadata

## Files

- Modify `shopify/app/services/sync/product-sync.server.ts` -- add image handling, metafield writes, and integration logging to importProduct flow
- Create `shopify/app/services/sync/metafields.server.ts` -- helper for writing metafields to Shopify products/variants via GraphQL
- Create `shopify/app/services/sync/metafields.server.test.ts` -- tests for metafield write helper
- Modify `shopify/app/services/sync/product-sync.server.test.ts` -- add tests for integration logging (success, error, warning)
- Modify `shopify/app/services/fibermade-client.server.ts` -- add createIntegrationLog method if not already present
- Modify `shopify/app/services/fibermade-client.types.ts` -- add IntegrationLog types if not already present
