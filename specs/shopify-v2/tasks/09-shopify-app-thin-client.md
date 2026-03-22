# Task 09 — Shopify App Thin Client

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/09-shopify-app-thin-client.md`, then implement Task 09 in full. Work through the checklist at the bottom of the task file. Tasks 01–08 are already complete and live in production. This task touches the Shopify app (`shopify/` directory), not the Laravel platform.



## Goal

Reduce the Shopify admin app to a single, minimal page. Remove all sync logic, buttons, and multi-page navigation. The app's only jobs are OAuth, webhook registration, and showing connection status.

**Prerequisite:** Tasks 01–08 must be complete and working in production before this task ships. The old buttons should stay functional until the platform-side sync is proven.

## What Gets Removed

| Route/File | Reason |
|------------|--------|
| `app.import.tsx` | Bulk import moves to Fibermade |
| `app.push.tsx` | Push colorway feature TBD (not in this scope) |
| `app.sync-history.tsx` | Sync history moves to Fibermade settings |
| `app/services/sync/bulk-import.server.ts` | Logic now in Laravel |
| `app/services/sync/product-sync.server.ts` | Logic now in Laravel |
| `app/services/sync/collection-sync.server.ts` | Logic now in Laravel |
| `webhooks.products.*.tsx` | Fibermade receives these directly |
| `webhooks.collections.*.tsx` | Fibermade receives these directly |
| `app/services/fibermade-client.server.ts` | Most methods no longer needed |

**Keep:**
- `app._index.tsx` — rewritten to handle both connected and not-connected states
- `app/routes/_index/route.tsx` — splash/install page, keep as-is
- `webhooks.app.uninstalled.tsx` — still needed
- `webhooks.app.scopes_update.tsx` — still needed

**Delete:**
- `app.connect.tsx` — logic moves into `app._index.tsx`
- `app.settings.tsx` — nothing left to configure in the Shopify app
- `app.push.tsx` + test — push feature removed; will be rebuilt properly in a future project
- `app/services/sync/product-push.server.ts` + test — same reason

## Single Page Design

`app._index.tsx` handles two states depending on whether a `fibermadeConnection` exists for the shop.

---

### State A: Not Connected

Inspired by Faire's thin client approach — marketing-first layout that makes the value clear before asking for a token.

```
[Fibermade logo]

Manage your fiber business from one place

✓  Keep your colorways and inventory in sync
✓  Manage collections across Shopify and Fibermade
✓  Changes in Shopify automatically reflect in Fibermade

[Fibermade API token field]
[Connect Fibermade account]  ← primary button

Don't have an account? Sign up at fibermade.app →
```

The token field and connect button are on the same page — no redirect to a separate connect screen. Submitting the form validates the token against the Fibermade API, creates the integration, stores the connection, and re-renders in the connected state.

**Loader:** Check for existing connection → if found, render connected state (no redirect).
**Action:** Move all logic from `app.connect.tsx` action here verbatim.

---

### State B: Connected

```
[Fibermade logo]
Fibermade is a commerce platform built for the fiber community.

[Log in to Fibermade →]   ← deep-links to /creator/settings?tab=shopify-api

────────────────────────────────
Connected to Fibermade
hand-dyer.myshopify.com — connected Mar 20, 2026
This store is linked to your Fibermade account.
[Disconnect]
```

---

### What It Does NOT Have
- No sync buttons (moved to Fibermade)
- No auto-sync toggle (moved to Fibermade)
- No collections to exclude (removed — creators archive what they don't want synced)
- No import progress, no sync history
- No navigation menu items (single page, nav removed entirely)

## Navigation

The `ui-nav-menu` in `app.tsx` currently shows links to Import, Push, Sync History, Settings. After this task, it shows nothing (or just "Settings" if we keep that page separately).

## FibermadeClient Cleanup

The `FibermadeClient` class has many methods (`createColorway`, `createBase`, `createInventory`, etc.) that will no longer be called. Keep only:
- `getIntegration()` — used to load connection status on the thin page
- `storeConnection()` / `connect()` — used in `app.connect.tsx`
- `log()` — may still be useful

Or delete the whole class if nothing needs it — let the connect page make direct fetch calls.

## Files to Delete

**Routes + their test files:**
- `shopify/app/routes/app.connect.tsx` + `app.connect.test.tsx`
- `shopify/app/routes/app.import.tsx` + `app.import.test.tsx`
- `shopify/app/routes/app.push.tsx` + `app.push.test.tsx`
- `shopify/app/routes/app.settings.tsx` + `app.settings.test.tsx`
- `shopify/app/routes/app.sync-history.tsx` + `app.sync-history.test.tsx`
- `shopify/app/routes/webhooks.products.create.tsx` + test
- `shopify/app/routes/webhooks.products.update.tsx` + test
- `shopify/app/routes/webhooks.products.delete.tsx` + test
- `shopify/app/routes/webhooks.collections.create.tsx` + test
- `shopify/app/routes/webhooks.collections.update.tsx` + test
- `shopify/app/routes/webhooks.collections.delete.tsx` + test

**Sync services + their test files (entire `sync/` directory):**
- `shopify/app/services/sync/bulk-import.server.ts` + test
- `shopify/app/services/sync/collection-sync.server.ts` + test
- `shopify/app/services/sync/mapping.server.ts` + test
- `shopify/app/services/sync/metafields.server.ts` + test
- `shopify/app/services/sync/product-push.server.ts` + test
- `shopify/app/services/sync/product-sync.server.ts` + test
- `shopify/app/services/sync/webhook-adapter.server.ts` + test
- `shopify/app/services/sync/webhook-context.server.ts`
- `shopify/app/services/sync/constants.ts`
- `shopify/app/services/sync/types.ts`
- The `shopify/app/services/sync/` directory itself (will be empty)

**Fibermade client:**
- `shopify/app/services/fibermade-client.server.ts` + test
- `shopify/app/services/fibermade-client.types.ts`

**Utilities (if only used by sync services):**
- `shopify/app/utils/date.ts` + test — confirm no remaining usages first

## Prisma Schema Cleanup

Remove `initialImportStatus` and `initialImportProgress` from `FibermadeConnection` — these tracked the old bulk import flow and are no longer meaningful.

```prisma
model FibermadeConnection {
  id                     Int      @id @default(autoincrement())
  shop                   String   @unique
  fibermadeApiToken      String   /// @encrypted
  fibermadeIntegrationId Int
  connectedAt            DateTime @default(now())
  // initialImportStatus and initialImportProgress removed
}
```

Write and run a migration to drop the columns.

## Disconnect Flow

The Disconnect button on the connected state calls an action on `app._index.tsx` that:
1. Deletes the `FibermadeConnection` row from SQLite
2. Calls the Fibermade API to set the integration as inactive (`PATCH /api/v1/integrations/{id}` with `active: false`)
3. Re-renders the not-connected state

This logic currently lives in the existing home page or connect page — find it and carry it over to the rewritten `app._index.tsx`.

## Notes

- The Shopify app is deployed separately — coordinate the deploy with the platform changes
- Make sure webhook registrations are updated (Task 08) before removing the old webhook routes
- Do not ship this task until Tasks 01–08 are live and the Fibermade sync UI is working

## Checklist

**Single page rewrite:**
- [ ] Rewrite `app._index.tsx` loader — return connection state, no redirect
- [ ] Move action logic from `app.connect.tsx` into `app._index.tsx`
- [ ] Build "not connected" UI: logo, headline, benefit bullets, token field, connect button, sign up link
- [ ] Build "connected" UI: logo, description, "Log in to Fibermade →" link, connection status, disconnect button
- [ ] Remove nav items from `app.tsx` (Import, Push, Sync History, Settings)

**Delete routes + tests:**
- [ ] Delete `app.connect.tsx` + test
- [ ] Delete `app.settings.tsx` + test
- [ ] Delete `app.import.tsx` + test
- [ ] Delete `app.push.tsx` + test (push removed — will be rebuilt in a future project)
- [ ] Delete `app.sync-history.tsx` + test
- [ ] Delete `webhooks.products.create.tsx` + test
- [ ] Delete `webhooks.products.update.tsx` + test
- [ ] Delete `webhooks.products.delete.tsx` + test
- [ ] Delete `webhooks.collections.create.tsx` + test
- [ ] Delete `webhooks.collections.update.tsx` + test
- [ ] Delete `webhooks.collections.delete.tsx` + test

**Delete sync services + tests:**
- [ ] Delete entire `app/services/sync/` directory (all files, including `product-push.server.ts`)
- [ ] Delete `app/services/fibermade-client.server.ts` + test
- [ ] Delete `app/services/fibermade-client.types.ts`
- [ ] Check `app/utils/date.ts` — delete if no remaining usages

**Prisma / database cleanup:**
- [ ] Switch `provider = "postgresql"` → `"sqlite"` in `prisma/schema.prisma`
- [ ] Update `DATABASE_URL` env var on the droplet to a file path (e.g. `file:./dev.sqlite`)
- [ ] Remove `initialImportStatus` and `initialImportProgress` from `FibermadeConnection`
- [ ] Reset migrations and create a fresh initial migration (schema is simple enough to start clean)
- [ ] Drop the managed Postgres database once confirmed working

**Verification:**
- [ ] Confirm no remaining imports reference deleted files (TypeScript build should catch this)
- [ ] Deploy Shopify app and verify install/connect flow works end-to-end
- [ ] Verify disconnect flow works (deletes FibermadeConnection, deactivates integration via API, returns to not-connected state)
