# Task 04 — Inventory Auto-Push

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/04-inventory-auto-push.md`, then implement this task. Work through the checklist before marking done. Don't start Task 05.

---

## Goal

When inventory quantity is changed in Fibermade, automatically push the new value to Shopify. The push service already exists — this task wires it to an observer.

---

## Current State

`InventorySyncService::pushInventoryToShopify(Inventory $inventory, Integration $integration)` is complete and production-ready. It:
1. Looks up the variant GID from ExternalIdentifier (`shopify_variant`)
2. Calls `ShopifySyncService::setVariantInventory()` which uses `inventorySetQuantities`
3. Updates `last_synced_at` and `sync_status = 'synced'` on the Inventory record
4. Logs to IntegrationLog
5. Returns `false` if no variant mapping exists (safe to call even when not connected)

There is also a manual push entry point: `InventoryController::pushToShopify()` which pushes all inventory for an account.

What's missing: an observer that fires automatically when `quantity` changes.

---

## What to Build

### 1. `InventoryObserver`

New observer: `app/Observers/InventoryObserver.php`

```php
class InventoryObserver
{
    public function updated(Inventory $inventory): void
    {
        // Only push if quantity actually changed
        if (! $inventory->wasChanged('quantity')) {
            return;
        }

        // Guard: integration exists and sync is enabled
        $integration = Integration::where('account_id', $inventory->account_id)
            ->where('type', 'shopify')
            ->first();

        if (! $integration || ! $integration->isCatalogSyncEnabled()) {
            return;
        }

        dispatch(new SyncInventoryToShopifyJob($inventory->id, $integration->id));
    }
}
```

**Why `wasChanged('quantity')` matters:** Inventory records are touched in many contexts (sync status updates, `last_synced_at` updates). We must not push to Shopify every time any field changes — only when the actual stock quantity changes.

Register in `AppServiceProvider`:
```php
Inventory::observe(InventoryObserver::class);
```

---

### 2. `SyncInventoryToShopifyJob`

New job: `app/Jobs/SyncInventoryToShopifyJob.php`

Store IDs, not the model — if the inventory record is deleted between dispatch and execution, deserializing a model would throw instead of handling gracefully.

```php
class SyncInventoryToShopifyJob implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $inventoryId,
        public readonly int $integrationId,
    ) {}

    public function handle(InventorySyncService $inventorySync): void
    {
        $integration = Integration::find($this->integrationId);
        $inventory = Inventory::find($this->inventoryId);

        if (! $integration || ! $inventory) {
            return;
        }

        $inventorySync->pushInventoryToShopify(
            $inventory,
            $integration,
            syncSource: 'observer'
        );
    }
}
```

Pass `syncSource: 'observer'` so IntegrationLog records are distinguishable from manual pushes.

---

### 3. Guard against sync echo

`InventorySyncService::pushInventoryToShopify()` updates `last_synced_at` and `sync_status` on the Inventory record. This `updated` call will fire the observer again.

Prevent the loop: the observer already checks `wasChanged('quantity')`. Since the push service only changes `last_synced_at` and `sync_status` — not `quantity` — the observer will return early. No additional guard needed.

Verify this is true by reading `InventorySyncService::pushInventoryToShopify()` — confirm it does not touch `quantity`. If it does, add a `withoutObservers()` wrapper.

---

### 4. `SyncInventoryToShopifyJob` should also handle new inventory records

When a new Inventory record is created (e.g., a new base is added to a colorway with an existing Shopify product), `pushInventoryToShopify()` returns `false` because there's no variant GID mapping yet — the variant hasn't been created in Shopify yet.

This case is handled by `SyncBaseToShopifyJob::handleCreated()` (Task 02) which creates the variant in Shopify and the ExternalIdentifier mapping. The inventory push can then succeed on the next cycle.

Do **not** add a `created` observer for Inventory — new inventory records are created by `SyncBaseToShopifyJob` and `InventorySyncService::pushAllInventoryForColorway()`, both of which handle Shopify immediately. An observer would create a race condition.

---

## Files to Create

| File | Purpose |
|------|---------|
| `app/Observers/InventoryObserver.php` | New observer |
| `app/Jobs/SyncInventoryToShopifyJob.php` | New job |

## Files to Touch

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Register `InventoryObserver` |

---

## Tests

- `InventoryObserver::updated()` dispatches `SyncInventoryToShopifyJob` when `quantity` changes
- `InventoryObserver::updated()` does not dispatch when `quantity` did not change (e.g., only `last_synced_at` changed)
- `InventoryObserver::updated()` does not dispatch when no Shopify integration exists
- `InventoryObserver::updated()` does not dispatch when catalog sync is disabled
- `SyncInventoryToShopifyJob::handle()` calls `pushInventoryToShopify()` with correct arguments
- `SyncInventoryToShopifyJob::handle()` returns early if integration no longer exists
- `SyncInventoryToShopifyJob::handle()` returns early if inventory record no longer exists

---

## Checklist

- [ ] Read `InventorySyncService::pushInventoryToShopify()` — confirm it does not update `quantity` field
- [ ] Create `InventoryObserver` — fire only on `quantity` change
- [ ] Create `SyncInventoryToShopifyJob`
- [ ] Register `InventoryObserver` in `AppServiceProvider`
- [ ] Write tests for all cases above
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
