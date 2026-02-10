# Fibermade — Shopify App

Installable Shopify app that links a merchant’s store to their Fibermade account. It creates and manages the connection (Integration record on the platform) and will sync catalog, inventory, and (later) orders between Shopify and Fibermade.

---

## Prerequisites

- [Shopify CLI](https://shopify.dev/docs/apps/tools/cli/getting-started)
- Node (see `engines` in `package.json`: e.g. Node 20.19+ or 22.12+)

---

## Setup

1. **Install dependencies**
   ```bash
   npm install
   ```

2. **Environment**
   - Shopify-related env (tunnel, app URL, etc.) is provided by the Shopify CLI when you run `shopify app dev` or by your deployment platform.
   - **Fibermade:** set `FIBERMADE_API_URL` to your Fibermade platform API base URL (e.g. `https://platform.test` for local, or your production API URL). The app uses this to create and manage the Integration record and for future sync features.

3. **Database (Prisma)**  
   See [Using Prisma](#using-prisma) below. To get going:
   ```bash
   npm run setup
   ```
   This runs `prisma generate` and `prisma migrate deploy` (creates/updates the SQLite DB and session + Fibermade connection tables).

---

## Local development

1. Ensure the **Fibermade platform** is running locally (see [platform/README.md](../platform/README.md)) and set `FIBERMADE_API_URL` to its URL (e.g. `https://platform.test` if using Herd).

2. Start the Shopify app:
   ```bash
   npm run dev
   ```
   This runs `shopify app dev` (tunnel, env, and app server). Use the CLI prompt to open the app in a browser (e.g. “Press P to open…”).

3. Install the app on a Shopify dev store when prompted. The in-app “Connect” flow links that store to a Fibermade account via the platform API.

---

## Using Prisma

The app uses [Prisma](https://www.prisma.io/) for session storage and the Fibermade connection table.

- **Schema:** `prisma/schema.prisma` (e.g. `Session`, `FibermadeConnection`).
- **Generate client** (after changing the schema or dependencies):
  ```bash
  npx prisma generate
  ```
  Or use the npm script: `npm run prisma generate` if defined.
- **Migrations**
  - Create a migration: `npx prisma migrate dev --name your_migration_name`
  - Apply migrations (e.g. in production or after pull): `npx prisma migrate deploy`. The `npm run setup` script runs `prisma generate` and `prisma migrate deploy`.
- **Inspect data:** `npx prisma studio` opens the Prisma Studio UI for the current database.

Default local DB is SQLite (`file:dev.sqlite`). For production, you can switch the datasource in `schema.prisma` to a hosted database (e.g. PostgreSQL) and run `prisma migrate deploy` in your deploy pipeline.

---

## What this app does

- **Account linking:** Merchants connect their Shopify store to a Fibermade account (Connect UI in the app → creates/updates an Integration record on the platform via the API).
- **Webhooks:** Handles app uninstall (and other app-specific webhooks you register) to keep platform state in sync.
- **Planned:** Product sync, inventory sync, order/customer sync (see repo root [.ai/epics.md](../.ai/epics.md)).

---

## Commands

| Command | Purpose |
|--------|---------|
| `npm run dev` | Local development (Shopify CLI + app server) |
| `npm run build` | Production build |
| `npm run deploy` | Deploy app config and extension to Shopify |
| `npm run setup` | Prisma generate + migrate deploy |
| `npm run typecheck` | TypeScript type check |
| `npx prisma studio` | Open Prisma Studio |

---

## Shopify template and docs

This app is based on the [Shopify React Router app template](https://github.com/Shopify/shopify-app-template-react-router). Auth, session storage (Prisma), and embedding use `@shopify/shopify-app-react-router`. For auth, GraphQL, webhooks, and deployment details, see the [Shopify App React Router docs](https://shopify.dev/docs/api/shopify-app-react-router) and the template repo.

---

## Production

- **This Shopify app** is deployed to your chosen host (e.g. Fly.io, Render, or a Node server on Forge/DigitalOcean). Configure `FIBERMADE_API_URL` and any DB URL for Prisma in production.
- **Fibermade platform** is deployed with Laravel Forge on DigitalOcean; see [platform/README.md](../platform/README.md).

---

## More

- **Repo root:** [../README.md](../README.md)
- **Platform (API):** [../platform/README.md](../platform/README.md)
