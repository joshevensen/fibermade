# Task 03 — Collection Sync Service (Laravel)

## Starting Prompt

> *(This task is implemented in the same session as Tasks 02 and 04. See the Starting Prompt in `tasks/02-product-sync-service.md`.)*



## Goal

Port the TypeScript `CollectionSyncService` to a new Laravel `ShopifyCollectionSyncService`. This service pulls collections from Shopify and creates or updates Collection records in Fibermade, correctly assigning the colorways that belong to each collection.

**Note:** This task depends on Task 02. Collections reference products, which must already be mapped to Colorways before collection sync runs. The orchestrator (Task 05) ensures the correct order.

**Collection exclusions:** No opt-out list. Skip collections with status `ARCHIVED`. Creators archive anything they don't want synced — no UI needed.

## Reference

TypeScript source: `shopify/app/services/sync/collection-sync.server.ts`

## What to Build

### `ShopifyCollectionSyncService`

**Main method:**
```php
syncAll(Integration $integration): SyncResult
```

**Per-collection method:**
```php
syncCollection(array $shopifyCollection, Integration $integration): CollectionSyncResult
```

### Import Logic (per collection)

1. Fetch the collection's products (paginated via `getCollectionProducts`)
2. Map each product GID → Colorway ID via `external_identifiers` lookups
3. Skip products that have no mapping (not yet synced or excluded)
4. Check if a mapping already exists (`shopify_collection` → `Collection`)
5. **If no mapping** → create new Collection with those colorways
6. **If mapping exists** → update Collection name/description, sync colorway assignments (add new, remove dropped)

### Colorway Assignment Sync

When updating an existing collection:
- Colorways in Shopify collection but not in Fibermade collection → add
- Colorways in Fibermade collection but not in Shopify collection → remove
- Don't touch colorways that are already correctly assigned

## Files to Create

- `platform/app/Services/Shopify/ShopifyCollectionSyncService.php`

## Tests

- Test collection created with correct colorways
- Test collection updated (colorways added and removed correctly)
- Test products with no mapping are gracefully skipped
- Test archived collections are skipped
- Test empty collection

## Checklist

- [ ] Create `ShopifyCollectionSyncService` class
- [ ] Implement `syncAll()` — paginated loop, skip archived collections
- [ ] Implement `syncCollection()` — fetch products, map GIDs to Colorway IDs
- [ ] Implement create path: create Collection with mapped colorways
- [ ] Implement update path: diff colorway assignments, add new, remove dropped
- [ ] Handle products with no mapping gracefully (skip, don't fail)
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
