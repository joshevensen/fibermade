# Fibermade — Project Guide for Claude

## Monorepo Structure

```
fibermade/
├── platform/    # Laravel 12 + Inertia (Vue 3) — main app & API
├── shopify/     # React Router (TypeScript) — Shopify embedded app
└── STAGING.md   # Deployment notes
```

Each subdirectory is an independent app with its own dependencies, config, and deployment. When working on a task, confirm which app is in scope before touching files.

---

## Platform (Laravel 12 + Vue 3 + Inertia)

The platform is the main Fibermade app. It serves the creator dashboard, store dashboard, public website, and the REST API consumed by the Shopify app.

### Running

```bash
cd platform
npm run dev           # vite only (if using Herd — preferred)
```

The app is served by Laravel Herd at `https://fibermade.test`.

### Testing & Formatting

```bash
php artisan test --compact                              # all tests
php artisan test --compact tests/Feature/SomeTest.php  # single file
php artisan test --compact --filter=testName           # filter
vendor/bin/pint --dirty                                # PHP formatting
npm run format                                         # frontend formatting
```

Always run `vendor/bin/pint --dirty` before finalizing PHP changes. Always write or update tests — every change needs test coverage.

### Key Directories

| Path | Purpose |
|------|---------|
| `app/Models/` | Eloquent models |
| `app/Http/Controllers/` | Web controllers (Inertia responses) |
| `app/Http/Controllers/Api/V1/` | REST API controllers (JSON responses) |
| `app/Http/Controllers/Creator/` | Creator-scoped web controllers |
| `app/Http/Requests/` | Form Request validation classes |
| `app/Http/Resources/Api/V1/` | Eloquent API Resources |
| `app/Services/` | Business logic services |
| `app/Services/Shopify/` | Shopify-specific services (sync, GraphQL client) |
| `app/Jobs/` | Queued jobs |
| `app/Enums/` | Typed enums (ColorwayStatus, OrderStatus, etc.) |
| `app/Policies/` | Authorization policies |
| `resources/js/pages/` | Inertia Vue page components |
| `resources/js/components/` | Shared Vue components |
| `resources/js/layouts/` | Page layouts (CreatorLayout, StoreLayout, etc.) |
| `routes/web.php` | Public routes |
| `routes/creator.php` | Creator dashboard routes (`/creator/*`) |
| `routes/store.php` | Store dashboard routes (`/store/*`) |
| `routes/api.php` | API v1 routes (`/api/v1/*`) |

### Naming Conventions

- **Models:** singular PascalCase — `Colorway`, `Base`, `Inventory`
- **Controllers:** `{Model}Controller` or `{Scope}{Model}Controller`
- **Services:** `{Domain}Service` — e.g., `ShopifyProductSyncService`, `InventorySyncService`
- **Jobs:** descriptive — `SyncShopifyProductsJob`
- **Form Requests:** `Store{Model}Request`, `Update{Model}Request`
- **Enums:** PascalCase class, TitleCase keys — `ColorwayStatus::Active`
- **Vue pages:** `{Name}Page.vue` — `IndexPage.vue`, `ShowPage.vue`
- **Vue components:** PascalCase — `ShopifySyncCard.vue`

### Key Patterns

**Inertia rendering:**
```php
return Inertia::render('creator/colorways/IndexPage', ['colorways' => $colorways]);
```

**API controllers** extend `ApiController`, scope queries to account, return Resources:
```php
$this->authorize('view', $colorway);
return $this->successResponse(new ColorwayResource($colorway));
```

**ExternalIdentifier** — polymorphic table linking internal models to external IDs (Shopify GIDs). Always look up/create mappings here when integrating with Shopify. Key fields: `integration_id`, `identifiable_type`, `identifiable_id`, `external_type`, `external_id`, `data` (JSON).

**Integration model** — stores Shopify connection. `credentials` is encrypted JSON `{ "access_token": "..." }`, `settings` is plain JSON `{ "shop": "store.myshopify.com", "auto_sync": true, "sync": {...} }`. Use `getShopifyConfig()` to retrieve the access token.

**ShopifyGraphqlClient** — already exists in `app/Services/`. Makes authenticated GraphQL requests to `https://{shop}/admin/api/2025-01/graphql.json`. Extend this for new queries rather than creating a new client.

**Wayfinder** — generates TypeScript types for routes/controllers. Run `php artisan wayfinder:generate` after adding routes. Import in Vue with `import { store } from '@/actions/...'`.

---

## Shopify App (React Router + TypeScript + Prisma)

A thin Shopify embedded app. After the v2 migration (`specs/shopify-v2/`), this will be a single-page shell — OAuth, webhook registration, and connection status only. All sync logic lives in the platform.

### Running

```bash
cd shopify
npm install
npm run setup    # prisma generate + migrate deploy
npm run dev      # shopify app dev
npm run build
npm run test:run # vitest (one-time)
```

### Key Files

| Path | Purpose |
|------|---------|
| `app/routes/app._index.tsx` | Home page (single page post-v2) |
| `app/routes/app.tsx` | App shell with auth |
| `app/routes/auth.$.tsx` | OAuth callback |
| `app/routes/webhooks.app.uninstalled.tsx` | Uninstall handler |
| `app/routes/webhooks.app.scopes_update.tsx` | Scopes update handler |
| `app/shopify.server.ts` | Shopify app config |
| `app/db.server.ts` | Prisma client |
| `prisma/schema.prisma` | DB schema (Session + FibermadeConnection) |

### Database

SQLite on the DO droplet (`file:./dev.sqlite`). Two models:
- `Session` — Shopify OAuth sessions (managed by Shopify framework)
- `FibermadeConnection` — one row per connected shop

Field encryption handled by `prisma-field-encryption` at the application layer — database-agnostic.

---

## Domain Model (Quick Reference)

| Model | What it is |
|-------|-----------|
| `User` | Authenticated user (belongs to Account) |
| `Account` | Creator or Store business account |
| `Colorway` | A yarn colorway (product) |
| `Base` | A yarn base (variant/material) |
| `Inventory` | Stock of a specific Colorway+Base combination |
| `Collection` | A group of Colorways |
| `Integration` | External service connection (e.g., Shopify) |
| `ExternalIdentifier` | Maps internal models ↔ external IDs |
| `IntegrationLog` | Per-entity sync log (status, message, metadata) |
| `Order` | A wholesale or retail order |
| `Customer` | A buyer (store or individual) |

---

## Specs

`specs/` is the working directory for implementation plans. Each feature or migration gets its own subdirectory with an overview and individual task files.

Current specs:
- `specs/shopify-v2/` — Shopify integration v2 migration (active)

When starting a task session, read the relevant `overview.md` and task file before touching any code. Task files include a **Starting Prompt** section you can paste directly into a new chat.

---

## Product Context

`about/` contains durable product documentation — vision, personas, user stories. **Read this for planning and design discussions, not for implementation tasks.** If you're deciding what to build or why, it's useful. If you're implementing a spec, skip it.

Key files:
- `about/Vision.md` — Long-term product direction
- `about/Personas.md` — User archetypes (creators and stores)
- `about/User-Stories.md` — 43 user stories with short IDs (SI-1, WO-3, etc.)
