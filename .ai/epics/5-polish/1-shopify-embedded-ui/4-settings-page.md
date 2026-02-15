status: done

# Story 5.1: Prompt 4 -- Settings Page

## Context

The Shopify app has an Integration record on the platform with a `settings` JSON field that currently stores `{ "shop": "<domain>" }`. This field can be extended to store sync preferences. The `FibermadeClient` has `updateIntegration(id, payload)` which can update the `settings` field. The app navigation (updated in Prompt 1) includes a "Settings" link pointing to `/app/settings`, but the route doesn't exist yet. Collections are synced from Shopify and stored as `Collection` records on the platform, associated with the account. The `FibermadeClient` has `listCollections(params?)` to list collections (account-scoped).

## Goal

Create a settings page at `/app/settings` where merchants can manage their sync preferences. Initially this includes: choosing which collections to sync (include/exclude specific collections), and toggling auto-sync on product webhook changes. These preferences are stored in the Integration's `settings` JSON field and read by the sync services when processing webhooks.

## Non-Goals

- Do not modify the webhook handlers to read settings in this prompt -- just store the preferences. The webhook handlers can be updated later to check these settings.
- Do not add account management or billing settings
- Do not add notification preferences
- Do not modify the Integration model or API beyond using the existing `settings` field

## Constraints

- Create a new route file `shopify/app/routes/app.settings.tsx`
- The loader fetches the current integration settings and available collections
- The action saves updated settings via `FibermadeClient.updateIntegration()`
- Settings stored in the Integration's `settings` JSON field alongside the existing `shop` key:
  ```json
  {
    "shop": "my-store.myshopify.com",
    "auto_sync": true,
    "excluded_collection_ids": [5, 12]
  }
  ```
- Use s-* components and native HTML form elements (match sync-history, index pattern): checkbox for auto-sync toggle, checkboxes for collection selection
- Show a save button that submits the form, with success/error feedback
- If not connected, redirect to `/app`
- Default `auto_sync` to `true` if not set
- Default `excluded_collection_ids` to `[]` (all collections synced) if not set

## Acceptance Criteria

- [x] Route `app.settings.tsx` exists and renders at `/app/settings`
- [x] Loader fetches integration settings and collections list
- [x] Redirects to `/app` if shop is not connected
- [x] Auto-sync toggle (checkbox) defaults to on
- [x] Collection list shows all synced collections with checkboxes
- [x] Collections are checked by default (opt-out model: uncheck to exclude)
- [x] Save button submits settings via `updateIntegration()`
- [x] Success banner shown after saving
- [x] Error banner shown if save fails
- [x] Existing `shop` setting preserved when saving (merged, not overwritten)
- [x] Page title is "Settings"
- [x] Navigation highlights "Settings" when on this page

---

## Tech Analysis

- **Settings schema**: The Integration `settings` field is a JSON column. Currently only `shop` is stored. Add `auto_sync` (boolean) and `excluded_collection_ids` (number array) alongside it. When saving, merge with existing settings to preserve `shop`.
- **Loader data**: Fetch two things in parallel:
  1. Integration via `getIntegration()` -- extract current settings
  2. Collections via `listCollections()` -- for the collection picker. Pass `limit: 100` to get all collections.
- **Collection display**: Each collection has `id`, `name`, and `colorways_count`. Show with checkboxes (one per collection). Checked = synced (not excluded). This is an opt-out model.
- **Form state**: Use React `useState` for `autoSync` and `excludedCollectionIds`. Initialize from loader data. On save, POST the form with the updated settings.
- **Action**: Parse the form data, construct the settings object (preserving `shop`), call `updateIntegration(integrationId, { settings: mergedSettings })`. Return success/error.
- **Merge pattern**: `{ ...currentSettings, auto_sync: formAutoSync, excluded_collection_ids: formExcluded }` -- this preserves `shop` and any other future settings.

## References

- `shopify/app/routes/app._index.tsx` -- loader/action pattern
- `shopify/app/services/fibermade-client.server.ts` -- `getIntegration()`, `updateIntegration()`, `listCollections()`
- `shopify/app/services/fibermade-client.types.ts` -- `IntegrationData`, `CollectionData`
- `platform/app/Models/Integration.php` -- settings JSON field
- `platform/app/Http/Controllers/Api/V1/IntegrationController.php` -- update endpoint

## Files

- Create `shopify/app/routes/app.settings.tsx` -- new settings page with loader, action, and form UI
