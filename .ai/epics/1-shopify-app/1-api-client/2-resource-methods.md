status: done

# Story 1.1: Prompt 2 -- Resource CRUD Methods

## Context

Prompt 1 created the `FibermadeClient` class with HTTP infrastructure (auth, error handling, rate limiting) and TypeScript types for API responses. The client has private `get<T>`, `post<T>`, `patch<T>`, and `delete` methods and a `healthCheck()` method. No resource-specific methods exist yet. The platform API has CRUD endpoints for: integrations, colorways, bases, collections, inventory, orders, customers, and external identifiers. This prompt adds typed methods for all of them.

## Goal

Add typed CRUD methods to `FibermadeClient` for every platform API resource. After this prompt, the Shopify app has a complete, typed client for the entire Fibermade API surface. Story 1.2 will use the integration methods immediately; later epics use the catalog, inventory, order, and customer methods.

## Non-Goals

- Do not modify the core HTTP infrastructure from Prompt 1
- Do not build any UI or routes
- Do not create Prisma models or database tables
- Do not add business logic beyond API calls (no sync logic, no data transformation)

## Constraints

- All methods must be typed with request and response interfaces -- define input types for create/update payloads and response types matching the API Resources from Epic 0
- Use the private HTTP methods from Prompt 1 (`get<T>`, `post<T>`, `patch<T>`, `delete`)
- List/index methods should accept optional query parameters for pagination (`page`, `per_page`) and filtering
- Response types should match the platform's API Resource JSON shapes (from Story 0.3)
- Add types to `fibermade-client.types.ts` -- organize by resource (e.g., `IntegrationData`, `CreateIntegrationPayload`, `ColorwayData`, etc.)
- Methods should be grouped logically in the class (integrations together, colorways together, etc.)

## Acceptance Criteria

- [ ] **Integration methods:**
  - `listIntegrations(params?)` -- GET /api/v1/integrations
  - `createIntegration(data)` -- POST /api/v1/integrations
  - `getIntegration(id)` -- GET /api/v1/integrations/{id}
  - `updateIntegration(id, data)` -- PATCH /api/v1/integrations/{id}
  - `deleteIntegration(id)` -- DELETE /api/v1/integrations/{id}
  - `getIntegrationLogs(integrationId, params?)` -- GET /api/v1/integrations/{id}/logs
- [ ] **External Identifier methods:**
  - `createExternalIdentifier(data)` -- POST /api/v1/external-identifiers
  - `lookupExternalIdentifier(params)` -- GET /api/v1/external-identifiers (filter by external_type + external_id)
- [ ] **Colorway methods:**
  - `listColorways(params?)` -- GET /api/v1/colorways
  - `createColorway(data)` -- POST /api/v1/colorways
  - `getColorway(id)` -- GET /api/v1/colorways/{id}
  - `updateColorway(id, data)` -- PATCH /api/v1/colorways/{id}
  - `deleteColorway(id)` -- DELETE /api/v1/colorways/{id}
- [ ] **Base methods:**
  - `listBases(params?)` -- GET /api/v1/bases
  - `createBase(data)` -- POST /api/v1/bases
  - `getBase(id)` -- GET /api/v1/bases/{id}
  - `updateBase(id, data)` -- PATCH /api/v1/bases/{id}
  - `deleteBase(id)` -- DELETE /api/v1/bases/{id}
- [ ] **Collection methods:**
  - `listCollections(params?)` -- GET /api/v1/collections
  - `createCollection(data)` -- POST /api/v1/collections
  - `getCollection(id)` -- GET /api/v1/collections/{id}
  - `updateCollection(id, data)` -- PATCH /api/v1/collections/{id}
  - `deleteCollection(id)` -- DELETE /api/v1/collections/{id}
- [ ] **Inventory methods:**
  - `listInventory(params?)` -- GET /api/v1/inventory
  - `createInventory(data)` -- POST /api/v1/inventory
  - `getInventory(id)` -- GET /api/v1/inventory/{id}
  - `updateInventory(id, data)` -- PATCH /api/v1/inventory/{id}
  - `deleteInventory(id)` -- DELETE /api/v1/inventory/{id}
  - `updateInventoryQuantity(id, data)` -- PATCH /api/v1/inventory/{id}/quantity
- [ ] **Order methods:**
  - `listOrders(params?)` -- GET /api/v1/orders
  - `createOrder(data)` -- POST /api/v1/orders
  - `getOrder(id)` -- GET /api/v1/orders/{id}
  - `updateOrder(id, data)` -- PATCH /api/v1/orders/{id}
  - `deleteOrder(id)` -- DELETE /api/v1/orders/{id}
- [ ] **Order Item methods:**
  - `listOrderItems(orderId, params?)` -- GET /api/v1/orders/{orderId}/items
  - `createOrderItem(orderId, data)` -- POST /api/v1/orders/{orderId}/items
  - `updateOrderItem(orderId, itemId, data)` -- PATCH /api/v1/orders/{orderId}/items/{itemId}
  - `deleteOrderItem(orderId, itemId)` -- DELETE /api/v1/orders/{orderId}/items/{itemId}
- [ ] **Customer methods:**
  - `listCustomers(params?)` -- GET /api/v1/customers
  - `createCustomer(data)` -- POST /api/v1/customers
  - `getCustomer(id)` -- GET /api/v1/customers/{id}
  - `updateCustomer(id, data)` -- PATCH /api/v1/customers/{id}
  - `deleteCustomer(id)` -- DELETE /api/v1/customers/{id}
- [ ] All response types match the platform API Resource JSON shapes
- [ ] All create/update payload types match the platform FormRequest validation rules

---

## Tech Analysis

- **Integration response shape** (from `IntegrationResource`): `{ id, type, settings, active, created_at, updated_at, logs? }`. Note: `credentials` is never returned in responses (write-only field).
- **Integration create payload** (from `StoreIntegrationRequest`): `{ type: "shopify", credentials: string, settings?: object, active: boolean }`. Note: `account_id` is set server-side by the API controller from the authenticated user.
- **Colorway response shape** (from `ColorwayResource`): `{ id, name, description, technique, colors, per_pan, status, primary_image_url, created_at, updated_at, collections?, inventories? }`.
- **Base response shape** (from `BaseResource`): `{ id, descriptor, description, code, status, weight, size, cost, retail_price, wool_percent, ... (fiber percentages), created_at, updated_at }`. Decimal fields are serialized as strings.
- **Collection response shape** (from `CollectionResource`): `{ id, name, description, status, created_at, updated_at, colorways? }`.
- **Inventory response shape** (from `InventoryResource`): `{ id, colorway_id, base_id, quantity, created_at, updated_at, colorway?, base? }`.
- **ExternalIdentifier response shape** (from `ExternalIdentifierResource`): `{ id, integration_id, identifiable_type, identifiable_id, external_type, external_id, data, created_at, updated_at }`.
- **IntegrationLog response shape** (from `IntegrationLogResource`): `{ id, integration_id, loggable_type, loggable_id, status, message, metadata, synced_at, created_at, updated_at }`.
- **Pagination format** (Laravel): `{ data: T[], links: { first, last, prev, next }, meta: { current_page, from, last_page, per_page, to, total } }`.
- **Query parameters for filtering**: The API controllers support standard Laravel query parameters. For now, define the types but keep them simple -- `page`, `per_page`, and resource-specific filters can be added as `Record<string, string | number>`.
- **Order and Customer endpoints** are expected from Story 0.6 and 0.7 (pending). Define the methods and types now based on the model schemas and FormRequest rules -- they establish the contract even if the endpoints aren't live yet.

## References

- `shopify/app/services/fibermade-client.server.ts` -- client class from Prompt 1 to extend with methods
- `shopify/app/services/fibermade-client.types.ts` -- types file from Prompt 1 to extend with resource types
- `platform/app/Http/Resources/Api/V1/IntegrationResource.php` -- Integration JSON shape
- `platform/app/Http/Resources/Api/V1/IntegrationLogResource.php` -- IntegrationLog JSON shape
- `platform/app/Http/Resources/Api/V1/ExternalIdentifierResource.php` -- ExternalIdentifier JSON shape
- `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- Colorway JSON shape
- `platform/app/Http/Resources/Api/V1/BaseResource.php` -- Base JSON shape
- `platform/app/Http/Resources/Api/V1/CollectionResource.php` -- Collection JSON shape
- `platform/app/Http/Resources/Api/V1/InventoryResource.php` -- Inventory JSON shape
- `platform/app/Http/Requests/StoreIntegrationRequest.php` -- Integration create validation
- `platform/app/Http/Requests/UpdateIntegrationRequest.php` -- Integration update validation
- `platform/app/Http/Requests/StoreColorwayRequest.php` -- Colorway create validation
- `platform/app/Http/Requests/UpdateColorwayRequest.php` -- Colorway update validation
- `platform/routes/api.php` -- all registered API routes and their patterns

## Files

- Modify `shopify/app/services/fibermade-client.server.ts` -- add CRUD methods for all resources
- Modify `shopify/app/services/fibermade-client.types.ts` -- add resource-specific request/response types
