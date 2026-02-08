status: pending

# Story 0.4: Prompt 2 -- Base & Collection CRUD Endpoints

## Context

Prompt 1 created the Colorway API controller with full CRUD, establishing the API controller pattern: extend `ApiController`, use `scopeToAccount()`, reuse existing FormRequests for validation+authorization, return responses via API Resources, register with `Route::apiResource()`. The Colorway controller includes status filtering and pagination. Base and Collection endpoints follow the same pattern.

## Goal

Create API controllers for Base and Collection with full CRUD endpoints, following the pattern established by the Colorway controller. After this prompt, all catalog resources (Colorways, Bases, Collections) have working API endpoints.

## Non-Goals

- Do not modify the Colorway controller from Prompt 1
- Do not add collection-colorway relationship sync endpoints (the web app has `updateColorways` and `updateCollections` actions, but these are not part of the Story 0.4 scope)
- Do not modify existing models, policies, or FormRequests

## Constraints

- Follow the exact pattern from `ColorwayController` (Prompt 1): same namespace, same base class, same response helper usage, same test structure
- Base controller: eager-load `['inventories']` on show, support `?status=` and `?weight=` query parameter filters
- Collection controller: eager-load `['colorways']` on show, use `withCount('colorways')` on index, support `?status=` filter
- Base route parameter must be explicitly set: `Route::apiResource('bases', ...)->parameters(['bases' => 'base'])` -- matching the web route in `creator.php` line 46
- Reuse existing FormRequests: `StoreBaseRequest`/`UpdateBaseRequest` and `StoreCollectionRequest`/`UpdateCollectionRequest`
- Use `BaseResource` and `CollectionResource` from Story 0.3
- Store endpoints set `account_id` from authenticated user
- Tests follow the same structure as Colorway tests from Prompt 1

## Acceptance Criteria

- [ ] `GET/POST /api/v1/bases` and `GET/PATCH/DELETE /api/v1/bases/{base}` work with auth, authorization, validation
- [ ] `GET /api/v1/bases?status=active` and `GET /api/v1/bases?weight=worsted` filter correctly
- [ ] `GET/POST /api/v1/collections` and `GET/PATCH/DELETE /api/v1/collections/{collection}` work with auth, authorization, validation
- [ ] `GET /api/v1/collections` includes `colorways_count` in each resource
- [ ] `GET /api/v1/collections?status=active` filters correctly
- [ ] All responses use the correct API Resource for serialization
- [ ] Tests cover auth (401), authorization (403), validation (422), and CRUD operations for both resources
- [ ] All existing tests still pass

---

## Tech Analysis

- **Base and Collection are simpler than Colorway** -- fewer relationships, no complex casts. The controllers are straightforward CRUD following the pattern from Prompt 1.
- **Base has a route parameter quirk**: Laravel's resource routing pluralizes the parameter name to `bases`, but the model expects `base`. The web routes handle this with `->parameters(['bases' => 'base'])` at `creator.php` line 46-47. The API route needs the same.
- **Collection's `withCount('colorways')`** adds a `colorways_count` attribute to each model. The `CollectionResource` needs to include this field when it's present (use `$this->when(isset($this->colorways_count), $this->colorways_count)`).
- **Base has many nullable fields** (fiber percentages, cost, retail_price, etc.). The store endpoint just passes through validated data -- no special handling needed since the FormRequest already validates.
- **`StoreCollectionRequest` validates** name (required), description (nullable), status (required, BaseStatus enum). Simple validation.
- **Both policies follow the standard pattern** -- fully enabled, account-scoped. No modifications needed.

## References

- `platform/app/Http/Controllers/Api/V1/ColorwayController.php` -- pattern to follow exactly (created in Prompt 1)
- `platform/app/Http/Controllers/BaseController.php` -- web controller: status filtering pattern (lines 27-39), store pattern
- `platform/app/Http/Controllers/CollectionController.php` -- web controller: withCount pattern, eager loading
- `platform/app/Http/Requests/StoreBaseRequest.php` -- validation rules
- `platform/app/Http/Requests/StoreCollectionRequest.php` -- validation rules
- `platform/app/Policies/BasePolicy.php` -- standard account scoping
- `platform/app/Policies/CollectionPolicy.php` -- standard account scoping
- `platform/routes/creator.php` -- line 46: `->parameters(['bases' => 'base'])` pattern to replicate

## Files

- Create `platform/app/Http/Controllers/Api/V1/BaseController.php` -- CRUD with status and weight filtering
- Create `platform/app/Http/Controllers/Api/V1/CollectionController.php` -- CRUD with colorways_count and status filtering
- Modify `platform/routes/api.php` -- add apiResource routes for bases and collections
- Create `platform/tests/Feature/Api/V1/BaseControllerTest.php` -- CRUD and filter tests
- Create `platform/tests/Feature/Api/V1/CollectionControllerTest.php` -- CRUD and filter tests
