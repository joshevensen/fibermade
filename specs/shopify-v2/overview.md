# Shopify Integration v2 — Overview

## Goal

Move all sync logic from the Shopify TypeScript app into the Laravel platform. The Shopify app becomes a thin, one-page shell. Fibermade becomes the source of truth for sync — both for manual triggers and webhook-driven automatic updates.

This also fixes the root cause of the collections and inventory sync issues: the TypeScript app ran product import, then collection import as sequential steps with no coordination, and any mid-run failure left the state partially committed. Moving to Laravel means we can orchestrate the full sync as a single queued job with proper error handling and rollback awareness.

---

## Current Architecture (v1)

```
[Shopify Admin App] ──buttons──> [TypeScript Sync Services]
                                         │
                              GraphQL queries to Shopify
                                         │
                              POST /api/v1/* ──────────> [Fibermade Platform]
                                         │
                              [Shopify App webhooks] ──> [TypeScript] ──> Fibermade API
```

**Problems:**
- Sync logic split across two codebases (TypeScript + PHP)
- Collections sync runs after products, relies on mappings already existing — fragile
- Inventory sync is a separate code path that can drift
- Errors surface in the Shopify app, not in Fibermade where the creator is working
- Manual sync buttons are buried in the Shopify admin, not in Fibermade

---

## Target Architecture (v2)

```
[Shopify Admin App] ──install/auth──> stores access_token in Fibermade
      │                                         │
      └── one page: connection status           │
                    link to Fibermade           │
                                                ▼
                                   [Fibermade Platform]
                                          │
                              ShopifyGraphqlClient (PHP)
                              queries Shopify Admin GraphQL API
                                          │
                              ┌───────────┴──────────────┐
                              │                          │
                    [Manual sync buttons]    [Webhook endpoints]
                    on /settings?tab=shopify-api    receive directly
                                                    from Shopify
```

---

## Key Decisions

### Access Token Storage
Already in place. The `integrations` table stores:
- `credentials`: encrypted JSON `{ "access_token": "shpat_..." }`
- `settings`: JSON `{ "shop": "store.myshopify.com" }`

The `ShopifyGraphqlClient` class already exists in the platform — we extend it, not replace it.

### Sync Direction
**Pull only.** Fibermade pulls from Shopify. We do not push colorways to Shopify as part of this migration (that's a separate future feature). The existing push route and service in the Shopify app are removed in Task 09 — push will be rebuilt properly in a future project when the time comes.

### Webhook Handling
The platform already has a `ShopifyWebhookController` handling `inventory_levels/update`. We extend it to handle product and collection webhooks directly. The Shopify app registers the webhooks on install; Fibermade receives and processes them.

The Shopify app's existing webhook routes (`webhooks.products.*.tsx`, `webhooks.collections.*.tsx`) that forward to Fibermade will be removed once the platform handles them directly.

### Sync Orchestration
A new `ShopifySyncOrchestrator` service coordinates the three sync types in the correct order:
1. Products (colorways + bases)
2. Collections (depends on product→colorway mappings existing)
3. Inventory (depends on variant→inventory mappings existing)

Each sync type can also be triggered independently.

### Sync Jobs
Long-running syncs run as queued Laravel jobs. The UI polls `GET /creator/shopify/sync/status` every few seconds until the job completes. Initial imports of large catalogs can take minutes.

### Automatic Sync via Webhooks
When `auto_sync` is enabled on the integration, incoming Shopify webhooks (product/collection create, update, delete) trigger the appropriate sync job automatically. This is handled in Task 08 — no frontend broadcasting required. The polling UI in the settings page will pick up the updated state if the creator happens to be on that page.

---

## Shopify Thin Client Page

The Shopify admin app becomes a single, minimal page:

The page handles two states:

**Not connected:** Marketing-style layout — headline, 3 benefit bullets, API token field, "Connect Fibermade account" button, "Sign up" link. No redirect to a separate connect screen; the form lives right on the home page.

**Connected:** Fibermade logo + description, "Log in to Fibermade →" deep-link to `/creator/settings?tab=shopify-api`, connection status (shop domain, date, Disconnect button).

The connect flow (`app.connect.tsx`) is deleted — its logic moves into the home page loader/action. The settings page (`app.settings.tsx`) is also deleted. The nav is removed entirely. The Shopify app's only jobs are OAuth, webhook registration, and rendering this one page.

**Collection exclusions:** Removed entirely. Archived collections are automatically excluded from sync. Creators archive what they don't want synced.

**Auto-sync toggle:** Moved to the Fibermade settings page (see below).

---

## Fibermade Settings Page (Shopify API Tab)

The `/creator/settings?tab=shopify-api` tab gets expanded with:

- **Connection status card** — shows connected store domain, last synced timestamps
- **Auto-sync toggle** — when enabled, Shopify webhook events automatically trigger a pull into Fibermade
- **Manual sync controls:**
  - Sync Products (pull colorways + bases from Shopify)
  - Sync Collections
  - Sync Inventory
  - Sync All (runs all three in order)
- **Sync progress** — live feedback during a running sync (counts, current item)
- **Sync history** — recent log entries from the existing `integration_logs` table (errors and warnings surfaced prominently)
- **Error detail** — expandable list of failed entities pulled from `integration_logs`

---

## What Stays in the Shopify App

- OAuth flow (Shopify requires this to live in the app)
- Webhook registration on install
- App uninstall handler (cleans up the integration)
- The single-page UI (handles both not-connected and connected states — connect logic is built into it, not a separate screen)

---

## Migration Path

The tasks are ordered so each one delivers working, testable functionality before the next begins. The Shopify app refactor is one of the later tasks — we can keep the old buttons working until the platform-side sync is proven.

See `tasks/` for individual task specs.

---

## How to Work Through This

Each task is implemented in a **separate chat session** with a fresh context window. This keeps each session focused and prevents context bloat across a long project.

### Session grouping

| Session | Tasks | Notes |
|---------|-------|-------|
| 1 | Task 01 | GraphQL client — unblocks everything else |
| 2 | Tasks 02 + 03 + 04 | Three small services, same pattern, no shared files |
| 3 | Task 05 | Orchestrator + jobs — depends on 02/03/04 |
| 4 | Task 06 | Controller + routes — depends on 05 |
| 5 | Task 07 | Settings UI — depends on 06 |
| 6 | Task 08 | Webhooks — depends on 02/03 |
| 7 | Task 09 | Shopify thin client — depends on all prior tasks being live |

### Workflow per session

1. Open a new chat
2. Paste the prompt from the **Starting Prompt** section of the task file
3. Implement, review, run tests
4. Commit when satisfied
5. Close the chat, open a new one for the next session

### New chat context is fine

Each task file is self-contained with enough context to start fresh. The spec files, CLAUDE.md guidelines, and the codebase itself provide all the context Claude needs. You do **not** need to carry this chat forward.
