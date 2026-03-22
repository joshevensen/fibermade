# Task 02 — Base Variant Push (Upgrade to Bulk Mutations)

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/02-base-variant-push.md`, then implement this task. Work through the checklist before marking done. Don't start Task 03.

---

## Goal

Replace single-call variant mutations (`productVariantCreate`, `productVariantUpdate`, `productVariantDelete`) with their bulk equivalents. Also replace the deprecated `productCreateMedia` in `syncImages()` with `productUpdate` using the `media` parameter.

The existing `SyncBaseToShopifyJob` logic is correct and complete — this task is about upgrading the underlying mutations it calls in `ShopifySyncService`, not changing the job logic.

---

## Current State

`SyncBaseToShopifyJob` handles two actions:

**`action: 'created'`** — when a new base is added:
- Finds all colorways with Shopify product mappings
- For each colorway, creates a new Inventory record + variant via `ShopifySyncService::createVariant()`
- Creates ExternalIdentifier mapping to the new variant GID

**`action: 'updated'`** — when a base changes:
- Finds all inventories for this base with variant mappings
- Calls `ShopifySyncService::updateVariant()` for each

**`SyncBaseDeletedToShopifyJob`** — when a base is retired/deleted:
- Finds all ExternalIdentifiers pointing to Shopify variants for this base's inventories
- Calls `ShopifySyncService::deleteVariant()` for each (individual calls)
- Deletes Inventory records

`ShopifySyncService::syncImages()` currently uses the deprecated `productCreateMedia` mutation.

---

## What to Build

### 1. New: `ShopifySyncService::createVariantsBulk()`

Replace `createVariant()` calls in `SyncBaseToShopifyJob`. Process all colorways for a newly created base in one bulk call per product.

```php
/**
 * Creates variants on an existing Shopify product for multiple inventories.
 * Returns a map of inventory_id => variant_gid.
 *
 * @param  string  $productGid
 * @param  array<array{inventory: Inventory, base: Base, quantity: int}>  $entries
 * @return array<int, string>  [inventory_id => variant_gid]
 */
public function createVariantsBulk(string $productGid, array $entries): array
```

GraphQL mutation:
```graphql
mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkCreate(productId: $productId, variants: $variants) {
    productVariants {
      id
      title
      selectedOptions {
        name
        value
      }
      inventoryItem {
        id
      }
    }
    userErrors {
      field
      message
    }
  }
}
```

Each variant input:
```json
{
  "optionValues": [{ "optionName": "Base", "name": "<base.descriptor>" }],
  "price": "<base.retail_price>",
  "inventoryItem": {
    "cost": "<base.cost>",
    "tracked": true
  },
  "inventoryQuantities": [{
    "locationId": "<defaultLocationGid>",
    "availableQuantity": "<quantity>"
  }]
}
```

Returns map of `inventory_id => variant_gid` so the job can create ExternalIdentifier entries.

### 2. New: `ShopifySyncService::updateVariantsBulk()`

Replace `updateVariant()` calls in `SyncBaseToShopifyJob`. Groups all variant updates for the same product into a single bulk call.

```php
/**
 * Updates multiple variants on a single Shopify product.
 *
 * @param  string  $productGid
 * @param  array<array{variant_gid: string, base: Base}>  $entries
 * @return void
 */
public function updateVariantsBulk(string $productGid, array $entries): void
```

GraphQL mutation:
```graphql
mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkUpdate(productId: $productId, variants: $variants) {
    productVariants {
      id
    }
    userErrors {
      field
      message
    }
  }
}
```

Each variant input:
```json
{
  "id": "<variant_gid>",
  "optionValues": [{ "optionName": "Base", "name": "<base.descriptor>" }],
  "price": "<base.retail_price>"
}
```

### 3. New: `ShopifySyncService::deleteVariantsBulk()`

Replace `deleteVariant()` loop in `SyncBaseDeletedToShopifyJob`.

```php
/**
 * Deletes multiple variants from a single Shopify product.
 *
 * @param  string  $productGid
 * @param  string[]  $variantGids
 * @return void
 */
public function deleteVariantsBulk(string $productGid, array $variantGids): void
```

GraphQL mutation:
```graphql
mutation productVariantsBulkDelete($productId: ID!, $variantsIds: [ID!]!) {
  productVariantsBulkDelete(productId: $productId, variantsIds: $variantsIds) {
    product {
      id
    }
    userErrors {
      field
      message
    }
  }
}
```

**Important:** All variant GIDs passed must belong to the same product. The jobs must group variants by product GID before calling this. If variant GIDs span multiple products, call once per product.

### 4. Fix: `ShopifySyncService::syncImages()` — replace deprecated `productCreateMedia`

Current flow:
1. `deleteExistingProductMedia()` — deletes all existing media via `productDeleteMedia` ✓
2. `createProductMedia()` — uploads new images via `productCreateMedia` ✗ (deprecated)

New flow:
1. `deleteExistingProductMedia()` — unchanged
2. Upload images via `productUpdate` with `media` parameter

```graphql
mutation productUpdate($product: ProductUpdateInput!, $media: [CreateMediaInput!]) {
  productUpdate(product: $product, media: $media) {
    product {
      id
      media(first: 10) {
        edges {
          node {
            id
            status
          }
        }
      }
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
  "product": { "id": "<productGid>" },
  "media": [
    {
      "originalSource": "<image_url>",
      "mediaContentType": "IMAGE",
      "alt": "<colorway.name>"
    }
  ]
}
```

The `originalSource` is the same URL used in the old `productCreateMedia` call — the shape is identical, just the mutation wrapper changes.

---

## Job Updates

### `SyncBaseToShopifyJob` — `handleCreated()` path

Current: calls `ShopifySyncService::createVariant()` per colorway (N API calls).

New: group colorways by product GID, call `createVariantsBulk()` per product (1 API call per product).

```php
// Group by product GID
$grouped = [];
foreach ($colorwaysWithProducts as $colorway) {
    $productGid = $colorway->getExternalIdFor($integration, 'shopify_product');
    $inventory = Inventory::firstOrCreate([...]);
    $grouped[$productGid][] = ['inventory' => $inventory, 'base' => $this->base, 'quantity' => 0];
}

foreach ($grouped as $productGid => $entries) {
    $variantMap = $shopifySyncService->createVariantsBulk($productGid, $entries);
    foreach ($variantMap as $inventoryId => $variantGid) {
        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Inventory::class,
            'identifiable_id' => $inventoryId,
            'external_type' => 'shopify_variant',
            'external_id' => $variantGid,
        ]);
    }
}
```

### `SyncBaseToShopifyJob` — `handleUpdated()` path

Current: calls `ShopifySyncService::updateVariant()` per inventory (N API calls).

New: group by product GID (via colorway mapping), call `updateVariantsBulk()` per product.

### `SyncBaseDeletedToShopifyJob`

Current: calls `ShopifySyncService::deleteVariant()` per variant (N API calls).

New: group variant GIDs by product GID, call `deleteVariantsBulk()` per product.

---

## Files to Touch

| File | Change |
|------|--------|
| `app/Services/Shopify/ShopifySyncService.php` | Add `createVariantsBulk()`, `updateVariantsBulk()`, `deleteVariantsBulk()`. Update `syncImages()`. |
| `app/Jobs/SyncBaseToShopifyJob.php` | Update `handleCreated()` and `handleUpdated()` to use bulk methods |
| `app/Jobs/SyncBaseDeletedToShopifyJob.php` | Update to use `deleteVariantsBulk()` |

Keep the old single-call methods (`createVariant`, `updateVariant`, `deleteVariant`) for now — they may be used by `InventorySyncService::pushAllInventoryForColorway()`. Revisit in Task 05.

---

## Tests

- `createVariantsBulk()` sends correct GraphQL with all variant inputs
- `createVariantsBulk()` returns correct `inventory_id => variant_gid` map
- `updateVariantsBulk()` sends correct GraphQL for all variants
- `deleteVariantsBulk()` sends correct GraphQL for all variant IDs
- `syncImages()` uses `productUpdate` with `media` parameter instead of `productCreateMedia`
- `SyncBaseToShopifyJob` groups variants by product and calls bulk methods
- `SyncBaseDeletedToShopifyJob` groups by product and calls `deleteVariantsBulk()`

---

## Job Retry Configuration

Add to all new and existing push jobs touched in this task:

```php
public int $tries = 3;
public int $backoff = 60; // seconds between retries
```

`ShopifyGraphqlClient` already handles Shopify rate limits internally (429 + Retry-After), but this covers transient network failures and other non-rate-limit errors.

---

## Checklist

- [ ] Add `createVariantsBulk()` to `ShopifySyncService`
- [ ] Add `updateVariantsBulk()` to `ShopifySyncService`
- [ ] Add `deleteVariantsBulk()` to `ShopifySyncService`
- [ ] Update `syncImages()` in `ShopifySyncService` — replace `createProductMedia()` with `productUpdate` + media
- [ ] Update `SyncBaseToShopifyJob::handleCreated()` to group by product and use `createVariantsBulk()`
- [ ] Update `SyncBaseToShopifyJob::handleUpdated()` to group by product and use `updateVariantsBulk()`
- [ ] Update `SyncBaseDeletedToShopifyJob` to group by product and use `deleteVariantsBulk()`
- [ ] Write tests for all new methods and updated job paths
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
