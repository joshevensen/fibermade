# Shopify Inventory Sync

Bidirectional inventory sync between Fibermade and Shopify. Fibermade is the source of truth for catalog and inventory; Shopify receives updates via manual push and webhooks pull changes back.

## Sync Philosophy (Fibermade as Source of Truth)

- **Catalog**: Colorways, Bases, and Inventory are managed in Fibermade. Changes sync to Shopify via model observers (when enabled).
- **Inventory quantities**: Manual push from Fibermade to Shopify. Shopify inventory changes (e.g., sales, manual adjustments) are pulled via webhooks.
- **No bidirectional catalog sync**: Product/variant structure is created from Fibermade. Shopify manual edits are overwritten on next sync.

## Inventory→Variant ExternalIdentifier Structure

ExternalIdentifiers map Fibermade Inventory to Shopify variants:

| Column | Value |
|--------|-------|
| `identifiable_type` | `App\Models\Inventory` |
| `identifiable_id` | Fibermade Inventory ID |
| `integration_id` | Integration record (Shopify) |
| `external_type` | `shopify_variant` |
| `external_id` | Shopify variant GID (e.g. `gid://shopify/ProductVariant/123`) |

One Inventory (Colorway × Base) maps to one Shopify variant per product. Bases are shared across colorways; each Shopify variant is unique per product.

### Lookup

```php
ExternalIdentifier::where('integration_id', $integration->id)
    ->where('external_type', 'shopify_variant')
    ->where('external_id', $variantGid)
    ->where('identifiable_type', Inventory::class)
    ->first();
```

## Webhook URL for Shopify App Configuration

Register the inventory webhook in your Shopify app:

- **URL**: `https://<your-platform-domain>/webhooks/shopify/inventory`
- **Topic**: `inventory_levels/update`
- **Format**: JSON

The endpoint verifies the `X-Shopify-Hmac-Sha256` header using `SHOPIFY_WEBHOOK_SECRET`. Configure this secret when creating the webhook subscription.

### Example Webhook Registration (Shopify Admin API)

```graphql
mutation webhookSubscriptionCreate($topic: WebhookSubscriptionTopic!, $webhookSubscription: WebhookSubscriptionInput!) {
  webhookSubscriptionCreate(topic: $topic, webhookSubscription: $webhookSubscription) {
    webhookSubscription { id }
    userErrors { field message }
  }
}
```

Variables: `topic: INVENTORY_LEVELS_UPDATE`, `webhookSubscription.callbackUrl: "https://platform.example.com/webhooks/shopify/inventory"`, and the appropriate format and secret.

## Import Price Conflict Handling

When importing products from CSV, multiple products may use the same Base (e.g., "Fingering") with different prices. Fibermade Bases have a single `retail_price`.

- **Strategy**: First encountered price wins. Conflicting prices are logged as warnings.
- **Example**: Product "Mountain Mist" has Fingering at $28; Product "Ocean Breeze" has Fingering at $32. Base "Fingering" is set to $28. A warning is logged for the $32 conflict.
- **Display**: Warnings appear in the import success message and are logged to IntegrationLog with `sync_source: import`.

## Configuration

| Environment Variable | Description |
|---------------------|-------------|
| `SHOPIFY_WEBHOOK_SECRET` | Shared secret for webhook HMAC verification. Required for webhook processing. |
| `SHOPIFY_CATALOG_SYNC_ENABLED` | `true` to enable model observers that sync catalog changes to Shopify. Default: `false`. |
| `SHOPIFY_API_MAX_RETRIES` | Max retries for Shopify GraphQL requests (5xx/429). Default: `3`. |
| `SHOPIFY_API_INITIAL_BACKOFF_MS` | Initial backoff in ms for exponential backoff. Default: `1000`. |

Integration credentials (shop domain, access token) are stored per-account in the `integrations` table.

**Per-account catalog sync**: When `SHOPIFY_CATALOG_SYNC_ENABLED` is true, catalog sync can be turned off for a specific account by setting the integration’s `settings.catalog_sync_enabled` to `false`. When the global flag is true and the integration does not set this key, sync is enabled for that account.

## Queue workers

Observer-triggered sync (Colorway, Base, Media changes) is dispatched to the queue. You must run a queue worker for catalog sync to run:

- `php artisan queue:work` (or your configured driver)
- Or use Laravel Horizon / a process manager (e.g. Supervisor) to keep workers running.

If the queue is not processed, catalog changes will not sync to Shopify until jobs are run.

## Troubleshooting

### Webhook returns 401

- **Cause**: Invalid HMAC or missing `SHOPIFY_WEBHOOK_SECRET`.
- **Fix**: Ensure `SHOPIFY_WEBHOOK_SECRET` matches the secret used when registering the webhook. Verify `X-Shopify-Hmac-Sha256` header is present.

### Webhook returns 200 but inventory not updating

- **Cause**: No Integration for the shop domain, or no ExternalIdentifier for the variant.
- **Fix**: Confirm Integration exists with matching shop domain in settings. For new products, run manual push first to create ExternalIdentifiers. Check IntegrationLog for "no Fibermade Inventory for Shopify variant" warnings.

### Push to Shopify fails with "Shopify integration is not configured"

- **Cause**: Integration missing or credentials incomplete.
- **Fix**: Add shop domain and access token in Integration settings. Ensure `credentials` includes valid `access_token` and `settings` includes `shop` or `store_url`.

### Sync loop (infinite push/pull)

- **Cause**: Push triggers Shopify webhook, which updates Fibermade, which triggers another push.
- **Mitigation**: Sync source is tracked in IntegrationLog. Webhook pulls use `sync_source: webhook` and do not trigger reverse sync. Ensure observers do not fire on webhook-originated updates.

### Catalog changes not syncing to Shopify

- **Cause**: `SHOPIFY_CATALOG_SYNC_ENABLED` is `false`, integration has `settings.catalog_sync_enabled` false, or queue workers not running.
- **Fix**: Set `SHOPIFY_CATALOG_SYNC_ENABLED=true`, ensure the integration has catalog sync enabled (or omit the setting to default to on), and run `php artisan queue:work` so observer jobs process.

### Import warnings about price conflicts

- **Expected behavior**: Base prices are set from first encounter. Review IntegrationLog for conflict details. Adjust Base `retail_price` manually in Fibermade if needed, then re-push to Shopify.

## Monitoring

**IntegrationLog**: All push, pull, and catalog sync operations write to `integration_logs`. Use the creator Integration Logs index (per integration) to inspect status, message, and metadata (e.g. `sync_source`, `direction`, errors). Filter by `status` (success, warning, error) to spot failures.

**Rate limits**: When the Shopify API returns 429, the client logs a warning (`Shopify API rate limit (429)`) with shop and retry-after. Monitor your log channel for these if you see sync delays.

**Repeated failures**: To alert on repeated sync failures, query `integration_logs` for a given integration where `status = error` and `created_at` is recent (e.g. multiple in the last hour). Use your logging or monitoring stack to alert on error volume.

**Metrics**: IntegrationLog entries include `synced_at` and `status`. You can derive success rate and approximate duration by querying logs (e.g. count by status over time windows). Optionally add duration to job metadata when you need precise timings.
