status: done

# Story 4.4: Prompt 1 -- Wholesale Terms Management

## Context

The `StoreEditPage.vue` already has a "Wholesale Settings" form that allows creators to edit terms per store: status (select button), discount rate, payment terms, minimum order quantity, minimum order value, lead time days, and allows preorders. The `StoreController::edit()` loads the store with its orders and passes them to the page. However, the store data passed to the page does not include the `creator_store` pivot fields -- the wholesale settings form exists in the UI but the values come from the store's direct props which are currently populated from a different source. The `StoreController::update()` calls `$store->update($request->validated())` which updates the Store model directly, not the pivot table. The `UpdateStoreRequest` validates store fields but may not include pivot fields. The `StoreIndexPage.vue` shows stores with status badges but does not display wholesale terms. The `creator_store` pivot has: discount_rate, minimum_order_quantity, minimum_order_value, payment_terms, lead_time_days, allows_preorders, status, notes. Currently `StorePolicy::update()` only allows the store's own account (or admin), so creators get 403 on submit.

## Goal

Wire up the wholesale terms management so that editing a store actually reads from and writes to the `creator_store` pivot table. Ensure the `StoreController::edit()` loads pivot data for the authenticated creator's relationship with the store, the form pre-fills with correct pivot values, and `StoreController::update()` saves changes to the pivot table. Add wholesale terms summary to the `StoreIndexPage.vue` so creators can see key terms at a glance.

## Non-Goals

- Do not add new wholesale term fields beyond what's already in the pivot table
- Do not modify the invite flow
- Do not add bulk terms editing across multiple stores
- Do not modify the Store model's direct fields (name, email, address, etc.) -- those are managed by the store account itself

## Decisions

- **Authorization:** Single `update` action. Extend `StorePolicy::update()` to allow creators who have a relationship with the store (same as `view`). In `StoreController::update()` branch by user type: creators only write to the pivot; store owner/admin only write to the Store model.
- **Validation:** Single `UpdateStoreRequest` with conditional `rules()`: for creators return only pivot rules; for store owner/admin return only store rules.
- **Notes:** Notes live in Wholesale Settings only. Remove Notes from the Store Information card so that section is strictly read-only store profile (name, owner, email, address).
- **Form payload:** When the user is a creator, the Wholesale Settings form submits only pivot fields (status, discount_rate, payment_terms, minimum_order_quantity, minimum_order_value, lead_time_days, allows_preorders, notes). No store fields in the payload.
- **Edit when no relationship:** Resolve the store via the creator's `stores()` relationship. If no pivot row exists, `abort(404)`.
- **Status options:** `statusOptions` = active, paused, ended only (no pending in the dropdown). Pending remains a valid DB value but is not selectable in the form.

## Constraints

- The `StoreController::edit()` must load the store via the creator's `stores()` relationship so pivot data is available; if the creator has no relationship with the store, return 404.
- The store props passed to the page must merge pivot fields with store fields so the form receives a single `store` object.
- `StoreController::update()` must branch on user type: for creators, update only the `creator_store` pivot via `$creator->stores()->updateExistingPivot($store->id, $pivotData)`; for store owner/admin, update only the Store model. Creators must not write to the Store model.
- Store fields (name, email, address) are not editable by creators and must not be in the creator submit payload.
- The `StoreIndexPage.vue` enhancements should be minimal: show discount rate and payment terms as additional metadata on the grid item cards.
- Use the existing `withPivot()` declaration on the Creator-Store BelongsToMany relationship to access pivot data.
- `UpdateStoreRequest::rules()` must return pivot rules when the user is a creator, store rules when the user is store owner or admin.

## Acceptance Criteria

- [ ] **StorePolicy:** `update()` allows creators who have a relationship with the store (in addition to store owner and admin).
- [ ] `StoreController::edit()` for creators:
  - Resolves the store via the authenticated creator's `stores()` relationship; if no relationship, aborts with 404.
  - Loads and merges `creator_store` pivot data into the store data passed to the page.
  - Passes `statusOptions` with (active, paused, ended) only.
  - Store info section contains only name, owner, email, address (read-only); no Notes in this section.
- [ ] `StoreController::update()`:
  - For creators: validates only pivot fields, updates only the `creator_store` pivot; does not update the Store model.
  - For store owner/admin: validates only store fields, updates only the Store model.
  - Returns redirect to `stores.index`.
- [ ] `UpdateStoreRequest::rules()`: returns pivot rules when user is a creator, store rules when user is store owner or admin. Pivot rules: discount_rate (nullable decimal 0-100), minimum_order_quantity (nullable integer ≥ 1), minimum_order_value (nullable decimal ≥ 0), payment_terms (nullable string), lead_time_days (nullable integer ≥ 0), allows_preorders (boolean), status (in: active, paused, ended), notes (nullable string).
- [ ] `StoreEditPage.vue`:
  - Store info section: name, owner, email, address only (read-only text). Notes removed from this section.
  - Wholesale Settings: includes status, discount_rate, payment_terms, minimums, lead_time_days, allows_preorders, notes. Pre-fills from pivot data.
  - When the page is used by a creator, form submission sends only wholesale (pivot) fields; submission saves to pivot table.
- [ ] `StoreIndexPage.vue`: grid item cards show discount rate (if set) and payment terms (if set) as additional metadata.
- [ ] `StoreController::indexForCreator()` / `mergeStoresAndInvites()` / `transformStoresForIndex()`: include `discount_rate` and `payment_terms` in store items; handle null pivot (e.g. admin path) safely.
- [ ] Tests verify:
  - Creator can open edit and submit; update persists to pivot only; Store model unchanged.
  - Edit page returns 404 when creator has no relationship with the store.
  - Edit page loads correct pivot data for the authenticated creator.
  - Index page includes pivot metadata (discount_rate, payment_terms).
- [ ] `php artisan test --filter=StoreControllerTest` passes

---

## Tech Analysis

- **Edit flow:** Resolve store via `$creator->stores()->where('stores.id', $store->id)->first()`. If null, `abort(404)`. Merge `$store->toArray()` with pivot-only keys from `$storeWithPivot->pivot`. Pass single `store` array and `statusOptions` to the page.
- **Update flow:** If user is creator, validate pivot input via `UpdateStoreRequest` (conditional rules), then `$creator->stores()->updateExistingPivot($store->id, $request->validated())`. If user is store owner or admin, validate store input and `$store->update($request->validated())`. Do not mix; creator path must never call `$store->update()` with request data.
- **Validation:** In `UpdateStoreRequest::rules()`, check `$this->user()->account?->type === AccountType::Creator` (and that they have a creator). If creator, return array of pivot rules only. Otherwise return existing store rules. Ensures creators cannot submit store fields and store owners cannot submit pivot-only payloads that would be ignored.
- **Index pivot data:** In `mergeStoresAndInvites()` and `transformStoresForIndex()`, add `discount_rate` and `payment_terms` from `$store->pivot` when present; use null-safe access for admin path where stores are loaded without the relationship.

## References

- `platform/app/Http/Controllers/StoreController.php` -- edit/update/indexForCreator/mergeStoresAndInvites/transformStoresForIndex
- `platform/app/Policies/StorePolicy.php` -- update() to allow creators with relationship
- `platform/app/Models/Creator.php` -- stores() BelongsToMany with pivot fields
- `platform/app/Models/Store.php` -- creators() BelongsToMany
- `platform/database/migrations/2026_01_10_173438_create_creator_store_table.php` -- pivot structure
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- store info read-only, notes in Wholesale Settings only, submit only pivot fields for creator
- `platform/resources/js/pages/creator/stores/StoreIndexPage.vue` -- grid item metadata
- `platform/app/Http/Requests/UpdateStoreRequest.php` -- conditional rules by user type
- `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- pivot CRUD and authorization tests

## Files

- Modify `platform/app/Policies/StorePolicy.php` -- allow creators with store relationship in `update()`
- Modify `platform/app/Http/Controllers/StoreController.php` -- edit() resolve via creator relationship and 404 when missing; update() branch by user type (pivot vs store); index include discount_rate, payment_terms
- Modify `platform/app/Http/Requests/UpdateStoreRequest.php` -- conditional rules: pivot rules for creators, store rules for others
- Modify `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- remove Notes from Store Information; ensure Wholesale Settings includes notes and form submits only pivot fields when user is creator
- Modify `platform/resources/js/pages/creator/stores/StoreIndexPage.vue` -- add discount_rate and payment_terms to grid item metadata
- Modify `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- tests for edit pivot data, update pivot only, 404 when no relationship, index pivot metadata, creator update allowed
