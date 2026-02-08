status: pending

# Story 0.5: Prompt 1 -- Inventory CRUD & Quantity Update Endpoints

## Context

Story 0.4 created API controllers for Colorways, Bases, and Collections following the established pattern: extend `ApiController`, use `scopeToAccount()`, reuse FormRequests, return via API Resources, register with `Route::apiResource()`. Inventory is the next resource -- it's critical for both product sync and inventory sync with Shopify. Inventory is unique in the codebase because it uses a composite key pattern (`account_id` + `colorway_id` + `base_id`) and has a dedicated quantity-update endpoint that uses `updateOrCreate`.

## Goal

Create an Inventory API controller with full CRUD plus a dedicated `PATCH /api/v1/inventory/{inventory}/quantity` endpoint for quantity updates. Support filtering by `colorway_id` and/or `base_id`. After this prompt, the API can manage inventory records and update quantities for specific colorway+base combinations.

## Non-Goals

- Do not create bulk update or batch import endpoints
- Do not add inventory history or audit logging
- Do not modify the Inventory model, policy, or FormRequests

## Constraints

- Follow the controller pattern from Story 0.4 (namespace `Api\V1`, extend `ApiController`, reuse FormRequests, return via `InventoryResource`)
- Index should eager-load `['colorway', 'base']` and support `?colorway_id=` and `?base_id=` query parameter filters
- The dedicated quantity endpoint (`PATCH /inventory/{inventory}/quantity`) mirrors the web route at `creator.php` line 66: it accepts `colorway_id`, `base_id`, and `quantity`, then uses `updateOrCreate` on the composite key `[account_id, colorway_id, base_id]`
- Reuse `StoreInventoryRequest`, `UpdateInventoryRequest`, and `UpdateInventoryQuantityRequest` for validation
- The quantity endpoint should use `UpdateInventoryQuantityRequest` which validates `colorway_id`, `base_id`, and `quantity`
- Store must set `account_id` from authenticated user
- Register the `apiResource` route plus a custom `PATCH` route for quantity update

## Acceptance Criteria

- [ ] `GET /api/v1/inventory` returns paginated inventory scoped to the user's account with colorway and base loaded
- [ ] `GET /api/v1/inventory?colorway_id=1` filters by colorway
- [ ] `GET /api/v1/inventory?base_id=2` filters by base
- [ ] `GET /api/v1/inventory?colorway_id=1&base_id=2` filters by both
- [ ] `POST /api/v1/inventory` creates an inventory record with `account_id` set
- [ ] `GET /api/v1/inventory/{inventory}` returns a single record with relationships
- [ ] `PATCH /api/v1/inventory/{inventory}` updates an inventory record
- [ ] `DELETE /api/v1/inventory/{inventory}` deletes an inventory record
- [ ] `PATCH /api/v1/inventory/{inventory}/quantity` updates quantity using `updateOrCreate` on the composite key
- [ ] Tests cover auth, authorization, validation, CRUD, quantity update, and filtering
- [ ] All existing tests still pass

---

## Tech Analysis

- **Inventory doesn't use SoftDeletes** -- unlike catalog models, `DELETE` actually removes the record. The controller's destroy method should use `$inventory->delete()` and return a 204 (or a success response with no content).
- **The `updateOrCreate` pattern** in the web `InventoryController::updateQuantity()` uses `Inventory::updateOrCreate(['account_id' => ..., 'colorway_id' => ..., 'base_id' => ...], ['quantity' => ...])`. This means the quantity endpoint can both create and update -- if the combination doesn't exist, it creates it. The API endpoint should preserve this behavior.
- **`UpdateInventoryQuantityRequest`** validates `colorway_id` (required, exists), `base_id` (required, exists), `quantity` (required, integer, min:0). It authorizes via `can('create', Inventory::class)` since it might create a new record.
- **Inventory has a unique constraint** on `[account_id, colorway_id, base_id]`. The store endpoint should handle the case where a duplicate is attempted -- Laravel will throw a `QueryException` with a unique violation. Either catch it and return a 422, or let the unique constraint speak for itself.
- **Filtering is straightforward** -- just `where('colorway_id', ...)` and `where('base_id', ...)` conditionally applied to the query.
- **The custom route** for quantity update needs to be registered separately from `apiResource`. Place it before the resource route so it doesn't conflict: `Route::patch('inventory/{inventory}/quantity', ...)`.

## References

- `platform/app/Http/Controllers/Api/V1/ColorwayController.php` -- established API controller pattern to follow (created in Story 0.4)
- `platform/app/Http/Controllers/InventoryController.php` -- web controller: `updateQuantity()` method with updateOrCreate pattern, index eager loading
- `platform/app/Http/Requests/StoreInventoryRequest.php` -- validation: colorway_id, base_id, quantity
- `platform/app/Http/Requests/UpdateInventoryQuantityRequest.php` -- validation for quantity endpoint: colorway_id, base_id, quantity
- `platform/app/Policies/InventoryPolicy.php` -- standard account scoping, fully enabled
- `platform/app/Http/Resources/Api/V1/InventoryResource.php` -- Resource with conditional colorway and base (created in Story 0.3)
- `platform/app/Models/Inventory.php` -- unique constraint on [account_id, colorway_id, base_id], no SoftDeletes

## Files

- Create `platform/app/Http/Controllers/Api/V1/InventoryController.php` -- CRUD + quantity update endpoint
- Modify `platform/routes/api.php` -- add apiResource for inventory plus custom quantity route
- Create `platform/tests/Feature/Api/V1/InventoryControllerTest.php` -- tests for CRUD, quantity update, filtering
