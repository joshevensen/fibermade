# Task 07 — Settings Page Sync UI

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/07-settings-page-sync-ui.md`, then implement Task 07 in full. Work through the checklist at the bottom of the task file. Tasks 01–06 are already complete. Don't start Task 08.



## Goal

Expand the Shopify API tab at `/creator/settings?tab=shopify-api` with sync controls, connection status, and sync history. This is what creators will use day-to-day instead of the Shopify app.

## Current State

`ShopifyApiCard.vue` currently only shows the API token generator. We'll add a new section (or a new card alongside it) for sync controls.

## What to Build

### Connection Status Card

Shows:
- Green/red indicator: connected or not
- Store domain (e.g. `mystore.myshopify.com`)
- Connected since date
- "Reconnect" link (opens the Shopify app connect flow) — or "Connect" if not yet connected

### Sync Controls Card

**Auto-sync toggle** — "Automatically sync when products change in Shopify". Saved to `integration.settings.auto_sync`. When off, webhooks are still received but not acted on.

Four buttons:
- **Sync Products** — triggers `POST /creator/shopify/sync/products`
- **Sync Collections** — triggers `POST /creator/shopify/sync/collections`
- **Sync Inventory** — triggers `POST /creator/shopify/sync/inventory`
- **Sync All** — triggers `POST /creator/shopify/sync/all`

**During a sync:**
- Buttons disabled
- Shows current step: "Syncing products..." / "Syncing collections..." / "Syncing inventory..."
- Shows a progress indicator (spinner or animated bar)
- Auto-polls `GET /creator/shopify/sync/status` every 3 seconds until `status !== 'running'`

**After sync completes:**
- Shows result summary: "Synced 24 products, 8 collections, 47 inventory items"
- Shows error count with expand to see details
- Updates "Last synced" timestamp

### Last Sync Summary

Below the buttons, show the `last_result` from the most recent sync:

```
Last synced: March 21, 2026 at 2:34 PM
Products: 24 synced, 0 failed
Collections: 8 synced, 0 failed
Inventory: 47 synced, 0 failed
```

If there were errors, show them in an expandable list sourced from recent `integration_logs` entries with `status = 'error'` for this integration.

## Data Flow

The `SettingsPage` controller passes shopify integration state as a prop:

```php
'shopify' => [
    'connected' => true,
    'shop' => 'store.myshopify.com',
    'auto_sync' => true,
    'sync' => [...],        // from integration.settings.sync (last run summary)
    'recent_errors' => [...], // from integration_logs where status = error, last 20
]
```

The Vue component handles polling and button state locally.

## Component Structure

Consider splitting into:
- `ShopifyConnectionCard.vue` — connection status + token generation (rename/refactor current `ShopifyApiCard.vue`)
- `ShopifySyncCard.vue` — sync buttons + status + last result

## Files to Create/Modify

- `platform/resources/js/pages/creator/settings/components/ShopifySyncCard.vue` (new)
- `platform/resources/js/pages/creator/settings/components/ShopifyApiCard.vue` (refactor — split connection status out)
- `platform/resources/js/pages/creator/settings/SettingsPage.vue` (add new card to shopify-api tab)
- `platform/app/Http/Controllers/Creator/SettingsController.php` (pass shopify prop)

## Notes

- Follow the existing card/tab patterns in the settings page
- Dark mode support required (follow existing patterns)
- Don't poll when tab is not active or component is unmounted

## Checklist

- [ ] Update `SettingsController` to pass `shopify` prop (connected, shop, sync state)
- [ ] Refactor `ShopifyApiCard.vue` → `ShopifyConnectionCard.vue` (connection status + token generation)
- [ ] Create `ShopifySyncCard.vue` with auto-sync toggle and four sync buttons
- [ ] Implement button disable state during active sync
- [ ] Implement polling (`GET /creator/shopify/sync/status` every 3s while `status === 'running'`)
- [ ] Stop polling when component is unmounted or tab changes
- [ ] Show current step label during sync ("Syncing products...", etc.)
- [ ] Show last sync summary (counts per step)
- [ ] Show expandable error list when errors exist
- [ ] Wire auto-sync toggle to save to integration settings via a PATCH endpoint or form submit
- [ ] Add both new cards to the `shopify-api` tab in `SettingsPage.vue`
- [ ] Confirm dark mode works
- [ ] Manual test: trigger sync, watch polling, confirm summary updates
