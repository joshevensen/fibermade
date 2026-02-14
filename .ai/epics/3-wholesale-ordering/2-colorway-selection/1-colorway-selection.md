status: done

# Story 3.2: Prompt 1 -- Order Builder: Colorway Selection (Step 1)

## Context

Story 3.1 built the store home page with creator cards (order counts, "View Orders" / "New Order" buttons) and the per-creator order list page. The "New Order" button links to `/store/{creator}/order`. No route, controller action, or Vue page exists for this URL. The creator's catalog data (Colorways, Bases, Collections, Inventory) is fully populated from Epics 0-2. The `creator_store` pivot table has `discount_rate` for wholesale pricing. The store needs to browse colorways and select which ones to include in their order before choosing bases and quantities (Story 3.3).

## Goal

Build the first step of the order flow: a two-panel page where stores browse a creator's active colorways (left panel, 2/3 width) with filters, and build a selection list (right panel, 1/3 width). The store clicks "Continue" to proceed to step 2 (base & quantity selection). This prompt handles the route, controller, data loading, and the full Vue page for step 1.

## Non-Goals

- Do not build the base & quantity selection page (that's Story 3.3)
- Do not create the Order or OrderItem records yet (those are created in Story 3.3 when quantities are set)
- Do not handle draft order resumption (the `?draft={orderId}` parameter will be handled in Story 3.3)
- Do not modify models, policies, or enums
- Do not add image upload functionality

## Constraints

- Route: `GET /store/{creator}/order` in `routes/store.php`, named `store.creator.order.step1`. Step 2 will be `GET /store/{creator}/order/step-2` (Story 3.3), named `store.creator.order.step2`.
- The controller must verify the store has a `creator_store` relationship with this creator (403 if not); reuse `viewCreatorOrders` policy like the order list action.
- Load the creator's active colorways with: collections, inventories (with base), and primary media
- The `discount_rate` from the `creator_store` pivot must be passed to the Vue page for wholesale price calculation. When `discount_rate` is null, Vue shows retail only (no wholesale line).
- Colorway data must include enough detail for filtering (collection names, colors) and display (name, primary image URL, description)
- Selected colorways are managed client-side (Vue reactive state) -- no server round-trip until the user clicks "Continue"
- The "Continue" action navigates to `GET /store/{creator}/order/step-2?colorways=1,2,3` (comma-separated IDs in query). Story 3.3 implements that route.
- Follow existing Vue patterns: TypeScript interfaces, `StoreLayout`, UI components

## Acceptance Criteria

- [x] New route: `GET /store/{creator}/order` named `store.creator.order.step1` renders the colorway selection page
- [x] Controller action:
  - Verifies store-creator relationship (403 if not)
  - Loads creator's active colorways with `collections`, `inventories.base`, `media`
  - Loads `discount_rate` from `creator_store` pivot
  - Loads all active collections for the creator (for filter dropdown)
  - Returns data via Inertia to the Vue page
- [x] Colorway data shape passed to Vue:
  - `id`, `name`, `description`, `status`, `colors` (array of color strings), `primary_image_url`
  - `collections`: array of `{ id, name }`
  - `bases`: array of `{ id, descriptor, weight, retail_price, inventory_quantity }` (derived from inventories with base)
- [x] Vue page `store/orders/ColorwaySelectionPage.vue`:
  - **Left panel (2/3 width)**: scrollable list of colorway cards
    - Each card shows: name, primary image (or placeholder), collection name(s), color tags
    - Cards are expandable to show: description, available bases with wholesale prices
    - Click/toggle to select a colorway (visual indicator: border highlight, checkmark, or similar)
  - **Right panel (1/3 width)**: sticky sidebar with selected colorways
    - Simple list of selected colorway names
    - Remove button per item
    - Count of selected colorways
  - **Filters** at the top of the left panel:
    - Collection dropdown (populated from creator's collections)
    - Color multi-select or tag filter
  - **Continue button** at the bottom (disabled if no colorways selected)
    - Navigates to `store.creator.order.step2` with query param `colorways=id1,id2,...`
  - **Empty state**: When creator has no active colorways, show "No colorways available. Ask this creator to add colorways to their catalog." and keep Continue disabled.
- [x] Wholesale price display: when `discount_rate` is set, show `retail_price * (1 - discount_rate)` per base in the expanded colorway view; when null, show retail only (no wholesale line).
- [x] Responsive: on mobile, stack layout — selection panel appears below the colorway grid (single column), not a bottom sheet.
- [x] Tests: controller tests for authorization, data loading, and correct data shape

---

## Tech Analysis

- **Loading colorways**: Query the creator's account colorways: `Colorway::where('account_id', $creator->account_id)->where('status', ColorwayStatus::Active)->with(['collections', 'inventories.base', 'media'])->get()`. This gives us everything needed for display and filtering.
- **Discount rate**: Load from the pivot: `$store->creators()->where('creator_id', $creator->id)->first()->pivot->discount_rate`. The `discount_rate` is a decimal (e.g., `0.20` for 20% off). When set, wholesale price = `retail_price * (1 - discount_rate)`. When null, Vue shows retail only (no wholesale line).
- **Primary image URL**: The `Colorway` model has a `getPrimaryImageUrlAttribute()` accessor that returns the URL of the primary media or the first media item. Access it as `$colorway->primary_image_url`. If no media exists, it returns null -- the Vue page should show a placeholder.
- **Colors enum**: Colorway has `colors` cast as `AsEnumCollection::class.':'.Color::class`. This is a collection of `Color` enum values (Red, Blue, Green, etc.). Serialize as an array of string values for the Vue page.
- **Collection filter**: Load `Collection::where('account_id', $creator->account_id)->where('status', BaseStatus::Active)->get()` separately for the filter dropdown. A colorway may belong to multiple collections.
- **Color filter**: The Vue page can filter client-side by checking if any of the colorway's colors match the selected filter colors. No server-side filtering needed since the dataset is small (typical creator has <200 colorways).
- **Passing selection to step 2**: Use URL query params. Continue navigates to `GET /store/{creator}/order/step-2?colorways=1,2,3` (comma-separated IDs). Use `router.visit()` with `query: { colorways: selectedIds.join(',') }` (or Wayfinder-generated URL). Story 3.3 implements the step-2 route.
- **Base data via Inventory**: A colorway's bases are accessed through the `inventories` relationship. Each Inventory record has `base_id` and `quantity`. Load `inventories.base` to get base details. Group by base to avoid duplicates (a colorway should have one inventory per base).
- **Responsive layout**: Use Tailwind's `lg:grid-cols-3` for the two-panel layout. On smaller screens, stack vertically — selection panel below the colorway grid (single column).

## References

- `platform/app/Http/Controllers/StoreController.php` -- add new `order()` action
- `platform/routes/store.php` -- add new route
- `platform/app/Models/Colorway.php` -- fields, relationships (collections, inventories, media), `primary_image_url` accessor, `colors` cast
- `platform/app/Models/Collection.php` -- fields (name, status)
- `platform/app/Models/Inventory.php` -- fields (colorway_id, base_id, quantity), `base()` relationship
- `platform/app/Models/Base.php` -- fields (descriptor, weight, retail_price)
- `platform/app/Models/Store.php` -- `creators()` relationship with pivot
- `platform/app/Enums/ColorwayStatus.php` -- Active status for filtering
- `platform/app/Enums/BaseStatus.php` -- Active status for collection filtering
- `platform/app/Enums/Color.php` -- all color values for filter UI
- `platform/resources/js/pages/store/HomePage.vue` -- existing component patterns
- `platform/resources/js/layouts/StoreLayout.vue` -- layout component
- `platform/resources/js/components/ui/UiCard.vue` -- card component
- `platform/resources/js/components/ui/UiTag.vue` -- tag component for colors/status

## Files

- Modify `platform/app/Http/Controllers/StoreController.php` -- add `order()` action that loads colorways, collections, discount_rate
- Modify `platform/routes/store.php` -- add `GET /{creator}/order` route named `store.creator.order.step1`
- Create `platform/resources/js/pages/store/orders/ColorwaySelectionPage.vue` -- two-panel layout with colorway browsing and selection
- Create `platform/tests/Feature/Http/Controllers/Store/ColorwaySelectionTest.php` -- tests for authorization (403 when no relationship), data loading, and correct payload shape (colorways, collections, discount_rate)
