status: done

# Story 2.1: Prompt 1 -- Product Mapping Constants & Utilities

## Context

Epic 1 delivered a connected Shopify app with a `FibermadeClient` service that has CRUD methods for all platform API resources, including `createExternalIdentifier()` and `lookupExternalIdentifier()`. The platform's `ExternalIdentifier` model maps internal Fibermade models to external system IDs using polymorphic relationships. It has two unique constraints: `(integration_id, external_type, external_id)` to prevent duplicate external IDs, and `(integration_id, identifiable_type, identifiable_id, external_type)` to prevent multiple mappings of the same type per model. No mapping logic or constants exist in the Shopify app yet.

## Goal

Create the foundational mapping layer: constants for external identifier types and Shopify metafield keys, and utility functions that wrap the ExternalIdentifier API endpoints for easy lookup and creation. Every sync operation in Stories 2.2-2.6 will use these utilities.

## Non-Goals

- Do not build the ProductSyncService (that's Story 2.2)
- Do not create webhook handlers or bulk import logic
- Do not write any Shopify GraphQL mutations
- Do not modify the platform API or models
- Do not add UI components or routes

## Constraints

- All mapping code is server-side only (`.server.ts` convention)
- Constants should be defined as TypeScript `const` objects (not enums) for better tree-shaking and JSON compatibility
- Utility functions should accept a `FibermadeClient` instance as a parameter (dependency injection) rather than importing a singleton -- different shops have different clients/tokens
- The `identifiable_type` values must match Laravel's polymorphic type strings exactly (e.g., `App\\Models\\Colorway`, `App\\Models\\Base`, `App\\Models\\Inventory`, `App\\Models\\Collection`). These are the fully qualified PHP class names.
- Functions should be pure utilities (no side effects beyond API calls), easily testable

## Acceptance Criteria

- [ ] `shopify/app/services/sync/constants.ts` exists with:
  - `EXTERNAL_TYPES` object: `{ SHOPIFY_PRODUCT: "shopify_product", SHOPIFY_VARIANT: "shopify_variant", SHOPIFY_COLLECTION: "shopify_collection" }`
  - `IDENTIFIABLE_TYPES` object: `{ COLORWAY: "App\\Models\\Colorway", BASE: "App\\Models\\Base", INVENTORY: "App\\Models\\Inventory", COLLECTION: "App\\Models\\Collection" }`
  - `METAFIELD_NAMESPACE` constant: `"fibermade"`
  - `METAFIELD_KEYS` object: `{ COLORWAY_ID: "colorway_id", BASE_ID: "base_id" }`
- [ ] `shopify/app/services/sync/mapping.server.ts` exists with utility functions:
  - `findFibermadeIdByShopifyGid(client, integrationId, externalType, shopifyGid)` -- looks up the Fibermade model ID from a Shopify GID. Returns `{ identifiableType, identifiableId } | null`
  - `findShopifyGidByFibermadeId(client, integrationId, identifiableType, identifiableId, externalType)` -- looks up the Shopify GID from a Fibermade model. Returns `string | null`
  - `createMapping(client, integrationId, identifiableType, identifiableId, externalType, shopifyGid, data?)` -- creates an ExternalIdentifier record. Returns the created record.
  - `mappingExists(client, integrationId, externalType, shopifyGid)` -- checks if a mapping already exists. Returns `boolean`
- [ ] All functions are typed with proper return types
- [ ] All functions handle API errors gracefully (catch and re-throw with context)
- [ ] Tests in `shopify/app/services/sync/mapping.server.test.ts`:
  - Test `findFibermadeIdByShopifyGid` returns correct result when mapping exists, returns null when not found
  - Test `findShopifyGidByFibermadeId` returns GID string when mapping exists, returns null when not found
  - Test `createMapping` calls `client.createExternalIdentifier` with correct payload and returns the created record
  - Test `mappingExists` returns true when mapping exists, false when not found
  - Test error handling: functions throw with context when API call fails
  - Mock `FibermadeClient` methods using `vi.fn()`

---

## Tech Analysis

- **ExternalIdentifier API endpoints** (from Story 0.7, expected):
  - `POST /api/v1/external-identifiers` with payload: `{ integration_id, identifiable_type, identifiable_id, external_type, external_id, data? }`
  - `GET /api/v1/external-identifiers?integration_id=X&external_type=Y&external_id=Z` for lookup
- **The FibermadeClient** (from Story 1.1) has:
  - `createExternalIdentifier(data)` -- POST to create
  - `lookupExternalIdentifier(params)` -- GET with query params
  These methods return typed responses based on `ExternalIdentifierData` type.
- **Shopify GIDs** are globally unique identifiers like `gid://shopify/Product/1234567890` or `gid://shopify/ProductVariant/9876543210`. They're strings, not numbers. The `external_id` field in ExternalIdentifier stores these as strings.
- **Laravel polymorphic types** use fully qualified class names by default (e.g., `App\Models\Colorway`). In JSON, the backslash must be escaped as `\\`. The `identifiable_type` field stores these strings.
- **The `data` field** on ExternalIdentifier is JSON. Use it to store useful metadata like the Shopify admin URL for easy linking: `{ admin_url: "https://{shop}/admin/products/{id}" }`.
- **File organization**: Create a `shopify/app/services/sync/` directory for all sync-related code. This keeps sync logic separate from the core `FibermadeClient` and connection management.
- **Testing setup**: The Shopify app uses Vitest with `vi.mock()` and `vi.fn()` for mocking. Test files are co-located with source files (e.g., `mapping.server.test.ts` next to `mapping.server.ts`). Mock the `FibermadeClient` methods to test utilities in isolation without real API calls. See `fibermade-client.server.test.ts` for the established mocking pattern.

## References

- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient with createExternalIdentifier and lookupExternalIdentifier methods
- `shopify/app/services/fibermade-client.types.ts` -- ExternalIdentifierData type definition
- `platform/app/Models/ExternalIdentifier.php` -- model fields, scopes, unique constraints
- `platform/app/Http/Resources/Api/V1/ExternalIdentifierResource.php` -- API response shape: { id, integration_id, identifiable_type, identifiable_id, external_type, external_id, data, created_at, updated_at }
- `platform/app/Http/Requests/StoreExternalIdentifierRequest.php` -- validation rules for create (if it exists)

## Files

- Create `shopify/app/services/sync/constants.ts` -- external type constants, identifiable type constants, metafield namespace/keys
- Create `shopify/app/services/sync/mapping.server.ts` -- mapping utility functions wrapping ExternalIdentifier API
- Create `shopify/app/services/sync/mapping.server.test.ts` -- tests for all mapping utility functions
