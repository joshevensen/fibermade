# Fibermade Platform

Laravel application that serves the Fibermade API and the Inertia/Vue dashboards for creators and stores. It is the system of record for catalog, inventory, orders, and customers.

---

## Tech stack

- **Backend:** PHP 8.2+, Laravel 12, Sanctum (API auth), Fortify (auth), Pennant (feature flags), Cashier (billing, for later).
- **Frontend:** Inertia v2, Vue 3, Tailwind v4, Vite. Wayfinder for type-safe routes/actions.
- **Database:** SQLite by default; MySQL or PostgreSQL via [DBngin](https://dbngin.com/) for local dev or production-like setups.

---

## Prerequisites

- PHP 8.2+, Composer, Node (see `package.json` for supported version).
- **Local development:** [Laravel Herd](https://herd.laravel.com/) — provides PHP, nginx, and local URLs (e.g. `https://platform.test`) so you don’t run `php artisan serve`.
- **Database:** [DBngin](https://dbngin.com/) (or similar) if you use MySQL/PostgreSQL locally. Default `.env` uses SQLite.

---

## Setup

1. **Clone and install**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure `.env`**
   - Set `APP_URL` to your Herd site URL (e.g. `https://platform.test`) if using Herd.
   - Default DB is SQLite. For MySQL/PostgreSQL, set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (e.g. to match DBngin).

3. **Database**
   ```bash
   php artisan migrate
   php artisan db:seed   # if you have seeders
   ```

4. **Frontend**
   ```bash
   npm install
   npm run build        # or use npm run dev while developing
   ```

5. **Herd (optional)**  
   Add this directory as a site in Herd so the app is served over HTTPS at a `.test` domain. Set `APP_URL` in `.env` to that URL.

6. **Stripe (optional, for Creator billing)**  
   Set `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and `STRIPE_PRICE_ID` (Stripe Price ID for the $39/month subscription). Configure the [Stripe Customer Portal](https://dashboard.stripe.com/settings/billing/portal) in the Stripe Dashboard for self-service billing (payment methods, invoices, cancellation).  
   Use **Stripe test mode** (test keys) for local/staging. Test card: `4242 4242 4242 4242`. To test webhooks locally, use the [Stripe CLI](https://docs.stripe.com/stripe-cli): `stripe listen --forward-to https://platform.test/webhooks/stripe` (replace with your local URL), then trigger events with `stripe trigger checkout.session.completed` etc.

---

## Running the app

- **With Herd:** Herd serves the app. Run frontend (and optionally queue/logs):
  ```bash
  npm run dev
  ```
  For queue worker and logs: `php artisan queue:listen`, `php artisan pail`, etc., in separate terminals, or use `composer run dev` (which also starts `php artisan serve` — redundant under Herd but harmless).

- **Without Herd:**
  ```bash
  composer run dev
  ```
  This runs `php artisan serve`, queue, pail, and Vite together.

---

## Tests and formatting

- **Tests:** Pest. Run all: `php artisan test --compact`. Run a file: `php artisan test --compact tests/Feature/SomeTest.php`. Filter by name: `php artisan test --compact --filter=testName`.
- **PHP style:** `vendor/bin/pint --dirty`
- **Frontend style:** `npm run format` (and `npm run format:check` if available).

---

## API

The app exposes a versioned API at `/api/v1/` used by the Shopify app and other clients. See `routes/api.php` for routes. API auth is token-based (Sanctum).

---

## Production

The platform is deployed with [Laravel Forge](https://forge.laravel.com/) on [DigitalOcean](https://www.digitalocean.com/). Forge manages the server, PHP, nginx, queues, and deployments.

---

## More

- **Repo root:** [../README.md](../README.md)
- **Conventions and tooling (AI/agents):** [AGENTS.md](AGENTS.md), [CLAUDE.md](CLAUDE.md)
