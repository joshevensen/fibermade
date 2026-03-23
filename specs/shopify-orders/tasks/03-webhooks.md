# Task 03 — Webhook Handlers + Registration

## Starting Prompt

> I'm working through the Shopify orders spec at `specs/shopify-orders/`. Please read `specs/shopify-orders/overview.md` and `specs/shopify-orders/tasks/03-webhooks.md`, then implement this task. Work through the checklist before marking done. Don't start Task 04.

---

## Goal

Register three new Shopify webhook topics and handle them by dispatching a queued sync job for the affected order.

---

## Current State

Read the existing webhook infrastructure before starting — check how `shopify-v2` registered and handled webhooks (`specs/shopify-v2/tasks/08-direct-webhook-handling.md` for context, then look at the actual handler files in `app/Http/Controllers/` or wherever they live). Follow exactly the same pattern.

---

## What to Build

### 1. `SyncShopifyOrderJob`

New file: `app/Jobs/SyncShopifyOrderJob.php`

Constructor: accepts `string $orderGid` and `Integration $integration`.

`handle()`:
1. Resolve `ShopifyOrderSyncService` from the container
2. Call `$service->syncOrder($this->orderGid, $this->integration)`
3. On exception, log to `IntegrationLog` with `status: 'error'` and re-throw

Implements `ShouldQueue`. Uses `tries = 3` and `backoff = [60, 300]`.

---

### 2. Webhook handlers

Three handlers — all follow the same pattern: validate the webhook, extract the order GID, dispatch `SyncShopifyOrderJob`.

**`HandleOrderCreatedWebhook`**
**`HandleOrderUpdatedWebhook`**
**`HandleOrderCancelledWebhook`**

Each handler:
1. Reads the order GID from the webhook payload (`$payload['admin_graphql_api_id']`)
2. Loads the `Integration` from the shop domain (from webhook headers or payload — follow existing pattern)
3. Dispatches `SyncShopifyOrderJob::dispatch($orderGid, $integration)`
4. Returns 200 immediately — all work is queued

All three dispatch the same job. The sync service handles upsert/cancel logic via status mapping.

---

### 3. Register webhook topics

Register three new topics in whatever mechanism the existing infrastructure uses (follow the existing pattern exactly — could be a config array, a registration command, or part of app boot). Topics:

- `orders/create`
- `orders/updated`
- `orders/cancelled`

---

## Files to Touch

| File | Change |
|---|---|
| `app/Jobs/SyncShopifyOrderJob.php` | New job |
| `app/Http/Controllers/.../HandleOrderCreatedWebhook.php` | New handler (follow existing location) |
| `app/Http/Controllers/.../HandleOrderUpdatedWebhook.php` | New handler |
| `app/Http/Controllers/.../HandleOrderCancelledWebhook.php` | New handler |
| Webhook registration file (follow existing pattern) | Register three new topics |
| Routes file (follow existing pattern) | Register webhook routes |

---

## Tests

- `SyncShopifyOrderJob` calls `ShopifyOrderSyncService::syncOrder()` with correct args
- `SyncShopifyOrderJob` logs to `IntegrationLog` on exception
- Each webhook handler dispatches `SyncShopifyOrderJob` with the correct GID
- Each webhook handler returns 200
- Webhook handlers return 401/422 if validation fails (follow existing pattern)

---

## Checklist

- [ ] Read existing webhook handlers to understand the exact pattern used
- [ ] Read existing webhook registration mechanism
- [ ] Create `SyncShopifyOrderJob`
- [ ] Create `HandleOrderCreatedWebhook`
- [ ] Create `HandleOrderUpdatedWebhook`
- [ ] Create `HandleOrderCancelledWebhook`
- [ ] Register topics in webhook config/registration
- [ ] Register routes
- [ ] Write tests
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
