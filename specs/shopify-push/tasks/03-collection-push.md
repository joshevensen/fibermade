# Task 03 — Collection Push (New)

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/03-collection-push.md`, then implement this task. Work through the checklist before marking done. Don't start Task 04.

---

## Goal

When a collection is created, updated, or deleted in Fibermade, mirror that change to Shopify. This includes syncing which colorways (products) belong to the collection.

This is entirely new — no collection push infrastructure exists yet. Only pull (Shopify → Fibermade) was implemented in the shopify-v2 migration.

---

## Prerequisites

Task 01 must be deployed. Colorways need Shopify product GIDs in `external_identifiers` before collection membership can be pushed. If a colorway in a collection has no Shopify mapping, skip it and log a warning.

---

## What to Build

### 1. `ShopifyCollectionPushService`

New service: `app/Services/Shopify/ShopifyCollectionPushService.php`

```php
class ShopifyCollectionPushService
{
    public function __construct(
        private ShopifyGraphqlClient $client,
    ) {}

    public function createCollection(Collection $collection, Integration $integration): string // returns collection GID
    public function updateCollection(Collection $collection, string $collectionGid): void
    public function syncCollectionProducts(Collection $collection, string $collectionGid, Integration $integration): void
    public function deleteCollection(string $collectionGid): void
}
```

#### `createCollection()`

Creates a custom collection in Shopify. Returns the new collection GID.

```graphql
mutation collectionCreate($input: CollectionInput!) {
  collectionCreate(input: $input) {
    collection {
      id
      title
      handle
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
  "input": {
    "title": "<collection.name>",
    "descriptionHtml": "<collection.description ?? ''>"
  }
}
```

After creating, call `syncCollectionProducts()` to populate membership.

Store mapping in `ExternalIdentifier`:
```php
ExternalIdentifier::create([
    'integration_id'    => $integration->id,
    'identifiable_type' => Collection::class,
    'identifiable_id'   => $collection->id,
    'external_type'     => 'shopify_collection',
    'external_id'       => $collectionGid,
    'data'              => ['handle' => $response['handle']],
]);
```

Throw `ShopifyApiException` if `userErrors` is non-empty.

#### `updateCollection()`

Updates title and description only.

```graphql
mutation collectionUpdate($input: CollectionInput!) {
  collectionUpdate(input: $input) {
    collection {
      id
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
  "input": {
    "id": "<collectionGid>",
    "title": "<collection.name>",
    "descriptionHtml": "<collection.description ?? ''>"
  }
}
```

Throw `ShopifyApiException` if `userErrors` is non-empty.

#### `syncCollectionProducts()`

Resolves colorway → Shopify product GIDs, then calls `collectionAddProductsV2`.

```php
public function syncCollectionProducts(Collection $collection, string $collectionGid, Integration $integration): void
{
    $colorways = $collection->colorways()->with('externalIdentifiers')->get();

    $productGids = $colorways
        ->map(fn($c) => $c->getExternalIdFor($integration, 'shopify_product'))
        ->filter()
        ->values()
        ->all();

    if (empty($productGids)) {
        return; // nothing to sync
    }

    $this->addProductsToCollection($collectionGid, $productGids);
}
```

`collectionAddProductsV2` is **asynchronous** — Shopify returns a job reference, not immediate confirmation. Log the operation as success when the mutation returns without user errors. Do not wait for or poll the Shopify job.

```graphql
mutation collectionAddProductsV2($id: ID!, $productIds: [ID!]!) {
  collectionAddProductsV2(id: $id, productIds: $productIds) {
    job {
      id
      done
    }
    userErrors {
      field
      message
    }
  }
}
```

**Note:** `collectionAddProductsV2` **adds** products but does not remove them. Removals require a separate REST call — see `removeProductsFromCollection()` below.

If any colorways in the collection have no Shopify product mapping, log a warning to IntegrationLog and continue.

#### `removeProductsFromCollection()`

Removes specific products from a collection via the Shopify REST API. There is no GraphQL mutation for this — `collectionAddProductsV2` only adds.

```php
public function removeProductsFromCollection(string $collectionGid, array $productGids): void
```

Use `DELETE /admin/api/2025-01/collects.json` — each "collect" is a join record between a collection and a product. The flow:

1. `GET /admin/api/2025-01/collects.json?collection_id={id}` — fetch all collect records for the collection
2. Filter to those whose `product_id` matches the GIDs being removed (convert GID to numeric ID)
3. `DELETE /admin/api/2025-01/collects/{collect_id}.json` for each

Add a `restGet(string $path): array` and `restDelete(string $path): void` method to `ShopifyGraphqlClient` (or a thin REST wrapper alongside it). Extract the numeric ID from a GID with: `(int) str_replace('gid://shopify/Product/', '', $gid)`.

This method is called from `syncCollectionProducts()` after resolving which products were removed from the collection (see pivot sync below).

#### `syncCollectionProducts()` — updated to handle removals

The method needs to know which products to add and which to remove. Update the signature:

```php
public function syncCollectionProducts(
    Collection $collection,
    string $collectionGid,
    Integration $integration,
    array $removedColorwayIds = [],
): void
```

- Resolve current colorway GIDs → call `collectionAddProductsV2` for products to add
- Resolve `$removedColorwayIds` → their product GIDs → call `removeProductsFromCollection()`

The job passes the removed IDs, which it calculates by comparing the previous and new pivot state (see pivot sync section below).

#### `deleteCollection()`

Delete the collection from Shopify via REST:

```
DELETE /admin/api/2025-01/custom_collections/{id}.json
```

Convert the collection GID to a numeric ID: `str_replace('gid://shopify/Collection/', '', $gid)`.

Add `restDelete()` to the HTTP client if it doesn't exist (same client used for `removeProductsFromCollection`).

The ExternalIdentifier mapping is deleted after a successful Shopify delete.

---

### 2. `CollectionObserver`

New observer: `app/Observers/CollectionObserver.php`

```php
class CollectionObserver
{
    public function created(Collection $collection): void
    public function updated(Collection $collection): void
    public function deleted(Collection $collection): void
}
```

Each method:
1. Find the Shopify integration for `$collection->account_id`
2. Return early if no integration or catalog sync not enabled
3. Dispatch the appropriate job

Register in `AppServiceProvider` (or wherever observers are registered):
```php
Collection::observe(CollectionObserver::class);
```

**Note on colorway membership changes:** The `collections()` pivot on Colorway is not observed by `CollectionObserver`. When colorways are added to or removed from a collection (via the pivot), that change must also sync. See below.

---

### 3. Jobs

#### `SyncCollectionToShopifyJob`

```php
class SyncCollectionToShopifyJob implements ShouldQueue
{
    public function __construct(
        public readonly Collection $collection,
        public readonly string $action, // 'created' | 'updated'
    ) {}
}
```

**`created` path:**
1. Guard checks (integration exists, catalog sync enabled)
2. Call `ShopifyCollectionPushService::createCollection()`
3. Call `ShopifyCollectionPushService::syncCollectionProducts()`
4. Log Success to IntegrationLog (`operation: 'collection_create'`)

**`updated` path:**
1. Guard checks
2. Look up collection GID from ExternalIdentifier
3. If no mapping: treat as `created` path (create it now)
4. Call `ShopifyCollectionPushService::updateCollection()`
5. Call `ShopifyCollectionPushService::syncCollectionProducts()`
6. Log Success to IntegrationLog (`operation: 'collection_update'`)

#### `SyncCollectionDeletedToShopifyJob`

```php
class SyncCollectionDeletedToShopifyJob implements ShouldQueue
{
    public function __construct(
        public readonly int $collectionId,
        public readonly int $accountId,
    ) {}
}
```

Uses IDs (not the model) because the record is deleted by the time the job runs.

1. Guard checks
2. Find ExternalIdentifier for this collection
3. If none, return (nothing in Shopify)
4. Call `ShopifyCollectionPushService::deleteCollection()`
5. Delete the ExternalIdentifier record
6. Log to IntegrationLog

---

### 4. Sync collection membership when colorways are added/removed

The many-to-many pivot between collections and colorways changes when:
- A colorway is added to a collection in the UI
- A colorway is removed from a collection in the UI

These changes go through the `colorway_collection` pivot table, not through the Collection model's `updated` event.

Find where `$collection->colorways()->sync(...)` is called (likely in a `CollectionController`). Before calling `sync()`, capture the current colorway IDs so you can diff them:

```php
$previousIds = $collection->colorways()->pluck('colorways.id')->all();
$newIds = $request->validated('colorway_ids', []);

$collection->colorways()->sync($newIds);

$removedIds = array_diff($previousIds, $newIds);

dispatch(new SyncCollectionToShopifyJob($collection, 'updated', $removedIds));
```

Update `SyncCollectionToShopifyJob` to accept and forward `$removedColorwayIds` to `syncCollectionProducts()`.

---

## Files to Create

| File | Purpose |
|------|---------|
| `app/Services/Shopify/ShopifyCollectionPushService.php` | New push service |
| `app/Observers/CollectionObserver.php` | New observer |
| `app/Jobs/SyncCollectionToShopifyJob.php` | New job |
| `app/Jobs/SyncCollectionDeletedToShopifyJob.php` | New job |

## Files to Touch

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Register `CollectionObserver` |
| `app/Http/Controllers/Creator/CollectionController.php` (or equivalent) | Dispatch push job after `colorways()->sync()` |
| `app/Services/Shopify/ShopifyGraphqlClient.php` | Add REST DELETE helper if needed for `deleteCollection()` |

---

## ExternalIdentifier

Collections use:
```php
[
    'external_type' => 'shopify_collection',
    'external_id'   => $collectionGid, // e.g. "gid://shopify/Collection/12345"
]
```

---

## IntegrationLog Operations

| Event | `operation` value |
|-------|------------------|
| Collection created in Shopify | `'collection_create'` |
| Collection updated in Shopify | `'collection_update'` |
| Collection deleted in Shopify | `'collection_delete'` |
| Products synced to collection | `'collection_products_sync'` |
| Colorway skipped (no Shopify mapping) | Warning: `'collection_products_sync'` |

---

## Tests

- `CollectionObserver::created()` dispatches `SyncCollectionToShopifyJob` with action `'created'`
- `CollectionObserver::updated()` dispatches `SyncCollectionToShopifyJob` with action `'updated'`
- `CollectionObserver::deleted()` dispatches `SyncCollectionDeletedToShopifyJob`
- Observers do not dispatch when no Shopify integration or sync disabled
- `ShopifyCollectionPushService::createCollection()` sends correct `collectionCreate` mutation
- `ShopifyCollectionPushService::createCollection()` creates ExternalIdentifier after success
- `ShopifyCollectionPushService::updateCollection()` sends correct `collectionUpdate` mutation
- `ShopifyCollectionPushService::syncCollectionProducts()` resolves colorway GIDs and calls `collectionAddProductsV2`
- `ShopifyCollectionPushService::syncCollectionProducts()` calls `removeProductsFromCollection()` for removed colorways
- `ShopifyCollectionPushService::syncCollectionProducts()` skips colorways with no Shopify mapping and logs warning
- `ShopifyCollectionPushService::syncCollectionProducts()` returns early when no product GIDs resolved
- `ShopifyCollectionPushService::removeProductsFromCollection()` fetches collects and deletes matching ones via REST
- `SyncCollectionToShopifyJob` (created path) creates collection and syncs products
- `SyncCollectionToShopifyJob` (updated path) creates collection if no mapping exists
- `SyncCollectionToShopifyJob` passes removed colorway IDs to `syncCollectionProducts()`
- `SyncCollectionDeletedToShopifyJob` deletes collection and removes ExternalIdentifier

---

## Job Retry Configuration

```php
public int $tries = 3;
public int $backoff = 60;
```

Add to `SyncCollectionToShopifyJob` and `SyncCollectionDeletedToShopifyJob`.

---

## Checklist

- [ ] Create `ShopifyCollectionPushService`
- [ ] Implement `createCollection()` with `collectionCreate` GraphQL mutation
- [ ] Implement `updateCollection()` with `collectionUpdate` GraphQL mutation
- [ ] Implement `syncCollectionProducts()` with `collectionAddProductsV2` + `removeProductsFromCollection()`
- [ ] Add `restGet()` and `restDelete()` helpers to `ShopifyGraphqlClient` (or a wrapper)
- [ ] Implement `removeProductsFromCollection()` using REST collects API
- [ ] Implement `deleteCollection()` using REST DELETE
- [ ] Create `CollectionObserver` and register it
- [ ] Create `SyncCollectionToShopifyJob` (created + updated paths, accepts `$removedColorwayIds`)
- [ ] Create `SyncCollectionDeletedToShopifyJob`
- [ ] Add retry config to both jobs
- [ ] Find where colorway↔collection pivot is synced — capture removed IDs before `sync()`, dispatch job with them
- [ ] Write all tests listed above
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
