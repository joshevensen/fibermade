# Task 08 — Direct Webhook Handling on Platform

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/08-direct-webhook-handling.md`, then implement Task 08 in full. Work through the checklist at the bottom of the task file. Tasks 01–07 are already complete. Don't start Task 09.



## Goal

Extend `ShopifyWebhookController` to handle product and collection webhooks directly from Shopify. Currently these webhooks hit the Shopify app's TypeScript routes, which process them and forward to Fibermade. After this task, Fibermade receives them directly.

This task also requires updating the Shopify app to register the new webhook URLs pointing at Fibermade instead of at itself.

## Current State

`ShopifyWebhookController.php` already handles `inventory_levels/update`. It verifies HMAC signatures and processes the payload. The same pattern applies to product and collection webhooks.

## Webhooks to Handle

| Topic | Action |
|-------|--------|
| `products/create` | Create colorway from new product |
| `products/update` | Update existing colorway |
| `products/delete` | Retire/flag colorway |
| `collections/create` | Create collection |
| `collections/update` | Update collection |
| `collections/delete` | Remove/flag collection |

## New Routes

```
POST /webhooks/shopify/products/create
POST /webhooks/shopify/products/update
POST /webhooks/shopify/products/delete
POST /webhooks/shopify/collections/create
POST /webhooks/shopify/collections/update
POST /webhooks/shopify/collections/delete
```

These are public routes (no auth) but must verify the Shopify HMAC signature.

## Controller Changes

Add methods to `ShopifyWebhookController`:

```php
productCreate(Request $request): Response
productUpdate(Request $request): Response
productDelete(Request $request): Response
collectionCreate(Request $request): Response
collectionUpdate(Request $request): Response
collectionDelete(Request $request): Response
```

### Processing Logic

**Products Create/Update:**
1. Verify HMAC
2. Find integration by shop domain (`X-Shopify-Shop-Domain` header)
3. Normalize the REST webhook payload to the shape expected by `ShopifyProductSyncService`
4. Call `ShopifyProductSyncService::syncProduct()`
5. Return `200 OK` immediately (Shopify requires fast response)

For large products, dispatch a job instead of processing inline.

**Products Delete:**
1. Find the colorway by external identifier mapping
2. Mark it as retired (or log a warning — don't hard delete)

**Collections Create/Update:**
1. Call `ShopifyCollectionSyncService::syncCollection()`

**Collections Delete:**
1. Find collection by mapping and soft delete or flag

### Webhook Payload Normalization

Shopify webhook payloads are REST format, not GraphQL. Write a normalizer that converts:
```json
{ "id": 123, "title": "...", "variants": [...] }
```
to the same shape used by the GraphQL-based sync services.

## Webhook Registration

The Shopify app (TypeScript) registers webhooks on install. Update `app/routes/app.tsx` or wherever webhooks are registered to point the product/collection topics at the Fibermade platform URLs instead of the app's own webhook routes.

The `FIBERMADE_API_URL` env var is already available in the Shopify app.

## Security

HMAC verification already exists in the controller. Reuse `verifyWebhook()`. The webhook secret is stored in the integration or in app config — confirm the current approach and follow it.

## Files to Modify

- `platform/app/Http/Controllers/ShopifyWebhookController.php`
- `platform/routes/webhooks.php` (or wherever the inventory webhook is registered)
- `shopify/app/` — wherever webhook topics are registered (update URLs)

## Files to Create

- Possibly `platform/app/Services/Shopify/ShopifyWebhookNormalizer.php`

## Tests

- Test HMAC verification rejects invalid signatures
- Test product create triggers colorway creation
- Test product update triggers colorway update
- Test product delete retires colorway
- Test collection create/update/delete
- Test unknown shop domain returns 200 silently (don't error on unrecognized stores)

## Checklist

- [ ] Confirm how HMAC secret is currently stored (config vs integration) — follow that pattern
- [ ] Add product and collection webhook routes to `webhooks.php` (or wherever inventory webhook lives)
- [ ] Add `productCreate`, `productUpdate`, `productDelete` methods to `ShopifyWebhookController`
- [ ] Add `collectionCreate`, `collectionUpdate`, `collectionDelete` methods
- [ ] Write `ShopifyWebhookNormalizer` to convert REST payload format → shape expected by sync services
- [ ] Dispatch a job for create/update (don't process inline — Shopify needs fast 200 response)
- [ ] Check `integration.settings.auto_sync` — skip processing if false
- [ ] Update Shopify app webhook registration to point product/collection topics at Fibermade URLs
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
- [ ] Manually verify a webhook fires and is received by the platform (use Shopify webhook tester or ngrok)
