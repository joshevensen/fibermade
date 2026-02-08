status: done

# Story 0.3: Prompt 1 -- Catalog Resources (Colorway, Base, Collection, Inventory)

## Context

Story 0.2 created the `ApiController` base class with JSON response helpers and account scoping, expanded `routes/api.php` with a v1 prefix group, and configured exception handling and rate limiting. The API infrastructure is in place. No API Resources exist yet -- the `app/Http/Resources/` directory doesn't exist. The models are mature (18 models with relationships, casts, and enums), but the API has no way to serialize them to JSON.

## Goal

Create Laravel API Resources for the catalog-related models: Colorway, Base, Collection, and Inventory. After this prompt, each model has a Resource class that produces a clean, consistent JSON representation with conditionally included relationships. Story 0.4 and 0.5 endpoints will use these Resources directly.

## Non-Goals

- Do not create Resources for Order, Customer, Integration, or ExternalIdentifier (that's Prompt 2)
- Do not create API controllers or routes (those are Stories 0.4-0.5)
- Do not add new fields or relationships to the models
- Do not create Collection classes (e.g., `ColorwayCollection`) unless needed for pagination metadata -- Laravel's `ResourceCollection` auto-wrapping with `::collection()` is sufficient
- Do not add computed fields or business logic to Resources -- they serialize what the model already has

## Constraints

- Resources go in `app/Http/Resources/Api/V1/` to match the versioned API namespace and leave room for future API versions
- Each Resource should use `$this->whenLoaded()` for relationships so they're only included when eager-loaded -- never trigger N+1 queries from a Resource
- Enum fields (e.g., `status`, `technique`, `weight`, `type`) should serialize as their string values (which is the default behavior with Laravel's enum casting, but verify)
- The `colors` field on Colorway is cast to `AsEnumCollection` of `Color` -- serialize it as an array of string values
- Follow Laravel conventions: Resources extend `JsonResource`, return an array from `toArray()`
- Include `id`, `created_at`, and `updated_at` in every Resource
- Decimal fields (e.g., `cost`, `retail_price`, `unit_price`) should serialize as strings to preserve precision (e.g., `"12.50"` not `12.5`)
- Relationships should nest the related Resource (e.g., `InventoryResource` includes `ColorwayResource` and `BaseResource` when loaded)
- Use Pest syntax for tests following the patterns in `tests/Feature/`

## Acceptance Criteria

- [ ] `app/Http/Resources/Api/V1/ColorwayResource.php` exists and serializes: id, name, description, technique, colors (array of strings), per_pan, status, created_at, updated_at, and conditionally: collections, inventories, media (primary_image_url)
- [ ] `app/Http/Resources/Api/V1/BaseResource.php` exists and serializes: id, descriptor, description, code, status, weight, size, cost, retail_price, fiber percentages, created_at, updated_at
- [ ] `app/Http/Resources/Api/V1/CollectionResource.php` exists and serializes: id, name, description, status, created_at, updated_at, and conditionally: colorways
- [ ] `app/Http/Resources/Api/V1/InventoryResource.php` exists and serializes: id, colorway_id, base_id, quantity, created_at, updated_at, and conditionally: colorway, base
- [ ] Relationships use `$this->whenLoaded()` and return the appropriate Resource type
- [ ] Tests verify each Resource produces the expected JSON structure when given a model instance
- [ ] Tests verify that relationships are excluded when not loaded and included when loaded
- [ ] All existing tests still pass (`php artisan test`)

---

## Tech Analysis

- **No `app/Http/Resources/` directory exists.** The entire directory tree needs to be created. Using `Api/V1/` namespacing keeps resources versioned alongside the API routes (`/api/v1/`), so a future v2 API can have different resource shapes without breaking v1.
- **Colorway has complex fields:**
  - `colors` is cast as `AsEnumCollection::of(Color::class)` -- this is a Laravel collection of Color enum instances. In the Resource, map it to plain string values: `$this->colors?->map(fn ($c) => $c->value)->toArray()`.
  - `technique` is cast to `Technique` enum -- serializes to its string value automatically via `->value`.
  - `primary_image_url` is an accessor (`getPrimaryImageUrlAttribute`) that returns a storage URL. Include this in the Resource as a flat field rather than nesting the full media relationship.
- **Base has 10 fiber percentage fields** (wool_percent through linen_percent). Serialize all of them as individual fields rather than grouping into a sub-object -- this keeps the API flat and predictable for consumers.
- **Base decimal fields** (`cost`, `retail_price`, fiber percentages) are cast as `decimal:2`. When serialized to JSON, PHP may strip trailing zeros (12.50 becomes 12.5). Use `number_format()` or cast to string to preserve the expected precision.
- **Inventory has a unique constraint** on `[account_id, colorway_id, base_id]`. The Resource doesn't need to know this, but it's why `colorway_id` and `base_id` are important to include as flat fields even when the relationships are nested.
- **Collection ↔ Colorway is many-to-many** via `colorway_collection` pivot table. The Resource should nest `ColorwayResource::collection()` when colorways are loaded, but the pivot timestamps are not useful to API consumers and can be omitted.
- **SoftDeletes**: Colorway, Base, and Collection use SoftDeletes. The `deleted_at` field should NOT be included in the Resource -- soft-deleted records won't be returned by default queries, and if they are, the consumer shouldn't see the deletion timestamp.

## References

- `platform/app/Models/Colorway.php` -- fields, casts (AsEnumCollection for colors, enum for technique/status), relationships (collections, inventories, media, dyes), getPrimaryImageUrlAttribute accessor
- `platform/app/Models/Base.php` -- fields, casts (decimal:2 for costs and percentages, enum for status/weight), fiber percentage fields
- `platform/app/Models/Collection.php` -- fields, casts (enum for status), belongsToMany colorways
- `platform/app/Models/Inventory.php` -- fields, casts (integer for quantity), belongsTo colorway and base
- `platform/app/Enums/ColorwayStatus.php` -- Idea, Active, Retired
- `platform/app/Enums/BaseStatus.php` -- Active, Retired
- `platform/app/Enums/Technique.php` -- Solid, Tonal, Variegated, Speckled, Other
- `platform/app/Enums/Weight.php` -- Lace, Fingering, DK, Worsted, Bulky
- `platform/app/Enums/Color.php` -- 18 color values

## Files

- Create `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- serializes Colorway with conditional collections, inventories, primary_image_url
- Create `platform/app/Http/Resources/Api/V1/BaseResource.php` -- serializes Base with all fiber percentages and decimal formatting
- Create `platform/app/Http/Resources/Api/V1/CollectionResource.php` -- serializes Collection with conditional colorways
- Create `platform/app/Http/Resources/Api/V1/InventoryResource.php` -- serializes Inventory with conditional colorway and base
- Create `platform/tests/Feature/Api/Resources/CatalogResourceTest.php` -- tests for all four Resources (JSON structure, relationship inclusion/exclusion)
