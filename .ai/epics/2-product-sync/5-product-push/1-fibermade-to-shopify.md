status: done

# Story 2.5: Prompt 1 -- Fibermade → Shopify Product Push

## Context

Stories 2.2-2.4 handle the Shopify → Fibermade direction: importing products, bulk import, and real-time webhooks. The reverse direction (Fibermade → Shopify) does not exist. A merchant who creates Colorways in the Fibermade platform has no way to push them to their Shopify store. The Shopify app has authenticated Admin API access with `write_products` scope and can execute GraphQL mutations (`productCreate`, `productVariantsBulkUpdate`). The existing `app._index.tsx` template demonstrates the `productCreate` mutation pattern.

## Goal

Build a product push flow that creates Shopify products from Fibermade Colorways using the Admin GraphQL API. A Colorway becomes a product, its associated Bases (via Inventory) become variants, and metafields + ExternalIdentifiers are written for bidirectional mapping. This is a manual action triggered from the embedded app UI (not automatic in Stage 1).

## Non-Goals

- Do not build automatic push (polling Fibermade for new Colorways) -- manual trigger only for Stage 1
- Do not push images from Fibermade to Shopify (handled in Prompt 2)
- Do not handle product updates from Fibermade to Shopify (push is create-only for Stage 1)
- Do not build a selection UI for choosing which Colorways to push (push all unpushed, or push by ID)
- Do not modify the platform API

## Constraints

- The push service is a server-side class: `shopify/app/services/sync/product-push.server.ts`
- Use the Shopify Admin GraphQL `productCreate` mutation (creates product with variants in one call)
- Include metafields in the `productCreate` mutation input (Shopify supports this) rather than writing them separately
- Create ExternalIdentifier records for the new Shopify product and each variant
- The push is triggered from a route action (`/app/push` or as an intent on the dashboard)
- The service accepts a Fibermade Colorway ID, fetches it and its Inventory/Base data from the API, maps to Shopify fields, and creates the product
- If the Colorway already has a Shopify mapping (ExternalIdentifier exists), skip it (don't create duplicates)
- Log the push operation to IntegrationLog

## Acceptance Criteria

- [ ] `shopify/app/services/sync/product-push.server.ts` exists with a `ProductPushService` class:
  - Constructor accepts `FibermadeClient`, `integrationId`, `shopDomain`, and Shopify Admin GraphQL client
  - `pushColorway(colorwayId: number): Promise<ProductPushResult>` method that:
    1. Fetches the Colorway from Fibermade via `client.getColorway(id)` (with inventories and bases loaded)
    2. Checks if a Shopify mapping already exists (skip if so)
    3. Maps Colorway fields → Shopify product input
    4. Maps each Base (via Inventory) → Shopify variant input
    5. Includes metafields in the product create input
    6. Executes `productCreate` GraphQL mutation
    7. Creates ExternalIdentifier mappings for product and each variant
    8. Logs the operation
    9. Returns `ProductPushResult` with Shopify GIDs and Fibermade IDs
- [ ] Field mapping -- Colorway → Shopify Product:
  - `name` → `title`
  - `description` → `descriptionHtml`
  - `status` → `status` ("active"→ACTIVE, "idea"→DRAFT, "retired"→ARCHIVED)
  - Metafields: `fibermade.colorway_id` = Colorway ID
- [ ] Field mapping -- Base (via Inventory) → Shopify Variant:
  - `descriptor` → variant option value (product option name: "Base")
  - `code` → `sku`
  - `retail_price` → `price`
  - Metafields: `fibermade.base_id` = Base ID
- [ ] GraphQL `productCreate` mutation:
  ```graphql
  mutation productCreate($product: ProductCreateInput!) {
    productCreate(product: $product) {
      product {
        id
        variants(first: 100) {
          edges { node { id, title, sku } }
        }
      }
      userErrors { field, message }
    }
  }
  ```
- [ ] Route action at `/app/push` (or dashboard intent):
  - Accepts `colorwayId` from form data
  - Loads FibermadeConnection for auth
  - Calls `productPushService.pushColorway(colorwayId)`
  - Returns success/failure result
- [ ] Simple UI on the dashboard:
  - "Push to Shopify" section with a text input for Colorway ID and a push button
  - Shows result (success with Shopify product link, or error message)
  - This is intentionally minimal -- Epic 5 adds a proper product selection UI
- [ ] ExternalIdentifier records created for the Shopify product and each variant
- [ ] IntegrationLog entry created for the push operation
- [ ] Tests in `shopify/app/services/sync/product-push.server.test.ts`:
  - Test `pushColorway` fetches Colorway from API, maps fields to Shopify input, and calls `productCreate` mutation
  - Test field mapping: `name` → `title`, `description` → `descriptionHtml`, status mapping (active→ACTIVE, idea→DRAFT, retired→ARCHIVED)
  - Test variant mapping: Base `descriptor` → option value, `code` → `sku`, `retail_price` → `price`
  - Test metafields included in mutation input: `fibermade.colorway_id` on product, `fibermade.base_id` on each variant
  - Test ExternalIdentifier records created for product GID and each variant GID after successful push
  - Test skip: Colorway already has Shopify mapping, returns without creating
  - Test zero Inventory: Colorway with no Bases creates product with single default variant
  - Test multi-variant: Colorway with multiple Bases creates product with multiple variants and "Base" product option
  - Test GraphQL `userErrors` returned: throws or returns error result
  - Test IntegrationLog created on success and on failure
  - Mock `FibermadeClient`, GraphQL client, and mapping utilities using `vi.mock()` and `vi.fn()`

---

## Tech Analysis

- **`productCreate` mutation with variants and metafields**: The Shopify Admin API 2026-04 supports creating a product with variants and metafields in a single mutation:
  ```graphql
  mutation productCreate($product: ProductCreateInput!) {
    productCreate(product: $product) {
      product {
        id
        title
        handle
        status
        variants(first: 100) {
          edges {
            node { id, title, sku, price }
          }
        }
      }
      userErrors { field, message }
    }
  }
  ```
  Variables:
  ```json
  {
    "product": {
      "title": "Red Merino Yarn",
      "descriptionHtml": "<p>Description</p>",
      "status": "ACTIVE",
      "productOptions": [{ "name": "Base", "values": [{ "name": "Merino Worsted" }, { "name": "Merino Fingering" }] }],
      "metafields": [
        { "namespace": "fibermade", "key": "colorway_id", "value": "42", "type": "number_integer" }
      ],
      "variants": [
        { "optionValues": [{ "optionName": "Base", "name": "Merino Worsted" }], "sku": "MW", "price": "29.99", "metafields": [{ "namespace": "fibermade", "key": "base_id", "value": "1", "type": "number_integer" }] },
        { "optionValues": [{ "optionName": "Base", "name": "Merino Fingering" }], "sku": "MF", "price": "24.99", "metafields": [{ "namespace": "fibermade", "key": "base_id", "value": "2", "type": "number_integer" }] }
      ]
    }
  }
  ```
- **Product options and variants**: Shopify products use options (e.g., "Size", "Color") to define variants. For Fibermade's product model, the natural option is "Base" -- each Base becomes a variant option value. If a Colorway has only one Base (one Inventory record), the product will have a single variant.
- **Variant metafields**: The `productCreate` mutation supports `metafields` at both the product level and the variant level. This lets us write `fibermade.base_id` on each variant in the same call.
- **Mapping variants back**: The `productCreate` response includes variant IDs in the same order as the input. Map each response variant to its corresponding Base to create ExternalIdentifier records.
- **Fetching Colorway with Inventory data**: Use `client.getColorway(id)` which returns inventories (with base data) when loaded. The API controller eager-loads `['collections', 'inventories', 'media']`. Each Inventory has `colorway_id`, `base_id`, and `quantity`. The Base data may need a separate fetch or may be nested in the Inventory resource.
- **Status mapping** (reverse of import): Fibermade `active` → Shopify `ACTIVE`, `idea` → `DRAFT`, `retired` → `ARCHIVED`.
- **Single vs multiple Bases**: A Colorway may have zero Inventory records (no Bases). In this case, create a product with a single default variant (Shopify requires at least one variant). Use the Colorway name as the variant title.
- **Admin URL for ExternalIdentifier data**: After creating the product, store the admin URL in the ExternalIdentifier data field: `{ admin_url: "https://{shop}/admin/products/{numericId}" }`.

## References

- `shopify/app/routes/app._index.tsx` -- existing productCreate mutation example (template demo)
- `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService patterns for field mapping and ExternalIdentifier creation
- `shopify/app/services/sync/constants.ts` -- EXTERNAL_TYPES, METAFIELD_NAMESPACE, METAFIELD_KEYS
- `shopify/app/services/sync/mapping.server.ts` -- createMapping, mappingExists
- `shopify/app/services/fibermade-client.server.ts` -- getColorway, createExternalIdentifier, createIntegrationLog
- `shopify/app/services/fibermade-client.types.ts` -- ColorwayData type (includes inventories, bases)
- `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- inventories and collections are conditionally loaded
- `platform/app/Http/Resources/Api/V1/InventoryResource.php` -- includes colorway_id, base_id, and conditional colorway/base resources

## Files

- Create `shopify/app/services/sync/product-push.server.ts` -- ProductPushService class
- Create `shopify/app/services/sync/product-push.server.test.ts` -- tests for pushColorway, field mapping, skip logic, variant handling
- Create `shopify/app/routes/app.push.tsx` -- route with action for triggering push, simple UI
- Modify `shopify/app/services/sync/types.ts` -- add ProductPushResult type
