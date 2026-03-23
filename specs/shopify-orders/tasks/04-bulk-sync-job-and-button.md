# Task 04 — Bulk Sync Job + Settings Page Button

## Starting Prompt

> I'm working through the Shopify orders spec at `specs/shopify-orders/`. Please read `specs/shopify-orders/overview.md` and `specs/shopify-orders/tasks/04-bulk-sync-job-and-button.md`, then implement this task. Work through the checklist before marking done. Don't start Task 05.

---

## Goal

Allow creators to manually trigger a pull of all Shopify orders from the last 60 days. This is the fallback for missed webhooks and the mechanism for initial sync on a new integration.

---

## What to Build

### 1. `SyncAllShopifyOrdersJob`

New file: `app/Jobs/SyncAllShopifyOrdersJob.php`

Constructor: accepts `Integration $integration`.

`handle()`:
1. Resolve `ShopifyGraphqlClient` and `ShopifyOrderSyncService` from the container
2. Build the query filter: `created_at:>={60 days ago in ISO 8601}`
3. Paginate through all orders using cursor-based pagination (`first: 50`, then `after: $cursor`)
4. For each order page, call `$service->syncOrder($order['id'], $integration)` per order
5. Continue until `pageInfo.hasNextPage` is false
6. On completion, log a summary to `IntegrationLog` with count of orders synced

Implements `ShouldQueue`. Uses a long timeout (`timeout = 300`) since this may process many orders.

---

### 2. Controller endpoint

Extend `ShopifySyncController` with a new action:

```php
public function syncOrders(Request $request): RedirectResponse
```

- Authorize: user must belong to an account with an active Shopify integration
- Load the integration
- Dispatch `SyncAllShopifyOrdersJob::dispatch($integration)`
- Redirect back with a flash message: "Order sync started. Your orders will appear shortly."

Route: `POST /creator/shopify/sync/orders` — name: `shopify.sync.orders`

Add to `routes/creator.php` inside the existing `shopify` prefix group.

---

### 3. Settings page button

Add a "Sync Orders" button to the existing Shopify settings page (find the correct Vue component — check `resources/js/pages/` for the Shopify settings page).

- Place it alongside the existing sync buttons (follow the same UI pattern)
- Label: "Sync Orders"
- Shows a loading state while the request is in-flight (follow existing sync button pattern)
- On success, shows the flash message

Run `php artisan wayfinder:generate` after adding the route.

---

## Files to Touch

| File | Change |
|---|---|
| `app/Jobs/SyncAllShopifyOrdersJob.php` | New job |
| `app/Http/Controllers/Creator/ShopifySyncController.php` | Add `syncOrders()` action |
| `routes/creator.php` | Add `syncOrders` route |
| Shopify settings Vue page | Add "Sync Orders" button |

---

## Tests

- `SyncAllShopifyOrdersJob` paginates through all orders and calls `syncOrder()` for each
- `SyncAllShopifyOrdersJob` stops paginating when `hasNextPage` is false
- `SyncAllShopifyOrdersJob` uses the correct 60-day date filter
- `SyncAllShopifyOrdersJob` logs a summary to `IntegrationLog` on completion
- `ShopifySyncController::syncOrders()` dispatches `SyncAllShopifyOrdersJob`
- `ShopifySyncController::syncOrders()` redirects back with flash message
- `ShopifySyncController::syncOrders()` returns 403 if no active Shopify integration

---

## Checklist

- [ ] Read `ShopifySyncController` to understand existing action patterns
- [ ] Read the Shopify settings Vue page to understand where to add the button
- [ ] Create `SyncAllShopifyOrdersJob`
- [ ] Add `syncOrders()` to `ShopifySyncController`
- [ ] Add route to `routes/creator.php`
- [ ] Run `php artisan wayfinder:generate`
- [ ] Add "Sync Orders" button to settings page Vue component
- [ ] Write tests
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
