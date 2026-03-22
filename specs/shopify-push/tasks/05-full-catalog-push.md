# Task 05 — Full Catalog Push + Disable Pull Sync

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/05-full-catalog-push.md`, then implement this task. Work through the checklist before marking done.

---

## Goal

1. Add a "Push All to Shopify" action that pushes the entire Fibermade catalog to Shopify in sequence: colorways → collections → inventory.
2. Disable automatic pull sync (Shopify → Fibermade) for products and collections — Fibermade is now the source of truth.

---

## Prerequisites

Tasks 01–04 must be complete and deployed before this task. Full push depends on the per-entity push jobs working correctly.

---

## ⚠️ Inventory Warning

A full catalog push **overwrites inventory quantities in Shopify** with whatever Fibermade currently holds. This is safe when Fibermade quantities are current. It is risky if:

- A customer placed an order on Shopify recently and that purchase hasn't flowed back into Fibermade yet
- Inventory was adjusted directly in Shopify

**Mitigation:** The UI should make this explicit. Show a warning on the button:

> "This will overwrite all product data and inventory quantities in your Shopify store with current Fibermade values. Make sure your inventory is up to date before pushing."

Consider adding a checkbox the user must confirm before the push runs. Alternatively, offer two buttons: "Push Products & Collections" (safe, no inventory) and "Push Everything Including Inventory" (with the warning). Implement whichever feels right — the job supports both via a flag.

---

## Part A: Full Catalog Push

### 1. `PushCatalogToShopifyJob`

New job: `app/Jobs/PushCatalogToShopifyJob.php`

Pushes the entire catalog in order. Runs as a queued job — this can take several minutes for large catalogs.

```php
class PushCatalogToShopifyJob implements ShouldQueue
{
    public $timeout = 600; // 10 minutes

    public function __construct(
        public readonly int $integrationId,
    ) {}

    public function handle(
        InventorySyncService $inventorySync,
        ShopifyCollectionPushService $collectionPush,
    ): void {
        $integration = Integration::with('account')->find($this->integrationId);

        if (! $integration) {
            return;
        }

        $this->updateSyncState($integration, 'running', 'colorways');

        // Step 1: Push all colorways (products + variants + images + inventory)
        $colorwayResult = $this->pushColorways($integration, $inventorySync);

        $this->updateSyncState($integration, 'running', 'collections');

        // Step 2: Push all collections (depends on colorways having Shopify mappings)
        $collectionResult = $this->pushCollections($integration, $collectionPush);

        $this->updateSyncState($integration, 'complete', null, [
            'colorways'   => $colorwayResult,
            'collections' => $collectionResult,
        ]);
    }
}
```

#### `pushColorways()`

For **all colorways** in the account (all statuses — active, idea, retired):
1. Call `InventorySyncService::pushAllInventoryForColorway()` — handles create-or-update, and `createProduct()` uses the correct status mapping so retired colorways push as ARCHIVED and idea colorways push as DRAFT
2. Catch exceptions per colorway — log error, continue

Returns `SyncResult` (or equivalent array with `created`, `updated`, `failed` counts).

The job accepts an `$includeInventory` flag (default `true`). When `false`, skip the inventory quantity push step — see the inventory warning above.

#### `pushCollections()`

For each collection in the account:
1. Check if it has a Shopify collection mapping
2. If yes: call `ShopifyCollectionPushService::updateCollection()` + `syncCollectionProducts()`
3. If no: call `ShopifyCollectionPushService::createCollection()` + `syncCollectionProducts()`
4. Catch exceptions per collection — log error, continue

Returns counts.

### 2. Sync state

Reuse the existing pattern from `ShopifySyncOrchestrator` — store progress in `integration.settings['push_sync']`:

```json
{
  "status": "running | complete | failed",
  "current_step": "colorways | collections | null",
  "started_at": "2026-03-22T10:00:00Z",
  "completed_at": null,
  "last_result": {
    "colorways": { "created": 12, "updated": 3, "failed": 0 },
    "collections": { "created": 4, "updated": 1, "failed": 0 }
  },
  "errors": []
}
```

Use a separate key (`push_sync`) from the existing `sync` key (which tracks pull sync state) to avoid collision.

### 3. Controller action

Add to the existing `ShopifySyncController` (or a new `ShopifyPushController`):

```php
public function pushAll(Integration $integration): JsonResponse
{
    $this->authorize('update', $integration);

    if ($integration->getPushSyncStatus() === 'running') {
        return response()->json(['error' => 'Push already running'], 409);
    }

    PushCatalogToShopifyJob::dispatch($integration->id);

    return response()->json(['status' => 'started'], 202);
}
```

Add route: `POST /creator/shopify/push/all`

### 4. UI — Push button on settings page

Add to the Shopify tab on `/creator/settings?tab=shopify-api`:

- **"Push All to Shopify" button** — visible only when connected. Triggers `POST /creator/shopify/push/all`.
- While running: show progress (same polling pattern as pull sync — poll `GET /creator/shopify/sync/status`).
- On complete: show result summary ("12 colorways pushed, 4 collections pushed, 0 errors").
- On error: show failed count with expandable details from IntegrationLog.

The existing sync status endpoint should be extended to include `push_sync` state alongside the existing `sync` state.

---

## Part B: Disable Pull Sync

Fibermade is now the source of truth. Pull sync (Shopify → Fibermade) for products and collections must be disabled to prevent Shopify from overwriting Fibermade data.

### 1. Remove pull sync from orchestrator

In `ShopifySyncOrchestrator`:
- `syncProducts()` — disable or guard with a deprecation notice. Do not delete yet (may be needed for admin import).
- `syncCollections()` — same.
- `syncAll()` — remove products and collections steps. If called, only run inventory (if still relevant).

Or: gate all pull sync operations behind a new setting `allow_pull_sync` (default `false`) so they can be re-enabled for admin use without a code deploy.

### 2. Remove webhook handling for products and collections

In `ShopifyWebhookController` (or wherever product/collection webhooks are handled):

- `products/create` webhook → return 200, take no action
- `products/update` webhook → return 200, take no action
- `products/delete` webhook → return 200, take no action
- `collections/create` webhook → return 200, take no action
- `collections/update` webhook → return 200, take no action
- `collections/delete` webhook → return 200, take no action

**Do not unregister these webhooks from Shopify.** Returning 200 with no action is safer — Shopify stops retrying, no errors appear in the partner dashboard, and the webhook handler can be re-enabled later if needed.

**Keep:** `inventory_levels/update` webhook — inventory changes from customer purchases on Shopify need to flow back into Fibermade. This is the one remaining pull direction.

### 3. Remove pull sync buttons from UI

On the Shopify settings tab, remove or disable the pull sync buttons:
- "Sync Products" (pull from Shopify)
- "Sync Collections" (pull from Shopify)
- "Sync All" (pull from Shopify)

These are replaced by the new "Push All to Shopify" button from Part A.

Keep: "Sync Inventory" may still be relevant for reconciling Shopify-originated inventory changes (customer purchases). If unsure, keep it but label it clearly: "Reconcile Inventory from Shopify (import quantities from customer purchases)."

---

## Files to Create

| File | Purpose |
|------|---------|
| `app/Jobs/PushCatalogToShopifyJob.php` | Full catalog push job |

## Files to Touch

| File | Change |
|------|--------|
| `app/Http/Controllers/Creator/ShopifySyncController.php` | Add `pushAll()` action |
| `app/Services/Shopify/ShopifySyncOrchestrator.php` | Gate or remove pull sync methods |
| `app/Http/Controllers/ShopifyWebhookController.php` | No-op product/collection webhook handlers |
| `routes/creator.php` | Add `POST /shopify/push/all` route |
| `resources/js/pages/creator/settings/ShopifyTab.vue` (or equivalent) | Add push button, remove pull buttons |

---

## Tests

- `PushCatalogToShopifyJob` calls `pushAllInventoryForColorway()` for each active colorway
- `PushCatalogToShopifyJob` calls collection push for each collection
- `PushCatalogToShopifyJob` continues after per-colorway failures (doesn't abort)
- `PushCatalogToShopifyJob` updates sync state to `running` then `complete`
- `pushAll()` controller action returns 409 if push already running
- `pushAll()` dispatches `PushCatalogToShopifyJob`
- Product/collection webhook handlers return 200 with no side effects

---

## Checklist

- [ ] Create `PushCatalogToShopifyJob` — colorways step
- [ ] Create `PushCatalogToShopifyJob` — collections step
- [ ] Add sync state management (`push_sync` key in integration settings)
- [ ] Add `pushAll()` to sync controller
- [ ] Add route for `POST /creator/shopify/push/all`
- [ ] Add `Integration::getPushSyncStatus()` helper method
- [ ] Gate or remove pull sync in `ShopifySyncOrchestrator`
- [ ] No-op product/collection webhook handlers
- [ ] Update settings page UI — add push button, update/remove pull buttons
- [ ] Extend status endpoint to include `push_sync` state
- [ ] Write all tests
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
