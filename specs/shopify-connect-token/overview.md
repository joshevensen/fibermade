# Shopify Connect Token — Overview

## Goal

Replace the Sanctum API token connection flow with a static UUID (the "connect token") that lives on the `Account` model. The connect token is generated at account creation, always visible on the Shopify API settings page, and never changes unless the creator explicitly resets it.

This removes the "generate token → copy → paste" friction from the Shopify connection flow — non-technical users just copy one value they always have access to.

---

## Problem with the Current Flow

The current flow uses a Laravel Sanctum API token:

1. Creator visits Settings → Shopify API
2. Clicks "Generate token" — a new Sanctum token is created
3. Copies the token (shown only once)
4. Opens the Shopify app, pastes the token, clicks Connect
5. The Shopify app calls `POST /api/v1/integrations` using the token as a Bearer credential

**Why this is friction:**
- Token is shown only once — if you miss it, you generate again
- Non-technical users don't know what an "API token" is
- The Sanctum token grants broad authenticated API access; this is more power than needed for a connection flow

---

## Target Flow (post-migration)

1. Creator visits Settings → Shopify API — the connect token is **always visible**
2. Copies it (one value, always there, copy button)
3. Opens the Shopify app, pastes it, clicks Connect
4. The Shopify app calls `POST /api/v1/shopify/connect` with the token — no Bearer auth required
5. Done

---

## Architecture

### Connect Token

- A UUID stored on the `accounts` table (`shopify_connect_token`)
- Generated automatically on account creation via the factory/seeder and in the registration flow
- Unique per account, indexed
- Can be reset by the creator (generates a new UUID, invalidates old connections from the Shopify app perspective)
- **Not a secret in the cryptographic sense** — but acts as a shared identifier. The practical risk of someone knowing your UUID is low: they would need to link *their* Shopify store to *your* Fibermade account. This is reversible via disconnect.

### New Dedicated Endpoints

Three new public routes (no Sanctum middleware) handled by `ShopifyConnectionController`:

```
POST /api/v1/shopify/connect
POST /api/v1/shopify/disconnect
GET  /api/v1/shopify/status
```

All three accept `connect_token` to identify the account. The `connect` endpoint also receives `shop` and `shopify_access_token`. Rate limiting applies to `connect`.

### What the Shopify App Stores

The `FibermadeConnection` Prisma model no longer needs `fibermadeApiToken`. It stores `connectToken` (the UUID) instead — used to call `disconnect` and `status` endpoints. Since the connect token is not a secret, field encryption on it is not needed (but leaving it is harmless).

---

## What Changes

### Platform (Laravel)

| What | Change |
|------|--------|
| `accounts` table | Add `shopify_connect_token` UUID column (unique, not null) |
| `Account` model | Add `shopify_connect_token` to fillable, cast, and add `generateConnectToken()` helper |
| `AccountFactory` | Auto-generate UUID in factory definition |
| Registration flow | Generate UUID when account is created |
| `ShopifyConnectionController` | New controller: `connect`, `disconnect`, `status` |
| `routes/api.php` | Add three new public routes (throttled) |
| `ShopifyConnectionCard.vue` | Remove "Generate token" flow; show connect token with copy button |
| `UserController::storeApiToken()` | Delete |
| Route `POST /creator/settings/api-token` | Delete |
| Sanctum tokens named `'shopify'` | Revoke all (one-time migration) |

### Shopify App (TypeScript)

| What | Change |
|------|--------|
| `app._index.tsx` | Call `POST /api/v1/shopify/connect` instead of `/api/v1/integrations`; use `GET /api/v1/shopify/status` in loader |
| `webhooks.app.uninstalled.tsx` | Call `POST /api/v1/shopify/disconnect` instead of `PATCH /api/v1/integrations/{id}` |
| `FibermadeConnection` Prisma model | Replace `fibermadeApiToken` with `connectToken` (plain string, no encryption needed) |
| Prisma migration | Rename/replace the column; reset migration since schema is simple |

---

## New Endpoint Specs

### `POST /api/v1/shopify/connect`

**No auth.** Rate limited (10 requests/minute per IP).

Request:
```json
{
  "connect_token": "uuid",
  "shop": "example.myshopify.com",
  "shopify_access_token": "shpat_..."
}
```

Logic:
1. Find `Account` by `shopify_connect_token` → 404 if not found (generic error: "Invalid connect token")
2. Create (or replace) the Shopify integration for that account — same logic as the existing `IntegrationController::store` (which already handles replacing old integrations and re-associating external identifiers)
3. Return `{ integration_id: int }`

Response `201`:
```json
{ "data": { "integration_id": 123 } }
```

---

### `POST /api/v1/shopify/disconnect`

**No auth.**

Request:
```json
{
  "connect_token": "uuid",
  "shop": "example.myshopify.com"
}
```

Logic:
1. Find `Account` by `connect_token`
2. Find the active Shopify integration for that account matching the shop domain
3. Set `active = false`
4. Return `204`

---

### `GET /api/v1/shopify/status`

**No auth.**

Query params: `?connect_token=uuid&shop=example.myshopify.com`

Logic:
1. Find `Account` by `connect_token` → if not found, return `{ active: false }`
2. Find Shopify integration for account + shop
3. Return `{ active: bool, integration_id: int|null }`

Used by the Shopify app loader to verify the connection is still live without needing Bearer auth.

---

## Settings Page UI

`ShopifyConnectionCard.vue` — the "Generate token" section is replaced with a simple always-visible connect token display:

```
Your Fibermade Connect Token
[  3f8a-...uuid...  ] [Copy]

Use this token to connect your Shopify store in the Fibermade Shopify app.
[Reset token]  ← destructive, confirmation required, warn that it will break existing connections
```

The `hasToken` / `loading` / `generateToken` reactive state is removed entirely. The token comes from the `shopify` prop (already passed from the server).

---

## Migration Notes

- Existing connected accounts will have their `FibermadeConnection.fibermadeApiToken` invalidated once the Shopify app is redeployed. They will need to reconnect — show a clear "reconnect required" message in the Shopify app.
- Revoke all Sanctum tokens named `'shopify'` via a one-time `php artisan tinker` call or a migration.
- The `shopify_connect_token` column should be backfilled for existing accounts in the same migration that adds the column.

---

## Tasks

See `tasks/` for individual task files.

| Task | Scope | Notes |
|------|-------|-------|
| Task 01 | Platform — DB + model | Add column, backfill, update Account |
| Task 02 | Platform — controller + routes | `ShopifyConnectionController` + 3 endpoints |
| Task 03 | Platform — settings UI | Replace generate-token flow with static display |
| Task 04 | Shopify app | Update connect/disconnect/status calls + Prisma schema |
