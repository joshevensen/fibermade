# Task 01 — Colorway Lifecycle Push (Create + Retire)

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/01-colorway-lifecycle-push.md`, then implement this task. Work through the checklist before marking done. Don't start Task 02.

---

## Goal

When a colorway is **created** in Fibermade, push it to Shopify as a new product with all active variants and inventory. When a colorway is **retired**, archive it in Shopify. Update is already handled.

---

## Current State

- `SyncColorwayCatalogToShopifyJob` exists and handles **update** only — it calls `ShopifySyncService::updateProduct()`.
- `SyncColorwayImagesToShopifyJob` exists and handles image sync — keep as-is.
- `ShopifySyncService::createProduct()` exists and is complete — it creates a Shopify product with all active bases as variants and returns `{ product_id, variant_ids }`.
- `InventorySyncService::pushAllInventoryForColorway()` exists and is complete — it creates the product if missing, creates variants, sets quantities, syncs images.

The **ColorwayObserver** presumably fires on `created` and `updated`. We need to confirm what it dispatches on `created` and add the retire path.

---

## What to Build

### 1. Wire ColorwayObserver `created` event

When a colorway is created:

1. Check if the account has an active Shopify integration with catalog sync enabled.
2. Dispatch `SyncColorwayCatalogToShopifyJob` with action `'created'`.

The job should call `InventorySyncService::pushAllInventoryForColorway()` — this already handles the full create flow: product creation, variant creation, inventory push, image sync, and ExternalIdentifier mapping.

**Note on image sync:** `pushAllInventoryForColorway()` calls `syncImages()` which currently uses the deprecated `productCreateMedia` mutation. This is fixed in Task 02. Deprecated does not mean immediately broken in Shopify, so deploying Task 01 before Task 02 is safe — images will still upload correctly.

### 2. Extend `SyncColorwayCatalogToShopifyJob` to handle `created` and `retired`

The existing job handles **update** only and calls `updateProduct()`. Extend it to accept an `action` parameter (`'created'` | `'updated'`), then add the retire branch.

**On `created`:** Call `InventorySyncService::pushAllInventoryForColorway()`.

**On `updated` (status → `retired`):**

1. Look up the Shopify product GID from ExternalIdentifier (`shopify_product`).
2. If no mapping exists, skip (nothing to archive).
3. Call `ShopifySyncService::archiveProduct()` (new method — see below).
4. Log to IntegrationLog with `operation: 'product_archive'`.

**On `updated` (status → `active` or `idea`):** Call `ShopifySyncService::updateProduct()` as before. This also handles re-activation: if a colorway was previously retired (ARCHIVED in Shopify), changing it back to `active` will call `updateProduct()` with `status: ACTIVE`, restoring it in Shopify.

The existing update job already fires on any colorway update — extend it to branch on action and status.

### 3. New method: `ShopifySyncService::archiveProduct()`

```php
public function archiveProduct(string $productGid): void
```

Calls `productUpdate` mutation with `status: ARCHIVED`.

```graphql
mutation productUpdate($product: ProductUpdateInput!) {
  productUpdate(product: $product) {
    product {
      id
      status
    }
    userErrors {
      field
      message
    }
  }
}
```

Variables:
```json
{
  "product": {
    "id": "<productGid>",
    "status": "ARCHIVED"
  }
}
```

Throw `ShopifyApiException` if `userErrors` is non-empty.

### 4. Status → Shopify status mapping

Ensure `createProduct()` and `updateProduct()` use this mapping consistently:

| Fibermade `ColorwayStatus` | Shopify `status` |
|---------------------------|-----------------|
| `active` | `ACTIVE` |
| `idea` | `DRAFT` |
| `retired` | `ARCHIVED` |

The `archiveProduct()` method hardcodes `ARCHIVED` — no mapping needed there.

---

## Job Flow

### On colorway `created`

```
ColorwayObserver::created()
  └── dispatch SyncColorwayCatalogToShopifyJob(colorway, 'created')
        └── InventorySyncService::pushAllInventoryForColorway(colorway, integration)
              ├── ShopifySyncService::createProduct()        → product GID
              ├── ShopifySyncService::createVariant() × N   → variant GIDs
              ├── InventorySyncService::pushInventoryToShopify() × N
              ├── ShopifySyncService::syncImages()
              └── ExternalIdentifier::create() × (1 product + N variants)
```

### On colorway `updated`

```
ColorwayObserver::updated()
  └── dispatch SyncColorwayCatalogToShopifyJob(colorway, 'updated')
        ├── [if status == retired]          ShopifySyncService::archiveProduct()
        ├── [if status == active or idea]   ShopifySyncService::updateProduct()
        └── (re-activation: retired → active also calls updateProduct() with ACTIVE)
```

---

## Files to Touch

| File | Change |
|------|--------|
| `app/Observers/ColorwayObserver.php` | Add `created()` method dispatching job |
| `app/Jobs/SyncColorwayCatalogToShopifyJob.php` | Add `action` parameter; add `created` path and retire branch |
| `app/Services/Shopify/ShopifySyncService.php` | Add `archiveProduct()` method |

---

## Guard Conditions

All jobs must:

1. Load the integration: `Integration::where('account_id', $colorway->account_id)->where('type', 'shopify')->first()`
2. Return early if no integration or `!$integration->isCatalogSyncEnabled()`
3. Return early if no Shopify config: `$integration->getShopifyConfig() === null`

---

## Tests

- `ColorwayObserver::created()` dispatches `SyncColorwayCatalogToShopifyJob` with action `'created'` when catalog sync is enabled
- `ColorwayObserver::created()` does not dispatch when no Shopify integration exists
- `ColorwayObserver::created()` does not dispatch when catalog sync is disabled
- `SyncColorwayCatalogToShopifyJob` (created path) calls `pushAllInventoryForColorway()`
- `SyncColorwayCatalogToShopifyJob` calls `archiveProduct()` when colorway status is `retired`
- `SyncColorwayCatalogToShopifyJob` calls `updateProduct()` when colorway status is `active`
- `SyncColorwayCatalogToShopifyJob` calls `updateProduct()` when colorway status is `idea`
- `SyncColorwayCatalogToShopifyJob` calls `updateProduct()` with `ACTIVE` when colorway status changes from `retired` back to `active` (re-activation)
- `ShopifySyncService::archiveProduct()` sends correct GraphQL mutation with `status: ARCHIVED`
- `ShopifySyncService::archiveProduct()` throws on `userErrors`

---

## Checklist

- [ ] Read `ColorwayObserver` — confirm what events it currently handles
- [ ] Add `created()` to `ColorwayObserver` — dispatch job with action `'created'` and integration guard
- [ ] Add `action` parameter to `SyncColorwayCatalogToShopifyJob`
- [ ] Add `created` path to job — calls `pushAllInventoryForColorway()`
- [ ] Add retire branch to job — calls `archiveProduct()` when status is `retired`
- [ ] Add `ShopifySyncService::archiveProduct(string $productGid): void`
- [ ] Confirm `createProduct()` and `updateProduct()` use correct Shopify status mapping for all three states
- [ ] Write tests for all paths above including re-activation
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
