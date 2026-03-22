# Task 04 — Inventory Pull-All Method

## Starting Prompt

> *(This task is implemented in the same session as Tasks 02 and 03. See the Starting Prompt in `tasks/02-product-sync-service.md`.)*



## Goal

The existing `InventorySyncService` already handles pulling a single inventory item from a webhook event. Add a `syncAll()` method that pulls current inventory quantities for all known variant mappings in bulk.

This is distinct from the webhook path — it's a manual "re-sync all inventory" operation.

## Reference

Existing service: `platform/app/Services/InventorySyncService.php`

## What to Build

### New method on `InventorySyncService`

```php
syncAll(Integration $integration): SyncResult
```

**Logic:**
1. Find all `ExternalIdentifier` records for this integration where `external_type = 'shopify_variant'`
2. For each, fetch current inventory from Shopify via `getVariantInventory(variantGid)`
3. Call the existing `pullInventoryFromShopify()` with the fetched quantity
4. Track created/updated/failed counts
5. Return `SyncResult`

### New method on `ShopifyGraphqlClient`

```php
getVariantInventory(string $variantGid): ?int
```

Fetches the current `inventoryQuantity` for a single variant. Small query — just needs the quantity.

## Notes

- This runs after product sync in the orchestrator, so all variant mappings should already exist
- Conflict detection from the existing `pullInventoryFromShopify()` still applies
- Batch the Shopify queries where possible (GraphQL supports aliases for multiple nodes in one request) — but simple sequential calls are acceptable for v1

## Files Likely Affected

- `platform/app/Services/InventorySyncService.php`
- `platform/app/Services/ShopifyGraphqlClient.php`

## Tests

- Test that all variants for an integration are synced
- Test that failed variants don't stop the rest
- Test conflict detection still works

## Checklist

- [ ] Add `syncAll(Integration $integration): SyncResult` to `InventorySyncService`
- [ ] Query all `shopify_variant` ExternalIdentifier records for the integration
- [ ] For each variant, call `getVariantInventory()` on the GraphQL client (added in Task 01)
- [ ] Pass quantity to existing `pullInventoryFromShopify()` — reuse conflict detection
- [ ] Track per-variant success/failure, don't stop on individual failures
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
