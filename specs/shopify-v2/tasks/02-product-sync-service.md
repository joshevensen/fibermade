# Task 02 — Product Sync Service (Laravel)

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and the following task files: `tasks/02-product-sync-service.md`, `tasks/03-collection-sync-service.md`, `tasks/04-inventory-sync-pull-all.md`. Implement all three tasks in order. Work through the checklist in each file before moving to the next. Don't start Task 05.



## Goal

Port the TypeScript `ProductSyncService` to a new Laravel `ShopifyProductSyncService`. This service pulls products from Shopify and creates or updates the corresponding Colorways, Bases, and Inventory records in Fibermade.

## Reference

TypeScript source to port: `shopify/app/services/sync/product-sync.server.ts`

## What to Build

### `ShopifyProductSyncService`

**Main method:**
```php
syncAll(Integration $integration): SyncResult
```
Iterates all Shopify products (paginated) and calls `syncProduct()` for each.

**Per-product method:**
```php
syncProduct(array $shopifyProduct, Integration $integration): ProductSyncResult
```

### Import Logic (per product)

1. Check if a mapping already exists in `external_identifiers` (`shopify_product` → `Colorway`)
2. **If no mapping** → create new Colorway
   - Map Shopify status to Fibermade status: `ACTIVE→active`, `DRAFT→idea`, `ARCHIVED→retired`
   - Upload featured image as a Media record (if present)
   - For each variant → find or create Base (match on descriptor + price), create Inventory record
   - Create external identifier mappings: product→colorway, each variant→inventory
3. **If mapping exists** → update existing Colorway
   - Update name, description, status
   - Compare variants to existing inventory records
   - Create new Bases/Inventory for added variants
   - Retire Bases for removed variants
   - Update prices for changed variants
4. Return `ProductSyncResult` with status (created/updated/skipped/failed) and any errors

### Status Mapping

| Shopify    | Fibermade |
|------------|-----------|
| ACTIVE     | active    |
| DRAFT      | idea      |
| ARCHIVED   | retired   |

### Base Matching

When a variant arrives, look for an existing Base by:
- Same `descriptor` (variant title)
- Belonging to the same creator account

If found, reuse it. If not, create a new one.

### Image Handling

- Only sync the featured image
- Skip if no image URL
- Skip if image is already synced (check existing media on the colorway)

## `SyncResult` Shape

```php
class SyncResult {
    int $created;
    int $updated;
    int $skipped;
    int $failed;
    array $errors; // [['product_gid' => '...', 'message' => '...']]
}
```

## Files to Create

- `platform/app/Services/Shopify/ShopifyProductSyncService.php`
- `platform/app/Data/Shopify/SyncResult.php` (or similar DTO)

## Files Likely Affected

- Existing `ExternalIdentifier` model usage
- `Colorway`, `Base`, `Inventory` models

## Tests

- Test product created when no mapping exists
- Test product updated when mapping exists
- Test variant added to existing colorway
- Test variant removed (base retired)
- Test failed product doesn't break the rest of the sync
- Test status mapping

## Checklist

- [ ] Create `SyncResult` DTO (shared across sync services)
- [ ] Create `ShopifyProductSyncService` class
- [ ] Implement `syncAll()` — paginated loop calling `syncProduct()` for each
- [ ] Implement `syncProduct()` — check mapping, branch on create vs update
- [ ] Implement create path: Colorway, Media (image), Bases, Inventory records, ExternalIdentifier mappings
- [ ] Implement update path: update Colorway fields, diff variants, retire removed, add new, update prices
- [ ] Implement status mapping (ACTIVE→active, DRAFT→idea, ARCHIVED→retired)
- [ ] Implement Base matching logic (descriptor + account)
- [ ] Implement image sync (skip if no URL, skip if already synced)
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
