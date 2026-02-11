status: pending

# Story 4.4: Prompt 1 -- Wholesale Terms Management

## Context

The `StoreEditPage.vue` already has a "Wholesale Settings" form that allows creators to edit terms per store: status (select button), discount rate, payment terms, minimum order quantity, minimum order value, lead time days, and allows preorders. The `StoreController::edit()` loads the store with its orders and passes them to the page. However, the store data passed to the page does not include the `creator_store` pivot fields -- the wholesale settings form exists in the UI but the values come from the store's direct props which are currently populated from a different source. The `StoreController::update()` calls `$store->update($request->validated())` which updates the Store model directly, not the pivot table. The `UpdateStoreRequest` validates store fields but may not include pivot fields. The `StoreIndexPage.vue` shows stores with status badges but does not display wholesale terms. The `creator_store` pivot has: discount_rate, minimum_order_quantity, minimum_order_value, payment_terms, lead_time_days, allows_preorders, status, notes.

## Goal

Wire up the wholesale terms management so that editing a store actually reads from and writes to the `creator_store` pivot table. Ensure the `StoreController::edit()` loads pivot data for the authenticated creator's relationship with the store, the form pre-fills with correct pivot values, and `StoreController::update()` saves changes to the pivot table. Add wholesale terms summary to the `StoreIndexPage.vue` so creators can see key terms at a glance.

## Non-Goals

- Do not modify the form layout in `StoreEditPage.vue` (it already has the right fields)
- Do not add new wholesale term fields beyond what's already in the pivot table
- Do not modify the invite flow
- Do not add bulk terms editing across multiple stores
- Do not modify the Store model's direct fields (name, email, address, etc.) -- those are managed by the store account itself

## Constraints

- The `StoreController::edit()` must load the pivot data via the creator's `stores()` BelongsToMany relationship, not from the Store model directly
- The store props passed to the page should merge pivot fields with store fields so the existing form works without restructuring
- `StoreController::update()` needs to update the `creator_store` pivot, not the Store model, for pivot fields (discount_rate, minimums, payment_terms, lead_time_days, allows_preorders, status, notes)
- Store fields (name, email, address) should not be editable by creators -- they belong to the store account. If the form currently allows editing store info, those fields should be read-only for creators who don't own the store
- The `StoreIndexPage.vue` enhancements should be minimal: show discount rate and payment terms as additional metadata on the grid item cards
- Use the existing `withPivot()` declaration on the Creator-Store BelongsToMany relationship to access pivot data
- Follow existing patterns: `StoreController` already handles creator vs store routing internally

## Acceptance Criteria

- [ ] `StoreController::edit()` for creators:
  - Loads the `creator_store` pivot data for the authenticated creator
  - Merges pivot fields into the store data passed to the page
  - Passes `statusOptions` for the pivot status (active, paused, ended)
  - Store info fields (name, email, address) are read-only in the UI for creators
- [ ] `StoreController::update()` for creators:
  - Updates the `creator_store` pivot table (not the Store model) for wholesale term fields
  - Validates pivot fields: discount_rate (nullable decimal 0-100), minimum_order_quantity (nullable integer ≥ 1), minimum_order_value (nullable decimal ≥ 0), payment_terms (nullable string), lead_time_days (nullable integer ≥ 0), allows_preorders (boolean), status (in: active, paused, ended), notes (nullable string)
  - Returns redirect to `stores.index`
- [ ] `StoreEditPage.vue`:
  - Store info section (name, owner, email, address) is displayed as read-only text, not form inputs
  - Wholesale settings form pre-fills correctly from pivot data
  - Form submission saves to pivot table
- [ ] `StoreIndexPage.vue`:
  - Grid item cards show discount rate (if set) and payment terms (if set) as additional metadata
- [ ] `StoreController::indexForCreator()` passes pivot data (discount_rate, payment_terms) along with store data
- [ ] Tests verify:
  - Edit page loads correct pivot data for the authenticated creator
  - Update saves to pivot, not to Store model
  - Store info fields are not modified by update
  - Index page includes pivot metadata
- [ ] `php artisan test --filter=StoreControllerTest` passes

---

## Tech Analysis

- **Current edit flow**: `StoreController::edit()` receives a `Store` model via route model binding and passes it directly to the page. The page's `Props.store` interface includes pivot fields (discount_rate, etc.) but these values are not populated because they come from the pivot table, not the Store model directly. The fix: after loading the store, query the creator's relationship to get the pivot data and merge it into the store array.
- **Loading pivot data**: The authenticated user's account has a creator. Use `$creator->stores()->where('store_id', $store->id)->first()` to get the store with pivot data. Then `$storePivot = $storeWithPivot->pivot` gives access to all pivot fields. Merge these into the store array: `array_merge($store->toArray(), $storePivot->only([...]))`.
- **Update flow**: Currently `StoreController::update()` calls `$store->update($request->validated())`. For pivot fields, instead do: `$creator->stores()->updateExistingPivot($store->id, $pivotData)`. Split the validated data into store fields and pivot fields. For creators, only allow pivot field updates.
- **Authorization distinction**: Creators can edit wholesale terms (pivot) but not store info (belongs to the store's account). Store owners can edit their own store info. The `StoreController::update()` needs to distinguish between these two cases. Since creator routes go through `routes/creator.php` and store routes through `routes/store.php`, the route context already distinguishes the user type.
- **Index pivot data**: The `indexForCreator()` method already calls `$creator->stores()->with('account')` and accesses `$store->pivot` in `mergeStoresAndInvites()` and `transformStoresForIndex()`. The pivot data is available -- just need to include `discount_rate` and `payment_terms` in the transformed data arrays.
- **Validation**: Create an `UpdateStoreWholesaleTermsRequest` form request (or add pivot validation rules to the existing `UpdateStoreRequest`) that validates only pivot fields. Since the same `update()` route handles both creator and store updates, conditionally validate based on the user type.

## References

- `platform/app/Http/Controllers/StoreController.php` -- edit/update/indexForCreator/transformStoresForIndex methods
- `platform/app/Models/Creator.php` -- `stores()` BelongsToMany with pivot fields
- `platform/app/Models/Store.php` -- `creators()` BelongsToMany
- `platform/database/migrations/2026_01_10_173438_create_creator_store_table.php` -- pivot structure
- `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- existing form to adjust
- `platform/resources/js/pages/creator/stores/StoreIndexPage.vue` -- grid items to enhance
- `platform/app/Http/Requests/UpdateStoreRequest.php` -- existing validation rules
- `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- existing test patterns

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- update `edit()` to load pivot data, update `update()` to write pivot data, update `indexForCreator()`/`transformStoresForIndex()` to include pivot metadata
- Modify `platform/resources/js/pages/creator/stores/StoreEditPage.vue` -- make store info read-only, ensure form values come from pivot data
- Modify `platform/resources/js/pages/creator/stores/StoreIndexPage.vue` -- add discount rate and payment terms to grid item metadata
- Modify `platform/app/Http/Requests/UpdateStoreRequest.php` -- add pivot field validation rules (or create new request class)
- Modify `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- add tests for pivot CRUD
