# Epic 0: API Foundation

## Goal

Stand up a versioned, token-authenticated API layer in the platform. This is platform infrastructure -- the shopify is the first consumer, but the API is designed for all future clients (mobile/POS, admin, merchant websites).

## Current State

- **No API infrastructure exists.** No `routes/api.php`, no API controllers, no API resources, no JSON response layer.
- **Sanctum is not installed.** Auth is session-based via Fortify.
- **Strong foundation to build on:**
  - 18 Eloquent models with relationships
  - 16 authorization policies (account-scoped)
  - 40 FormRequest classes with validation rules
  - Account-based multi-tenancy throughout
  - Laravel 12 with bootstrap/app.php configuration

## What This Epic Delivers

By the end of this epic, the platform has:
- Sanctum token authentication for external clients
- A versioned API (`/api/v1/`) with consistent JSON responses and error handling
- CRUD endpoints for: Integrations, Colorways, Bases, Inventory, Orders, Customers, Collections
- API Resources for clean JSON serialization
- Tests covering auth, authorization, and CRUD operations

## What This Epic Does NOT Do

- No Shopify-specific logic (that's Epic 1+)
- No new business logic -- the API exposes existing models/policies
- No changes to existing Inertia controllers or web routes

## Stories

### Story 0.1: Install Sanctum & Configure API Authentication

Install Laravel Sanctum and configure token-based auth for API consumers. End state: an external client can authenticate with a bearer token and receive a JSON response.

- Install Sanctum via Composer
- Publish Sanctum config and run migration (personal_access_tokens table)
- Add Sanctum's API guard to `config/auth.php`
- Register API middleware in `bootstrap/app.php` (Sanctum stateless guard, rate limiting)
- Add `HasApiTokens` trait to User model
- Create a token creation mechanism (likely a service or console command for Stage 1 -- full token management UI is not needed yet)
- Verify: authenticated request returns 200, unauthenticated returns 401

### Story 0.2: API Routing & Base Controller

Set up the API routing structure and a base controller with consistent response formatting. End state: API routes resolve and return well-structured JSON.

- Create `routes/api.php` with `/v1/` prefix group
- Register API routes in `bootstrap/app.php`
- Create `ApiController` base class with:
  - Consistent JSON response helpers (success, created, error, not found)
  - Consistent error formatting (422 validation, 403 forbidden, 404 not found, 500 server error)
  - Account scoping (API requests are always scoped to the authenticated user's account)
- Configure API exception handling for JSON responses (Laravel 12 uses `bootstrap/app.php` `withExceptions`)
- Add API rate limiting configuration

### Story 0.3: API Resources

Create Laravel API Resources for JSON serialization of all models that need API endpoints. End state: each model has a clean, documented JSON representation.

- ColorwayResource (and ColorwayCollection)
- BaseResource
- CollectionResource
- InventoryResource
- OrderResource (with OrderItemResource nested)
- CustomerResource
- IntegrationResource
- ExternalIdentifierResource
- Resources should include relevant relationships when loaded (e.g., Colorway includes its Inventory records)

### Story 0.4: Catalog Endpoints (Colorways, Bases, Collections)

CRUD endpoints for catalog management. These are the first resources the shopify will need for product sync.

- `GET/POST /api/v1/colorways` -- list and create
- `GET/PATCH/DELETE /api/v1/colorways/{colorway}` -- show, update, delete
- `GET/POST /api/v1/bases` -- list and create
- `GET/PATCH/DELETE /api/v1/bases/{base}` -- show, update, delete
- `GET/POST /api/v1/collections` -- list and create
- `GET/PATCH/DELETE /api/v1/collections/{collection}` -- show, update, delete
- Reuse existing FormRequest validation where applicable
- Reuse existing Policies for authorization
- Include filtering/search support (by status, name, etc.)
- Tests for each endpoint (auth, authorization, validation, CRUD)

### Story 0.5: Inventory Endpoints

CRUD endpoints for inventory management. Critical for both product sync and inventory sync epics.

- `GET/POST /api/v1/inventory` -- list and create
- `GET/PATCH/DELETE /api/v1/inventory/{inventory}` -- show, update, delete
- `PATCH /api/v1/inventory/{inventory}/quantity` -- dedicated quantity update (mirrors existing web route)
- Filtering by colorway and/or base
- Tests

### Story 0.6: Order & OrderItem Endpoints

CRUD endpoints for orders. Needed for both wholesale management and Shopify order import.

- `GET/POST /api/v1/orders` -- list and create
- `GET/PATCH/DELETE /api/v1/orders/{order}` -- show, update, delete
- `GET/POST /api/v1/orders/{order}/items` -- list and create items
- `PATCH/DELETE /api/v1/orders/{order}/items/{item}` -- update, delete items
- Filtering by type (wholesale, retail, show), status, store
- Tests

### Story 0.7: Customer & Integration Endpoints

CRUD endpoints for customers and integrations. Integrations are needed for Epic 1 (Shopify app linking). Customers for Epic 7 (Shopify customer import).

- `GET/POST /api/v1/customers` -- list and create
- `GET/PATCH/DELETE /api/v1/customers/{customer}` -- show, update, delete
- `GET/POST /api/v1/integrations` -- list and create
- `GET/PATCH/DELETE /api/v1/integrations/{integration}` -- show, update, delete
- `GET /api/v1/integrations/{integration}/logs` -- view sync logs
- `POST /api/v1/external-identifiers` -- create mappings (used by sync services)
- `GET /api/v1/external-identifiers` -- lookup by external_type + external_id
- Tests
