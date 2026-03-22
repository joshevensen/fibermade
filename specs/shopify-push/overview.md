# Shopify Push — Overview

## Goal

Make Fibermade the single source of truth for all catalog data. Every create, update, and delete in Fibermade is automatically mirrored to Shopify. Shopify becomes a read-only storefront.

This is the complement to the shopify-v2 migration (which pulled from Shopify into Fibermade). This spec inverts the flow: Fibermade pushes out.

---

## Current State

The shopify-v2 migration is complete. The following push infrastructure already exists and is production-ready:

| What | Where | Status |
|------|-------|--------|
| `ShopifySyncService::createProduct()` | `app/Services/Shopify/ShopifySyncService.php` | Complete |
| `ShopifySyncService::updateProduct()` | same | Complete |
| `ShopifySyncService::createVariant()` | same | Complete — single-call, not bulk |
| `ShopifySyncService::updateVariant()` | same | Complete — single-call, not bulk |
| `ShopifySyncService::deleteVariant()` | same | Complete |
| `ShopifySyncService::syncImages()` | same | Complete — uses deprecated `productCreateMedia` |
| `ShopifySyncService::setVariantInventory()` | same | Complete |
| `InventorySyncService::pushInventoryToShopify()` | `app/Services/InventorySyncService.php` | Complete |
| `InventorySyncService::pushAllInventoryForColorway()` | same | Complete |
| `SyncColorwayCatalogToShopifyJob` | `app/Jobs/` | Complete — handles **update** only |
| `SyncColorwayImagesToShopifyJob` | `app/Jobs/` | Complete |
| `SyncBaseToShopifyJob` | `app/Jobs/` | Complete — handles create + update |
| `SyncBaseDeletedToShopifyJob` | `app/Jobs/` | Complete |

**What's missing:**
- Colorway **create** push (new colorways aren't pushed to Shopify)
- Colorway **retire** push (status change to Archived isn't propagated)
- Collection push — entirely absent (no `collectionCreate`, `collectionUpdate`, or product-membership sync)
- Inventory **auto-push** on quantity change (only manual push exists)
- Full catalog initial push (for new integrations or re-sync)
- `productCreateMedia` is deprecated — image sync needs updating

---

## Architecture

```
[Fibermade Platform]
    │
    ├── ColorwayObserver ──────────────────> SyncColorwayToShopifyJob
    │   (created, updated, retired)              │
    │                                            ├── ShopifySyncService::createProduct()
    │                                            ├── ShopifySyncService::updateProduct()
    │                                            └── ShopifySyncService::archiveProduct()
    │
    ├── ColorwayObserver ──────────────────> SyncColorwayImagesToShopifyJob (existing)
    │   (media changed)                          │
    │                                            └── ShopifySyncService::syncImages()
    │
    ├── BaseObserver (existing) ───────────> SyncBaseToShopifyJob
    │   (created, updated, retired)              │
    │                                            ├── productVariantsBulkCreate (new)
    │                                            └── productVariantsBulkUpdate (new)
    │
    ├── CollectionObserver (new) ──────────> SyncCollectionToShopifyJob (new)
    │   (created, updated, deleted)              │
    │                                            └── ShopifyCollectionPushService (new)
    │
    └── InventoryObserver (new) ───────────> SyncInventoryToShopifyJob (new)
        (quantity changed)                       │
                                                 └── InventorySyncService::pushInventoryToShopify()
```

**Key principle:** Every observer dispatches a queued job. Jobs handle all GraphQL calls. Observers never call services directly.

---

## Shopify API Mutations Used

| Operation | Mutation | Notes |
|-----------|----------|-------|
| Create product | `productCreate` | Passes all active bases as variants |
| Update product | `productUpdate` | Title, description, status, tags only |
| Add variants | `productVariantsBulkCreate` | Replaces single `productVariantCreate` |
| Update variants | `productVariantsBulkUpdate` | Replaces single `productVariantUpdate` |
| Delete variants | `productVariantsBulkDelete` | Replaces single `productVariantDelete` |
| Sync images | `productDeleteMedia` + `productUpdate` (with `media`) | Replaces deprecated `productCreateMedia` |
| Set inventory | `inventorySetQuantities` | Existing — no change |
| Create collection | `collectionCreate` | New |
| Update collection | `collectionUpdate` | New |
| Sync collection products | `collectionAddProductsV2` | New — async, returns a Job |

---

## Key Decisions

### Fibermade is the authority — pull sync is disabled

The `ShopifyProductSyncService` (pull: Shopify → Fibermade) and `ShopifyCollectionSyncService` (pull) are disabled as automatic operations. They may be kept as admin-only import tools for migrating existing Shopify catalogs into a fresh Fibermade account, but they must not run automatically once Fibermade is the source of truth.

Webhook handlers for `products/create`, `products/update`, `products/delete`, `collections/create`, `collections/update`, `collections/delete` are removed or no-op'd. Shopify firing these back after a Fibermade push would create an echo loop.

Inventory pull (`InventorySyncService::syncAll()`) is also disabled as an automatic operation. Inventory is managed in Fibermade. Shopify sales reduce inventory via order webhooks (separate concern, not part of this spec).

### Status mapping (Fibermade → Shopify)

| Fibermade | Shopify |
|-----------|---------|
| `active` | `ACTIVE` |
| `idea` | `DRAFT` |
| `retired` | `ARCHIVED` |

### Images

`productCreateMedia` is deprecated. Replace with `productUpdate` using the `media` parameter (same `CreateMediaInput` shape, same `originalSource` URL field). The delete step (`productDeleteMedia`) remains unchanged.

### Collection product membership

`collectionAddProductsV2` is asynchronous — it returns a Shopify Job, not immediate confirmation. The job should log a "pending" status and not wait. The next sync cycle will confirm membership. No polling required.

### Variant bulk mutations

Replace `productVariantCreate` and `productVariantUpdate` (single-call) with `productVariantsBulkCreate` and `productVariantsBulkUpdate`. This reduces API calls when a base affects many colorways and keeps us under rate limits.

### ExternalIdentifier types

| Model | `external_type` |
|-------|----------------|
| Colorway | `shopify_product` |
| Inventory | `shopify_variant` |
| Collection | `shopify_collection` |

---

## Tasks

| # | Task | Builds On |
|---|------|-----------|
| 01 | Colorway lifecycle push (create + retire) | Existing update job |
| 02 | Base variant push — upgrade to bulk mutations | Existing base jobs |
| 03 | Collection push (new) | Task 01 must be done first |
| 04 | Inventory auto-push | Existing push service |
| 05 | Full catalog push + disable pull sync | All prior tasks |
| 06 | Error notification (Sentry + user banner) | All prior tasks |

---

## How to Work Through This

Each task runs in a **separate chat session**. Paste the **Starting Prompt** from each task file into a fresh chat.

| Session | Task(s) | Notes |
|---------|---------|-------|
| 1 | Task 01 | Colorway create/retire — self-contained |
| 2 | Task 02 | Base bulk mutations — self-contained |
| 3 | Task 03 | Collection push — depends on colorways having Shopify mappings (Task 01 live) |
| 4 | Task 04 | Inventory observer — self-contained |
| 5 | Task 05 | Full push + disable pull sync — depends on all prior tasks |
| 6 | Task 06 | Error notification — can run in parallel with Tasks 01–04, but all jobs must exist first |
