# Task 05 — Sync Orchestrator & Queued Jobs

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/05-sync-orchestrator-and-jobs.md`, then implement Task 05 in full. Work through the checklist at the bottom of the task file. Tasks 01–04 are already complete. Don't start Task 06.



## Goal

Create a `ShopifySyncOrchestrator` service that coordinates the three sync services in the correct order, and wrap each sync type in a queued Laravel job so the UI stays responsive during long-running syncs.

## What to Build

### `ShopifySyncOrchestrator`

```php
syncAll(Integration $integration): void
syncProducts(Integration $integration): void
syncCollections(Integration $integration): void
syncInventory(Integration $integration): void
```

Each method dispatches the appropriate job and optionally records that a sync is in progress on the integration.

**`syncAll` order:**
1. Dispatch `SyncShopifyProductsJob`
2. On completion, dispatch `SyncShopifyCollectionsJob` (chained)
3. On completion, dispatch `SyncShopifyInventoryJob` (chained)

Use Laravel job chaining (`Bus::chain()`).

### Jobs

**`SyncShopifyProductsJob`**
- Accepts `Integration $integration`
- Calls `ShopifyProductSyncService::syncAll()`
- Stores result on the integration (see Sync State below)
- Broadcasts progress event

**`SyncShopifyCollectionsJob`**
- Same pattern, calls `ShopifyCollectionSyncService::syncAll()`

**`SyncShopifyInventoryJob`**
- Same pattern, calls `InventorySyncService::syncAll()`

### Sync History

Per-entity results (which colorway synced, which failed, why) are written to the existing `integration_logs` table by the sync services. The settings UI queries this table directly for error detail. No separate history store needed.

### Sync State on Integration

Store only the current/last run summary in `integration.settings` — used for the quick status card in the UI:

```json
{
  "shop": "store.myshopify.com",
  "sync": {
    "status": "idle | running | failed | complete",
    "current_step": "products | collections | inventory | null",
    "started_at": "2026-03-21T...",
    "completed_at": "2026-03-21T...",
    "last_result": {
      "products": { "created": 12, "updated": 3, "failed": 0 },
      "collections": { "created": 5, "updated": 1, "failed": 0 },
      "inventory": { "updated": 15, "failed": 0 }
    },
    "errors": [
      { "step": "products", "gid": "gid://...", "message": "..." }
    ]
  }
}
```

### Preventing Duplicate Runs

Before dispatching, check if a sync is already running (`status === 'running'`). If so, return early or throw a `SyncAlreadyRunningException`.

## Files to Create

- `platform/app/Services/Shopify/ShopifySyncOrchestrator.php`
- `platform/app/Jobs/SyncShopifyProductsJob.php`
- `platform/app/Jobs/SyncShopifyCollectionsJob.php`
- `platform/app/Jobs/SyncShopifyInventoryJob.php`

## Tests

- Test `syncAll` dispatches chained jobs in correct order
- Test duplicate sync prevention
- Test sync state written to integration settings after each step
- Test failed job sets status to `failed`

## Checklist

- [ ] Create `ShopifySyncOrchestrator` service
- [ ] Implement `syncAll()` using `Bus::chain()` with the three jobs in order
- [ ] Implement individual `syncProducts()`, `syncCollections()`, `syncInventory()` methods
- [ ] Add duplicate sync prevention (check `status === 'running'` before dispatching)
- [ ] Create `SyncShopifyProductsJob` — calls `ShopifyProductSyncService::syncAll()`, writes result to integration settings
- [ ] Create `SyncShopifyCollectionsJob` — same pattern
- [ ] Create `SyncShopifyInventoryJob` — same pattern
- [ ] Define the sync state JSON shape on integration settings (status, current_step, started_at, completed_at, last_result, errors)
- [ ] Write sync state to integration before dispatch (`status: running`) and after each job completes
- [ ] Handle job failures — set `status: failed` in integration settings
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
