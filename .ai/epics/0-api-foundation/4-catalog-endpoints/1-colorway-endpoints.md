status: done

# Story 0.4: Prompt 1 -- Colorway CRUD Endpoints

## Context

Stories 0.1-0.3 established the API infrastructure: Sanctum auth, `ApiController` base class with response helpers and account scoping, exception handling, rate limiting, and API Resources for all models. The `routes/api.php` has a v1 prefix group with `auth:sanctum` and `throttle:api` middleware. No API controllers for specific resources exist yet. This is the first resource controller -- it establishes the API CRUD pattern that all subsequent stories will follow.

## Goal

Create a Colorway API controller with full CRUD endpoints (index, show, store, update, delete) and tests. After this prompt, `GET/POST /api/v1/colorways` and `GET/PATCH/DELETE /api/v1/colorways/{colorway}` work end-to-end with auth, authorization, validation, and JSON responses via `ColorwayResource`.

## Non-Goals

- Do not create endpoints for Bases or Collections (that's Prompt 2)
- Do not add relationship management endpoints (e.g., syncing colorway-collection associations)
- Do not modify existing web controllers or routes
- Do not modify the Colorway model, policy, or FormRequests

## Constraints

- Controller goes in `app/Http/Controllers/Api/V1/ColorwayController.php` -- namespaced under `Api\V1` to match the versioned route structure
- Extend `ApiController` and use its response helpers (`successResponse`, `createdResponse`, `notFoundResponse`) and account scoping (`scopeToAccount`)
- Reuse existing `StoreColorwayRequest` and `UpdateColorwayRequest` for validation -- they already include policy authorization in their `authorize()` methods
- Use `ColorwayResource` (from Story 0.3) for all JSON serialization
- Index should eager-load `['collections', 'inventories', 'media']` following the web controller's pattern in `ColorwayController`
- Store must set `account_id` and `created_by` from the authenticated user, matching the web controller pattern in `ColorwayController::store()`
- Update must set `updated_by` from the authenticated user
- Register routes in `routes/api.php` inside the v1 group using `Route::apiResource()`
- Tests go in `tests/Feature/Api/V1/` to match the controller namespace
- Test auth (401 without token), authorization (403 for wrong account), validation (422 for bad data), and successful CRUD (200/201/204)

## Acceptance Criteria

- [ ] `GET /api/v1/colorways` returns paginated list of colorways scoped to the user's account
- [ ] `POST /api/v1/colorways` creates a colorway with validated data, sets `account_id` and `created_by`
- [ ] `GET /api/v1/colorways/{colorway}` returns a single colorway with loaded relationships
- [ ] `PATCH /api/v1/colorways/{colorway}` updates with validated data, sets `updated_by`
- [ ] `DELETE /api/v1/colorways/{colorway}` soft-deletes the colorway
- [ ] All responses use `ColorwayResource` for JSON serialization
- [ ] Request without token returns 401
- [ ] Request for another account's colorway returns 403
- [ ] Invalid data returns 422 with field-level errors
- [ ] All existing tests still pass

---

## Tech Analysis

- **This is the first API resource controller** -- it sets the pattern. Subsequent controllers (Base, Collection, Inventory, Order, etc.) will follow whatever structure is established here. Keep it clean and conventional.
- **Existing web `ColorwayController`** has the account scoping, eager loading, and store/update patterns to mirror. The API version is simpler (no Inertia rendering, no enum option mapping for forms), but the data operations are identical.
- **`StoreColorwayRequest` handles authorization** via `can('create', Colorway::class)` in its `authorize()` method. The API controller does NOT need to call `$this->authorize()` separately for store/update -- the FormRequest handles it. But `index`, `show`, and `destroy` need explicit authorization calls.
- **`ColorwayPolicy` is fully enabled** -- all actions work for account-scoped users. No policy modifications needed.
- **`Route::apiResource()`** generates index, store, show, update, destroy routes automatically -- no create/edit routes (those are form-only for web). This is the right choice for API controllers.
- **Pagination**: Use Laravel's `->paginate()` for index endpoints. `ColorwayResource::collection()` works with paginated results and includes pagination metadata automatically.

## References

- `platform/app/Http/Controllers/Api/ApiController.php` -- base class to extend, response helpers and account scoping methods (created in Story 0.2)
- `platform/app/Http/Controllers/ColorwayController.php` -- web controller patterns for eager loading, store (sets account_id, created_by), update (sets updated_by)
- `platform/app/Http/Requests/StoreColorwayRequest.php` -- validation rules and authorization to reuse
- `platform/app/Http/Requests/UpdateColorwayRequest.php` -- validation rules and authorization to reuse
- `platform/app/Policies/ColorwayPolicy.php` -- authorization logic (standard account scoping pattern)
- `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- Resource for JSON serialization (created in Story 0.3)
- `platform/routes/api.php` -- register new routes in v1 group
- `platform/tests/Feature/Http/Controllers/ColorwayControllerTest.php` -- web test patterns to adapt for API

## Files

- Create `platform/app/Http/Controllers/Api/V1/ColorwayController.php` -- CRUD controller extending ApiController
- Modify `platform/routes/api.php` -- add `Route::apiResource('colorways', ...)` inside v1 group
- Create `platform/tests/Feature/Api/V1/ColorwayControllerTest.php` -- tests for auth, authorization, validation, and CRUD
