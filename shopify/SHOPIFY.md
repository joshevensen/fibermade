# Shopify App — Release Guide

## Creating a New Version in the Partner Dashboard

When releasing a new version, fill out the **Create version** form as follows.

---

### App name

`Fibermade`

---

### Access — Scopes

**Scopes (required):**

```
read_products,read_inventory,read_locations
```

These are the only scopes the app needs. All sync logic runs in the Laravel platform using the stored access token — the Shopify app itself only reads data to confirm connection status.

- `read_products` — query products, variants, and collections via GraphQL
- `read_inventory` — query inventory quantities and inventory item GIDs
- `read_locations` — query store locations (needed for inventory sync)

**Optional scopes:** leave empty.

**Use legacy install flow:** unchecked.

---

### Redirect URLs

**Production:**
```
https://shopify.fibermade.app/auth/callback
```

**Staging:**
```
https://staging.shopify.fibermade.app/auth/callback
```

---

### URLs

**App URL:**

| Environment | URL |
|-------------|-----|
| Production  | `https://shopify.fibermade.app/` |
| Staging     | `https://staging.shopify.fibermade.app/` |

**Embed app in Shopify admin:** checked.

**Preferences URL:** leave empty.

---

### Webhooks API Version

Select the most recent stable version available (e.g. `2026-01`). Keep this in sync with `api_version` in `shopify.app.toml`.

> Note: `shopify.app.toml` is the source of truth. If the dashboard offers a newer version than what's in the toml, update the toml first, commit, and redeploy before releasing.

---

### Everything else

Leave POS, App proxy, and all other sections at their defaults.

---

## Source of Truth

All of the above values are derived from `shopify.app.toml`. If something looks wrong in the dashboard, check the toml first.
