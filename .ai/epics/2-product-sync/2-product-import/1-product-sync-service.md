status: pending

# Story 2.2: Prompt 1 -- ProductSyncService Core (Field Mapping & Record Creation)

## Context

Story 2.1 created mapping constants (`EXTERNAL_TYPES`, `IDENTIFIABLE_TYPES`, `METAFIELD_NAMESPACE`, `METAFIELD_KEYS`) and utility functions (`findFibermadeIdByShopifyGid`, `createMapping`, `mappingExists`, etc.) for working with ExternalIdentifier records. The `FibermadeClient` has CRUD methods for Colorways, Bases, and Inventory. The Shopify app has authenticated Admin API access via `authenticate.admin(request)` which provides a GraphQL client. No sync service or product import logic exists yet.

## Goal

Create the `ProductSyncService` that takes a Shopify product (from GraphQL) and creates the corresponding Colorway, Base(s), and Inventory record(s) in Fibermade via the API. This is the core data transformation engine. Prompt 2 adds image sync, metafield writes, and integration logging.

## Non-Goals

- Do not handle image download or upload (that's Prompt 2)
- Do not write Shopify metafields (that's Prompt 2)
- Do not create IntegrationLog entries (that's Prompt 2)
- Do not build bulk import or webhook handlers (those are Stories 2.3-2.4)
- Do not handle product updates or deletes (this prompt handles initial creation only)
- Do not modify the platform API

## Constraints

- The service is a server-side class in `shopify/app/services/sync/product-sync.server.ts`
- Accept a `FibermadeClient` and `integrationId` in the constructor (injected per-request based on the shop's connection)
- The `importProduct(shopifyProduct)` method takes a typed Shopify product object and orchestrates the full import
- Field mapping must handle missing/null values gracefully -- Shopify fields are often optional
- The Shopify product GraphQL type should be defined (or generated) -- use the Admin API 2026-04 schema
- Each Shopify variant maps to one Base + one Inventory record. A product with 3 variants creates 1 Colorway + 3 Bases + 3 Inventory records.
- If a mapping already exists (product was previously imported), skip the import and return the existing mapping. Use `mappingExists()` from Story 2.1.
- Use the mapping utilities from Story 2.1 for all ExternalIdentifier operations

## Acceptance Criteria

- [ ] `shopify/app/services/sync/product-sync.server.ts` exists with a `ProductSyncService` class
- [ ] Constructor accepts `FibermadeClient`, `integrationId: number`, and `shopDomain: string`
- [ ] `importProduct(product: ShopifyProduct): Promise<ProductSyncResult>` method that:
  1. Checks if the product is already mapped (via `mappingExists`)
  2. Maps Shopify product fields to a Colorway create payload
  3. Creates the Colorway via `client.createColorway()`
  4. Creates an ExternalIdentifier mapping the Shopify product GID to the Colorway
  5. For each variant: maps variant fields to a Base create payload, creates the Base, creates an Inventory record linking the Colorway and Base, creates ExternalIdentifier mapping the variant GID to the Inventory
  6. Returns a `ProductSyncResult` with IDs of all created records
- [ ] Field mapping -- Shopify Product → Colorway:
  - `title` → `name`
  - `descriptionHtml` → `description`
  - `status` → `status` (ACTIVE→"active", DRAFT→"idea", ARCHIVED→"retired")
  - `per_pan` defaults to `1` (not available from Shopify)
- [ ] Field mapping -- Shopify Variant → Base:
  - `title` → `descriptor` (falls back to product title if variant title is "Default Title")
  - `sku` → `code` (falls back to auto-generation if empty)
  - `price` → `retail_price`
  - Weight mapping: Shopify `weight` + `weightUnit` → `weight` enum (map grams to closest yarn weight category, or null if not determinable)
- [ ] Field mapping -- Variant → Inventory:
  - `colorway_id` from the newly created Colorway
  - `base_id` from the newly created Base
  - `quantity` defaults to `0` (actual inventory sync is Epic 9)
- [ ] `shopify/app/services/sync/types.ts` exists with TypeScript interfaces for:
  - `ShopifyProduct` -- typed product object from GraphQL response
  - `ShopifyVariant` -- typed variant object
  - `ProductSyncResult` -- result of a product import (colorwayId, bases[], inventoryRecords[], mappings[])
  - `FieldMappingResult` -- intermediate type for mapped fields
- [ ] Graceful error handling: if Base creation fails for one variant, log the error and continue with remaining variants (partial success is acceptable)

---

## Tech Analysis

- **Shopify Product GraphQL fields** (Admin API 2026-04). The key fields for product sync:
  ```graphql
  product {
    id              # GID: gid://shopify/Product/123
    title           # String
    descriptionHtml # String (HTML)
    status          # ProductStatus: ACTIVE, DRAFT, ARCHIVED
    productType     # String
    vendor          # String
    tags            # [String]
    variants(first: 100) {
      edges {
        node {
          id          # GID: gid://shopify/ProductVariant/456
          title       # String
          sku         # String (nullable)
          price       # Money (string like "29.99")
          weight      # Float
          weightUnit  # WeightUnit: GRAMS, KILOGRAMS, OUNCES, POUNDS
          barcode     # String (nullable)
        }
      }
    }
    images(first: 10) {
      edges {
        node {
          id          # GID
          url         # String
          altText     # String (nullable)
        }
      }
    }
  }
  ```
- **Status mapping**: Shopify uses `ACTIVE`/`DRAFT`/`ARCHIVED` (uppercase). Fibermade uses `active`/`idea`/`retired` (lowercase strings). Map: `ACTIVE→"active"`, `DRAFT→"idea"`, `ARCHIVED→"retired"`.
- **Weight mapping heuristic**: Shopify stores weight as a number + unit. Yarn weight is a category (Lace, Fingering, DK, Worsted, Bulky). These aren't directly convertible. Options:
  1. Don't map weight automatically -- set to `null` and let the user categorize
  2. Use a rough heuristic based on grams per skein (not reliable)
  **Recommendation:** Set `weight` to `null` for imported products. Weight categorization is domain-specific knowledge the merchant will set manually.
- **"Default Title" variants**: Shopify creates a single variant with `title: "Default Title"` for products without options. When mapping to Base `descriptor`, use the product title instead of "Default Title".
- **Colorway `per_pan`**: This is a Fibermade-specific field (number of pans in a colorway, 1-6). No Shopify equivalent. Default to `1`.
- **Base `code`**: If the variant has a SKU, use it. Otherwise, the platform auto-generates a code from the descriptor initials in the `Base::boot()` method.
- **Variant `price`**: Shopify returns price as a string (e.g., `"29.99"`). The Fibermade API accepts `retail_price` as a numeric value. Parse the string to a number.
- **Inventory quantity**: Set to `0` for imported products. Real inventory sync (reading Shopify inventory levels) is a separate epic (Epic 9). The Inventory record is needed to link the Colorway and Base.
- **ExternalIdentifier `data` field**: Store useful metadata. For products: `{ admin_url: "https://{shop}/admin/products/{numericId}", shopify_handle: product.handle }`. For variants: `{ admin_url: "https://{shop}/admin/products/{productNumericId}/variants/{variantNumericId}" }`.
- **Extracting numeric ID from GID**: Shopify GIDs are like `gid://shopify/Product/1234567890`. Extract the numeric part for admin URLs: `gid.split('/').pop()`.

## References

- `shopify/app/services/sync/constants.ts` -- EXTERNAL_TYPES, IDENTIFIABLE_TYPES constants
- `shopify/app/services/sync/mapping.server.ts` -- mapping utility functions (createMapping, mappingExists, findFibermadeIdByShopifyGid)
- `shopify/app/services/fibermade-client.server.ts` -- createColorway, createBase, createInventory methods
- `shopify/app/services/fibermade-client.types.ts` -- CreateColorwayPayload, CreateBasePayload, CreateInventoryPayload types
- `platform/app/Http/Requests/StoreColorwayRequest.php` -- required: name, per_pan, status. Optional: description, technique, colors, recipe, notes
- `platform/app/Http/Requests/StoreBaseRequest.php` -- required: descriptor, status. Optional: description, weight, code, size, cost, retail_price, fiber percentages
- `platform/app/Http/Requests/StoreInventoryRequest.php` -- required: colorway_id, base_id, quantity
- `platform/app/Models/Colorway.php` -- fillable fields, casts, enums
- `platform/app/Models/Base.php` -- fillable fields, code auto-generation logic

## Files

- Create `shopify/app/services/sync/product-sync.server.ts` -- ProductSyncService class with importProduct method
- Create `shopify/app/services/sync/types.ts` -- TypeScript interfaces for Shopify product types and sync results
